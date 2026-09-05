<?php

namespace Ifds\HttpAdapter\Tests\Fixtures;

use Ifds\HttpAdapter\Attributes\Driver;
use Ifds\HttpAdapter\Client\AbstractApiClient;

#[Driver('fake')]
class FakeApiClient extends AbstractApiClient
{
    protected function defineEndpoints(): array
    {
        return [
            'ping' => [
                'method' => 'GET',
                'path' => '/ping',
            ],
            'echo_payload' => [
                'method' => 'POST',
                'path' => '/echo',
                'mock' => fn (array $payload, array $headers) => [
                    'echoed' => $payload,
                    'mocked' => true,
                ],
            ],
            'show_user' => [
                'method' => 'GET',
                'path' => '/users/{id}',
            ],
            'unstable_get' => [
                'method' => 'GET',
                'path' => '/unstable',
            ],
            'unstable_post' => [
                'method' => 'POST',
                'path' => '/unstable',
            ],
        ];
    }
}
