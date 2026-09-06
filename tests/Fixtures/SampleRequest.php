<?php

namespace Ifds\Interchange\Tests\Fixtures;

use Ifds\Interchange\Data\AbstractRequest;

class SampleRequest extends AbstractRequest
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?int $orderCount = null,
    ) {}
}
