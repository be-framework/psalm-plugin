<?php

declare(strict_types=1);

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

namespace Be\PsalmPlugin\Handler;

use Override;
use PhpParser\Node;
use Psalm\Codebase;
use Psalm\Plugin\EventHandler\AddTaintsInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Storage\AttributeStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TaintKindGroup;

use function array_unique;
use function array_values;
use function explode;
use function is_string;
use function ltrim;
use function str_contains;
use function strtolower;

/**
 * Treats configured root input classes as Psalm taint sources
 *
 * Only Ray\InputQuery\Attribute\Input constructor parameters on configured root
 * input classes are tainted. Reassigned downstream input classes preserve their
 * existing taint flow instead of becoming new sources.
 */
final class InputTaintHandler implements AddTaintsInterface
{
    private const string INPUT_FQCN = 'Ray\\InputQuery\\Attribute\\Input';

    /** @var array<lowercase-string, true> */
    private static array $sourceClasses = [];

    /** @param list<string> $sourceClasses */
    public static function configure(array $sourceClasses): void
    {
        self::$sourceClasses = [];

        foreach ($sourceClasses as $sourceClass) {
            $sourceClass = ltrim($sourceClass, '\\');
            self::$sourceClasses[strtolower($sourceClass)] = true;
        }
    }

    /**
     * Called to see what taints should be added
     *
     * @return list<string>
     */
    #[Override]
    public static function addTaints(AddRemoveTaintsEvent $event): array
    {
        $expr = $event->getExpr();

        if ($expr instanceof Node\Expr\Variable && self::isInputConstructorVariable($expr, $event)) {
            return TaintKindGroup::ALL_INPUT;
        }

        if ($expr instanceof Node\Expr\PropertyFetch && self::isInputPromotedPropertyFetch($expr, $event)) {
            return TaintKindGroup::ALL_INPUT;
        }

        return [];
    }

    private static function isInputConstructorVariable(Node\Expr\Variable $expr, AddRemoveTaintsEvent $event): bool
    {
        if (! is_string($expr->name)) {
            return false;
        }

        $className = $event->getStatementsSource()->getFQCLN();
        if ($className === null) {
            return false;
        }

        if ($event->getContext()->calling_method_id !== strtolower($className . '::__construct')) {
            return false;
        }

        if (! self::isSourceClass($className)) {
            return false;
        }

        return self::constructorParameterHasInput($event->getCodebase(), $className, $expr->name);
    }

    private static function isInputPromotedPropertyFetch(
        Node\Expr\PropertyFetch $expr,
        AddRemoveTaintsEvent $event,
    ): bool {
        if (! $expr->name instanceof Node\Identifier) {
            return false;
        }

        if (! ($expr->var instanceof Node\Expr\Variable && $expr->var->name === 'this')) {
            $exprType = $event->getStatementsSource()->getNodeTypeProvider()->getType($expr);
            if ($exprType !== null && $exprType->parent_nodes !== []) {
                return false;
            }
        }

        $propertyName = $expr->name->toString();
        foreach (self::propertyFetchClassNames($expr, $event) as $className) {
            if (self::promotedPropertyHasInput($event->getCodebase(), $className, $propertyName)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private static function propertyFetchClassNames(Node\Expr\PropertyFetch $expr, AddRemoveTaintsEvent $event): array
    {
        $classNames = [];

        if ($expr->var instanceof Node\Expr\Variable && $expr->var->name === 'this') {
            $className = $event->getStatementsSource()->getFQCLN();
            if ($className !== null) {
                $classNames[] = $className;
            }
        }

        $type = $event->getStatementsSource()->getNodeTypeProvider()->getType($expr->var);
        if ($type === null) {
            return array_values(array_unique($classNames));
        }

        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TNamedObject) {
                $classNames[] = $atomic->value;
            }
        }

        return array_values(array_unique($classNames));
    }

    private static function promotedPropertyHasInput(Codebase $codebase, string $className, string $propertyName): bool
    {
        $classStorage = self::declaringPropertyClassStorage($codebase, $className, $propertyName);
        if ($classStorage === null) {
            return false;
        }

        if (! self::isSourceClass($classStorage->name)) {
            return false;
        }

        return ($classStorage->properties[$propertyName] ?? null)?->is_promoted === true
            && self::constructorParameterHasInput($codebase, $classStorage->name, $propertyName);
    }

    private static function isSourceClass(string $className): bool
    {
        return isset(self::$sourceClasses[strtolower(ltrim($className, '\\'))]);
    }

    /** @psalm-suppress InternalMethod */
    private static function constructorParameterHasInput(Codebase $codebase, string $className, string $paramName): bool
    {
        $classStorage = $codebase->classlikes->getStorageFor($className);
        if ($classStorage === null) {
            return false;
        }

        $ctor = $classStorage->methods['__construct'] ?? null;
        if ($ctor === null) {
            return false;
        }

        foreach ($ctor->params as $param) {
            if ($param->name === $paramName && self::hasInputAttribute($param->attributes)) {
                return true;
            }
        }

        return false;
    }

    /** @psalm-suppress InternalMethod */
    private static function declaringPropertyClassStorage(
        Codebase $codebase,
        string $className,
        string $propertyName,
    ): ClassLikeStorage|null {
        $classStorage = $codebase->classlikes->getStorageFor($className);
        if ($classStorage === null) {
            return null;
        }

        $declaringPropertyId = $classStorage->declaring_property_ids[$propertyName] ?? $className;
        $declaringClass = self::declaringClassName($declaringPropertyId);

        return $codebase->classlikes->getStorageFor($declaringClass);
    }

    private static function declaringClassName(string $declaringPropertyId): string
    {
        if (! str_contains($declaringPropertyId, '::$')) {
            return ltrim($declaringPropertyId, '\\');
        }

        return ltrim(explode('::$', $declaringPropertyId, 2)[0], '\\');
    }

    /** @param list<AttributeStorage> $attributes */
    private static function hasInputAttribute(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if ($attribute->fq_class_name === self::INPUT_FQCN) {
                return true;
            }
        }

        return false;
    }
}
