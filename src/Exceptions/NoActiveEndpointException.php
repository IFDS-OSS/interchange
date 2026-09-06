<?php

namespace Ifds\Interchange\Exceptions;

class NoActiveEndpointException extends InterchangeException
{
    public static function make(): self
    {
        return new self('No endpoint configured for this request. Call an endpoint method before send().');
    }
}
