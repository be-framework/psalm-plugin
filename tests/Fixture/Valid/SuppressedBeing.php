<?php

declare(strict_types=1);

namespace Be\PsalmPlugin\Tests\Fixture\Valid;

use Ray\InputQuery\Attribute\Input;

interface SuppressedStorageInterface
{
}

/** @psalm-suppress MissingBeingParameterAttribute */
final readonly class SuppressedBeing
{
    public function __construct(
        #[Input]
        public string $recordedAt,
        public SuppressedStorageInterface $storage,
    ) {
    }
}
