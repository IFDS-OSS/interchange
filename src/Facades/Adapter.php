<?php

namespace Ifds\HttpAdapter\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ifds\HttpAdapter\Client\AbstractApiClient driver(string $driver)
 */
class Adapter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'http-adapter';
    }
}
