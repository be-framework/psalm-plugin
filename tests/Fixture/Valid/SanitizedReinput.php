<?php

declare(strict_types=1);

namespace Be\PsalmPlugin\Tests\Fixture\Valid;

use Ray\InputQuery\Attribute\Input;

use function htmlspecialchars;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final readonly class RawProfileInput
{
    public function __construct(
        #[Input]
        public string $name,
    ) {
    }

    public function sanitized(): SanitizedProfileInput
    {
        return new SanitizedProfileInput(
            htmlspecialchars($this->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }
}

final readonly class SanitizedProfileInput
{
    public function __construct(
        #[Input]
        public string $name,
    ) {
    }

    public function render(): void
    {
        echo $this->name;
    }
}
