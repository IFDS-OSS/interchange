<?php

namespace Ifds\Interchange\Tests\Fixtures;

use Ifds\Interchange\Data\AbstractAdapterRequest;

class SampleRequest extends AbstractAdapterRequest
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?int $orderCount = null,
    ) {}
}
