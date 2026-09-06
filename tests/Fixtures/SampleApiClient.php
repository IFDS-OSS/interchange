<?php

namespace Ifds\HttpAdapter\Tests\Fixtures;

use Ifds\HttpAdapter\Attributes\Driver;
use Ifds\HttpAdapter\Client\AbstractApiClient;

/**
 * A client for JSONPlaceholder (https://jsonplaceholder.typicode.com), the
 * public sample REST API. The suite never reaches the network — every test
 * stubs the transport with Http::fake() — but modelling a real API keeps the
 * fixture honest about the endpoint shapes the package has to support.
 */
#[Driver('sample')]
class SampleApiClient extends AbstractApiClient
{
    /**
     * @return array<string, array<string, mixed>>
     */
    protected function defineEndpoints(): array
    {
        return [
            // Idempotent, no path params.
            'posts' => [
                'method' => 'GET',
                'path' => '/posts',
            ],

            // Idempotent, with a path parameter to interpolate.
            'show_post' => [
                'method' => 'GET',
                'path' => '/posts/{id}',
            ],

            // Non-idempotent, and carries a mock so the mock/live switch and
            // the retry opt-in can both be exercised against it.
            'create_post' => [
                'method' => 'POST',
                'path' => '/posts',
                'mock' => fn (array $payload, array $headers) => ['id' => 101] + $payload,
            ],
        ];
    }
}
