<?php

namespace Ifds\Interchange\Exceptions;

class CircuitOpenException extends InterchangeException
{
    public static function forDriver(string $driver): self
    {
        return new self("Circuit is open for driver [{$driver}]; refusing to dispatch the request.");
    }
}
