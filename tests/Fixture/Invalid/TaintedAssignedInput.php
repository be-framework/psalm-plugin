<?php

declare(strict_types=1);

// phpcs:disable SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion.RequiredConstructorPropertyPromotion

namespace Be\PsalmPlugin\Tests\Fixture\Invalid;

use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

interface AssignedInputLoggerInterface
{
}

final class TaintedAssignedInput
{
    private string $name;

    public function __construct(
        #[Input]
        string $name,
        #[Inject]
        private AssignedInputLoggerInterface $logger,
    ) {
        $this->name = $name;
    }

    public function render(): void
    {
        echo $this->name;
    }
}
