<?php

namespace Ifds\HttpAdapter\Exceptions;

class CircuitOpenException extends HttpAdapterException
{
    public static function forDriver(string $driver): self
    {
        return new self("Circuit is open for driver [{$driver}]; refusing to dispatch the request.");
    }
}
