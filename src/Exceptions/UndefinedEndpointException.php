<?php

namespace Ifds\HttpAdapter\Exceptions;

class UndefinedEndpointException extends HttpAdapterException
{
    public static function forKey(string $endpointKey): self
    {
        return new self("Endpoint [{$endpointKey}] is not defined.");
    }
}
