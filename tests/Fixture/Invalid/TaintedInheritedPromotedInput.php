<?php

declare(strict_types=1);

namespace Be\PsalmPlugin\Tests\Fixture\Invalid;

use Ray\InputQuery\Attribute\Input;

class TaintedInheritedPromotedInput
{
    public function __construct(
        #[Input]
        public string $name,
    ) {
    }
}

final class TaintedInheritedPromotedChildInput extends TaintedInheritedPromotedInput
{
}

function renderInheritedPromotedInputObject(TaintedInheritedPromotedChildInput $being): void
{
    echo $being->name;
}
