<?php

use Illuminate\Support\Facades\Http;

it('returns the mock response without hitting the network when mocking is enabled', function () {
    $this->configureFakeDriver(['mock_enabled' => true]);
    Http::fake();

    $response = fakeDriver()->echoPayload(['name' => 'widget'])->send();

    expect($response->json())->toBe([
        'echoed' => ['name' => 'widget'],
        'mocked' => true,
    ]);

    Http::assertNothingSent();
});

it('dispatches a live request when mocking is disabled', function () {
    Http::fake(['*' => Http::response(['echoed' => 'from-server'])]);

    $response = fakeDriver()->echoPayload(['name' => 'widget'])->send();

    expect($response->json())->toBe(['echoed' => 'from-server']);

    Http::assertSentCount(1);
});

it('does not mock an endpoint that has no mock defined even when mocking is enabled', function () {
    $this->configureFakeDriver(['mock_enabled' => true]);
    Http::fake(['*' => Http::response(['pong' => true])]);

    $response = fakeDriver()->ping()->send();

    expect($response->json())->toBe(['pong' => true]);

    Http::assertSentCount(1);
});
