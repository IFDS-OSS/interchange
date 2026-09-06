<?php

namespace Ifds\Interchange\Tests\Fixtures;

use Ifds\Interchange\Attributes\Driver;
use Ifds\Interchange\Client\AbstractApiClient;

/**
 * Registered in config only after the manager has already been resolved, to
 * prove driver resolution reads the live config rather than a boot-time copy.
 */
#[Driver('late')]
class LateSampleApiClient extends AbstractApiClient
{
    /**
     * @return array<string, array<string, mixed>>
     */
    protected function defineEndpoints(): array
    {
        return [
            'todos' => ['method' => 'GET', 'path' => '/todos'],
        ];
    }
}
