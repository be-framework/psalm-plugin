<?php

declare(strict_types=1);

namespace Be\PsalmPlugin\Tests\Fixture\Invalid;

use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

interface ConflictingStorageInterface
{
}

final readonly class BadBeingConflictingAttributes
{
    public function __construct(
        #[Input]
        public string $recordedAt,
        #[Input]
        #[Inject]
        public ConflictingStorageInterface $storage,
    ) {
    }
}
