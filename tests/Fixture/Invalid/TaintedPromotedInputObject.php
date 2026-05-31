<?php

declare(strict_types=1);

namespace Be\PsalmPlugin\Tests\Fixture\Invalid;

use Ray\InputQuery\Attribute\Input;

final readonly class TaintedPromotedInputObject
{
    public function __construct(
        #[Input]
        public string $name,
    ) {
    }
}

function renderPromotedInputObject(TaintedPromotedInputObject $being): void
{
    echo $being->name;
}
