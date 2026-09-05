<?php

namespace Ifds\HttpAdapter\Tests\Fixtures;

use Ifds\HttpAdapter\Data\AbstractAdapterResponse;

class SampleResponse extends AbstractAdapterResponse
{
    public ?int $orderId = null;

    public ?string $status = null;
}
