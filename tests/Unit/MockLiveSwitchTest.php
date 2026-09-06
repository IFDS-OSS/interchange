<?php

use Illuminate\Support\Facades\Http;

it('returns the mock response without hitting the network when mocking is enabled', function () {
    $this->configureSampleDriver(['mock_enabled' => true]);
    Http::fake();

    $response = sampleDriver()->createPost(['title' => 'widget'])->send();

    expect($response->json())->toBe([
        'id' => 101,
        'title' => 'widget',
    ]);

    Http::assertNothingSent();
});

it('dispatches a live request when mocking is disabled', function () {
    Http::fake(['*' => Http::response(['id' => 1, 'title' => 'from-server'])]);

    $response = sampleDriver()->createPost(['title' => 'widget'])->send();

    expect($response->json())->toBe(['id' => 1, 'title' => 'from-server']);

    Http::assertSentCount(1);
});

it('does not mock an endpoint that has no mock defined even when mocking is enabled', function () {
    $this->configureSampleDriver(['mock_enabled' => true]);
    Http::fake(['*' => Http::response([['id' => 1]])]);

    $response = sampleDriver()->posts()->send();

    expect($response->json())->toBe([['id' => 1]]);

    Http::assertSentCount(1);
});
