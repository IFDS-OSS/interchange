<?php

namespace Workbench\App\Adapters;

use Ifds\HttpAdapter\Attributes\Driver;
use Ifds\HttpAdapter\Client\AbstractApiClient;

/**
 * A client for JSONPlaceholder (https://jsonplaceholder.typicode.com), the free
 * public sample REST API. The workbench app uses it to exercise the package
 * inside a real, booted Laravel application against a real endpoint.
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
            'posts' => [
                'method' => 'GET',
                'path' => '/posts',
            ],
            'show_post' => [
                'method' => 'GET',
                'path' => '/posts/{id}',
            ],
            'comments_for_post' => [
                'method' => 'GET',
                'path' => '/posts/{id}/comments',
            ],
            'create_post' => [
                'method' => 'POST',
                'path' => '/posts',
                // Served instead of a live call when 'mock_enabled' is true.
                'mock' => fn (array $payload, array $headers) => ['id' => 101] + $payload,
            ],
        ];
    }

    /**
     * A typed convenience method over the generic builder.
     *
     * @return array<int, array<string, mixed>>
     */
    public function commentsFor(int $postId): array
    {
        return $this->commentsForPost()
            ->withPathParam('id', $postId)
            ->send()
            ->json();
    }
}
