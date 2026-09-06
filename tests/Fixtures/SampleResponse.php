<?php

namespace Ifds\Interchange\Tests\Fixtures;

use Ifds\Interchange\Data\AbstractResponse;

class SampleResponse extends AbstractResponse
{
    public ?int $orderId = null;

    public ?string $status = null;
}
