<?php

namespace Workbench\App\Adapters;

use Ifds\HttpAdapter\Attributes\Driver;
use Ifds\HttpAdapter\Client\AbstractApiClient;

/**
 * A demo driver used by the workbench app to exercise the package inside a
 * real, booted Laravel application.
 */
#[Driver('demo')]
class DemoClient extends AbstractApiClient
{
    /**
     * @return array<string, array<string, mixed>>
     */
    protected function defineEndpoints(): array
    {
        return [
            'status' => [
                'method' => 'GET',
                'path' => '/status/{code}',
            ],
        ];
    }
}
