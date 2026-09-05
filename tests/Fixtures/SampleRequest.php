<?php

namespace Ifds\HttpAdapter\Tests\Fixtures;

use Ifds\HttpAdapter\Data\AbstractAdapterRequest;

class SampleRequest extends AbstractAdapterRequest
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?int $orderCount = null,
    ) {}
}
