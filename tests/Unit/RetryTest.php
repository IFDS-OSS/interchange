<?php

use Ifds\HttpAdapter\Events\RequestRetrying;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->configureSampleDriver(['retry' => ['times' => 3, 'backoff_ms' => 0]]);
});

it('retries an idempotent GET on a 5xx and returns the eventual success', function () {
    Event::fake([RequestRetrying::class]);

    Http::fake(['*' => Http::sequence()
        ->push(['error' => true], 500)
        ->push(['ok' => true], 200)]);

    $response = sampleDriver()->posts()->send();

    expect($response->successful())->toBeTrue()
        ->and($response->json())->toBe(['ok' => true]);

    Http::assertSentCount(2);
    Event::assertDispatchedTimes(RequestRetrying::class, 1);
});

it('does not retry a non-idempotent POST by default', function () {
    Event::fake([RequestRetrying::class]);

    Http::fake(['*' => Http::sequence()
        ->push(['error' => true], 500)
        ->push(['ok' => true], 200)]);

    expect(fn () => sampleDriver()->createPost(['title' => 'widget'])->send())
        ->toThrow(RequestException::class);

    Http::assertSentCount(1);
    Event::assertDispatchedTimes(RequestRetrying::class, 0);
});

it('retries a POST when the per-call override opts in', function () {
    Event::fake([RequestRetrying::class]);

    Http::fake(['*' => Http::sequence()
        ->push(['error' => true], 500)
        ->push(['ok' => true], 200)]);

    $response = sampleDriver()->createPost(['title' => 'widget'])
        ->withRetry(times: 3, backoffMs: 0)
        ->send();

    expect($response->successful())->toBeTrue();

    Http::assertSentCount(2);
    Event::assertDispatchedTimes(RequestRetrying::class, 1);
});

it('does not retry when no retry policy is configured', function () {
    // Hard-reset the policy the beforeEach installed (array_replace_recursive
    // can't clear a key with an empty array).
    config()->set('http-adapter.drivers.sample.retry', []);
    Event::fake([RequestRetrying::class]);

    Http::fake(['*' => Http::sequence()
        ->push(['error' => true], 500)
        ->push(['ok' => true], 200)]);

    expect(fn () => sampleDriver()->posts()->send())
        ->toThrow(RequestException::class);

    Http::assertSentCount(1);
    Event::assertDispatchedTimes(RequestRetrying::class, 0);
});
