<?php

declare(strict_types=1);

namespace Be\PsalmPlugin\Demo;

use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function htmlspecialchars;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

interface TemplateRendererInterface
{
    public function renderTrusted(string $template): string;
}

final readonly class GreetingInput
{
    public function __construct(
        #[Input]
        public string $name,
        #[Inject]
        public TemplateRendererInterface $renderer,
        #[Inject]
        public string $trustedTemplate,
    ) {
    }

    public function unsafeOutput(): void
    {
        echo $this->name; // TaintedHtml: #[Input] is user-controlled.
    }

    public function sanitizedInput(): SanitizedGreetingInput
    {
        return new SanitizedGreetingInput(
            htmlspecialchars($this->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    public function injectedOutput(): void
    {
        echo $this->trustedTemplate; // No taint: #[Inject] is not a user input source.
    }

    public function renderedInjectedOutput(): void
    {
        echo $this->renderer->renderTrusted($this->trustedTemplate);
    }
}

final readonly class SanitizedGreetingInput
{
    public function __construct(
        #[Input]
        public string $name,
    ) {
    }

    public function output(): void
    {
        echo $this->name; // No taint: this downstream Input class is not a source.
    }
}
