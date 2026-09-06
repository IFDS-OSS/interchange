<?php

namespace Ifds\Interchange\Exceptions;

class UndefinedEndpointException extends InterchangeException
{
    public static function forKey(string $endpointKey): self
    {
        return new self("Endpoint [{$endpointKey}] is not defined.");
    }
}
