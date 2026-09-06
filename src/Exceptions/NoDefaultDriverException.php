<?php

namespace Ifds\Interchange\Exceptions;

class NoDefaultDriverException extends InterchangeException
{
    public static function make(): self
    {
        return new self('No default interchange driver is configured; call a driver explicitly, e.g. Interchange::driver("name").');
    }
}
