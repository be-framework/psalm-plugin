<?php

declare(strict_types=1);

namespace Be\PsalmPlugin;

use Be\PsalmPlugin\Handler\BeingParameterAttributeHandler;
use Be\PsalmPlugin\Handler\ValidateThrowHandler;
use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

/**
 * Psalm plugin entry point for Be Framework
 *
 * Registers static-analysis counterparts for three runtime errors:
 *
 * 1. {@see \Be\PsalmPlugin\Issue\MissingBeingParameterAttribute}
 *    Detects Being constructor parameters missing both #[Input] and #[Inject].
 *
 * 2. {@see \Be\PsalmPlugin\Issue\ConflictingBeingParameterAttribute}
 *    Detects Being constructor parameters with both #[Input] and #[Inject].
 *
 * 3. {@see \Be\PsalmPlugin\Issue\InvalidValidateException}
 *    Detects #[Validate] methods that throw exceptions not extending DomainException.
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
        require_once __DIR__ . '/Handler/ValidateThrowHandler.php';

        $registration->registerHooksFromClass(BeingParameterAttributeHandler::class);
        $registration->registerHooksFromClass(ValidateThrowHandler::class);
    }
}
