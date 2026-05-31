<?php

declare(strict_types=1);

namespace Be\PsalmPlugin;

use Be\PsalmPlugin\Handler\BeingParameterAttributeHandler;
use Be\PsalmPlugin\Handler\InputTaintHandler;
use Be\PsalmPlugin\Handler\ValidateThrowHandler;
use DOMElement;
use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use function dom_import_simplexml;
use function ltrim;

/**
 * Psalm plugin entry point for Be Framework
 *
 * Registers static-analysis counterparts for three runtime errors and one
 * taint-analysis source:
 *
 * 1. {@see \Be\PsalmPlugin\Issue\MissingBeingParameterAttribute}
 *    Detects Being constructor parameters missing both #[Input] and #[Inject].
 *
 * 2. {@see \Be\PsalmPlugin\Issue\ConflictingBeingParameterAttribute}
 *    Detects Being constructor parameters with both #[Input] and #[Inject].
 *
 * 3. {@see \Be\PsalmPlugin\Issue\InvalidValidateException}
 *    Detects #[Validate] methods that throw exceptions not extending DomainException.
 *
 * 4. {@see \Be\PsalmPlugin\Handler\InputTaintHandler}
 *    Treats configured root input classes as user-controlled input sources.
 */
final class Plugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(RegistrationInterface $registration, SimpleXMLElement|null $config = null): void
    {
        // Psalm checks class_exists($handler, false) without autoload, so handler
        // files must be loaded explicitly before registerHooksFromClass().
        require_once __DIR__ . '/Internal/AttributeNodeUtil.php';
        require_once __DIR__ . '/Internal/ThrowCollectorVisitor.php';
        require_once __DIR__ . '/Issue/MissingBeingParameterAttribute.php';
        require_once __DIR__ . '/Issue/ConflictingBeingParameterAttribute.php';
        require_once __DIR__ . '/Issue/InvalidValidateException.php';
        require_once __DIR__ . '/Handler/BeingParameterAttributeHandler.php';
        require_once __DIR__ . '/Handler/InputTaintHandler.php';
        require_once __DIR__ . '/Handler/ValidateThrowHandler.php';

        InputTaintHandler::configure(self::inputTaintSourceClasses($config));

        $registration->registerHooksFromClass(BeingParameterAttributeHandler::class);
        $registration->registerHooksFromClass(InputTaintHandler::class);
        $registration->registerHooksFromClass(ValidateThrowHandler::class);
    }

    /** @return list<string> */
    private static function inputTaintSourceClasses(SimpleXMLElement|null $config): array
    {
        if ($config === null) {
            return [];
        }

        $node = dom_import_simplexml($config);
        if (! $node instanceof DOMElement) {
            return [];
        }

        $sourceClasses = self::sourceClassesFromConfigNode($node);
        $sourceNodes = $node->getElementsByTagName('inputTaintSources');
        for ($i = 0; $i < $sourceNodes->length; $i++) {
            $sourceNode = $sourceNodes->item($i);
            if (! $sourceNode instanceof DOMElement) {
                continue;
            }

            foreach (self::sourceClassesFromConfigNode($sourceNode) as $sourceClass) {
                $sourceClasses[] = $sourceClass;
            }
        }

        return $sourceClasses;
    }

    /** @return list<string> */
    private static function sourceClassesFromConfigNode(DOMElement $node): array
    {
        if ($node->localName !== 'inputTaintSources') {
            return [];
        }

        $sourceClasses = [];
        $classNodes = $node->getElementsByTagName('class');
        for ($i = 0; $i < $classNodes->length; $i++) {
            $class = $classNodes->item($i);
            if (! $class instanceof DOMElement) {
                continue;
            }

            $sourceClass = ltrim($class->getAttribute('name'), '\\');
            if ($sourceClass !== '') {
                $sourceClasses[] = $sourceClass;
            }
        }

        return $sourceClasses;
    }
}
