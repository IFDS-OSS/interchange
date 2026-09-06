<?php

namespace Ifds\Interchange\Events;

use Throwable;

class RequestRetrying
{
    public function __construct(
        public readonly string $driver,
        public readonly string $endpoint,
        public readonly Throwable $exception,
    ) {}
}
