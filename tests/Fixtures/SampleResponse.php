<?php

namespace Ifds\Interchange\Tests\Fixtures;

use Ifds\Interchange\Data\AbstractAdapterResponse;

class SampleResponse extends AbstractAdapterResponse
{
    public ?int $orderId = null;

    public ?string $status = null;
}
