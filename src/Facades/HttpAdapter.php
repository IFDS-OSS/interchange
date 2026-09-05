<?php

namespace Ifds\HttpAdapter\Facades;

use Ifds\HttpAdapter\AdapterManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ifds\HttpAdapter\Client\AbstractApiClient driver(string $driver)
 *
 * @see AdapterManager
 */
class HttpAdapter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'http-adapter';
    }
}
