<?php

namespace Ifds\HttpAdapter;

use Ifds\HttpAdapter\Exceptions\NoDefaultDriverException;
use Illuminate\Support\Manager;

class AdapterManager extends Manager
{
    public function getDefaultDriver()
    {
        throw NoDefaultDriverException::make();
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function __call($method, $parameters)
    {
        return $this->driver($method);
    }
}
