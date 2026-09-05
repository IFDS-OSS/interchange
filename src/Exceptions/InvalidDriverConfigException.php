<?php

namespace Ifds\HttpAdapter\Exceptions;

class InvalidDriverConfigException extends HttpAdapterException
{
    public static function missingClient(string $driver): self
    {
        return new self("Driver [{$driver}] is missing a 'client' entry in config('http-adapter.drivers.{$driver}').");
    }

    public static function missingBaseUrl(string $driver): self
    {
        return new self("Driver [{$driver}] has neither 'base_url' nor 'stage_url' configured.");
    }

    public static function attributeMismatch(string $driver, string $class, string $attributeName): self
    {
        return new self(
            "Driver [{$driver}] is configured to use [{$class}], but that class declares #[Driver('{$attributeName}')] — ".
            'the config key and the class attribute must match.'
        );
    }

    public static function missingAttribute(string $driver, string $class): self
    {
        return new self("Driver client class [{$class}] for [{$driver}] must declare a #[Driver('{$driver}')] attribute.");
    }
}
