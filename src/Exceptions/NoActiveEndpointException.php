<?php

namespace Ifds\HttpAdapter\Exceptions;

class NoActiveEndpointException extends HttpAdapterException
{
    public static function make(): self
    {
        return new self('No endpoint configured for this request. Call an endpoint method before send().');
    }
}
