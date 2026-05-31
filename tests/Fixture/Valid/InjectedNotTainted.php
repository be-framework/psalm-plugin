<?php

declare(strict_types=1);

namespace Be\PsalmPlugin\Tests\Fixture\Valid;

use Ray\Di\Di\Inject;

final readonly class InjectedNotTainted
{
    public function __construct(
        #[Inject]
        public string $trustedHtml,
    ) {
    }

    public function render(): void
    {
        echo $this->trustedHtml;
    }
}
