<?php

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Ifds\HttpAdapter\Tests\Fixtures\SampleResponse;
use Illuminate\Http\Client\Response;

function httpResponse(array $data, int $status = 200): Response
{
    return new Response(new GuzzleResponse($status, [], json_encode($data)));
}

it('maps camel-cased JSON keys onto matching typed properties', function () {
    $dto = SampleResponse::fromHttpResponse(httpResponse([
        'order_id' => 5,
        'status' => 'ok',
    ]));

    expect($dto->orderId)->toBe(5)
        ->and($dto->status)->toBe('ok');
});

it('captures unmatched JSON keys as dynamic overrides', function () {
    $dto = SampleResponse::fromHttpResponse(httpResponse([
        'order_id' => 5,
        'tracking_code' => 'ABC123',
    ]));

    // camelCased and reachable through the magic accessor.
    expect($dto->trackingCode)->toBe('ABC123');
});

it('exposes passthrough helpers to the underlying HTTP response', function () {
    $dto = SampleResponse::fromHttpResponse(httpResponse(['status' => 'ok'], 200));

    expect($dto->successful())->toBeTrue()
        ->and($dto->failed())->toBeFalse()
        ->and($dto->status())->toBe(200)
        ->and($dto->json())->toBe(['status' => 'ok']);
});

it('reports a failed response for a 4xx/5xx status', function () {
    $dto = SampleResponse::fromHttpResponse(httpResponse(['error' => true], 500));

    expect($dto->successful())->toBeFalse()
        ->and($dto->failed())->toBeTrue()
        ->and($dto->status())->toBe(500);
});

it('make() builds an empty instance without an HTTP response', function () {
    $dto = SampleResponse::make();

    expect($dto->http())->toBeNull()
        ->and($dto->status())->toBe(0)
        ->and($dto->successful())->toBeFalse();
});
