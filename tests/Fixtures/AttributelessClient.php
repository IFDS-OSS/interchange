<?php

namespace Ifds\HttpAdapter\Tests\Fixtures;

use Ifds\HttpAdapter\Client\AbstractApiClient;

/**
 * A client deliberately missing the #[Driver] attribute, used to prove the
 * registrar rejects it at resolve time.
 */
class AttributelessClient extends AbstractApiClient
{
    protected function defineEndpoints(): array
    {
        return [
            'posts' => ['method' => 'GET', 'path' => '/posts'],
        ];
    }
}
