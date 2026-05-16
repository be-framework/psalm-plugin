<?php

declare(strict_types=1);

namespace Be\PsalmPlugin\Tests\Fixture\Valid;

use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

interface StorageInterface
{
}

final readonly class GoodBeing
{
    public function __construct(
        #[Input]
        public string $recordedAt,
        #[Inject]
        public StorageInterface $storage,
    ) {
    }
}
