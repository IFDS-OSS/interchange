<?php

namespace Ifds\HttpAdapter\Exceptions;

class NoDefaultDriverException extends HttpAdapterException
{
    public static function make(): self
    {
        return new self('No default http-adapter driver is configured; call a driver explicitly, e.g. HttpAdapter::driver("name").');
    }
}
