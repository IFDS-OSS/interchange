<?php

use Ifds\HttpAdapter\Exceptions\CircuitOpenException;
use Ifds\HttpAdapter\Support\CircuitBreaker;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->configureFakeDriver([
        'circuit_breaker' => ['enabled' => true, 'failure_threshold' => 3, 'cooldown_seconds' => 30],
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

function failOnce(): void
{
    try {
        fakeDriver()->unstableGet()->send();
    } catch (RequestException) {
        // swallow; the breaker records the failure
    }
}

it('trips open after the failure threshold and short-circuits further calls', function () {
    Http::fake(['*' => Http::response(['error' => true], 500)]);

    failOnce();
    failOnce();
    failOnce();

    Http::assertSentCount(3);

    expect(fn () => fakeDriver()->unstableGet()->send())
        ->toThrow(CircuitOpenException::class);

    // The short-circuited call never reached the network.
    Http::assertSentCount(3);
});

it('moves to half-open after the cooldown and closes again on a successful trial', function () {
    Carbon::setTestNow('2026-01-01 12:00:00');

    // A single stub sequence: three failures trip the breaker, then the
    // half-open trial and the following call succeed. (Http::fake() called
    // twice appends stubs rather than replacing, so one sequence is required.)
    Http::fake(['*' => Http::sequence()
        ->push(['error' => true], 500)
        ->push(['error' => true], 500)
        ->push(['error' => true], 500)
        ->push(['ok' => true], 200)
        ->push(['ok' => true], 200)]);

    failOnce();
    failOnce();
    failOnce();

    expect(fn () => fakeDriver()->unstableGet()->send())->toThrow(CircuitOpenException::class);

    Carbon::setTestNow(now()->addSeconds(31));

    $trial = fakeDriver()->unstableGet()->send();
    expect($trial->successful())->toBeTrue();

    // Closed again: a subsequent call also passes through.
    $again = fakeDriver()->unstableGet()->send();
    expect($again->successful())->toBeTrue();

    // 3 trip + 1 trial + 1 follow-up; the open-state call never reached the network.
    Http::assertSentCount(5);
});

it('reopens when the half-open trial fails again', function () {
    Carbon::setTestNow('2026-01-01 12:00:00');
    Http::fake(['*' => Http::response(['error' => true], 500)]);

    failOnce();
    failOnce();
    failOnce();

    Carbon::setTestNow(now()->addSeconds(31));

    // Half-open trial fails...
    failOnce();

    // ...so the breaker is open again and short-circuits.
    expect(fn () => fakeDriver()->unstableGet()->send())
        ->toThrow(CircuitOpenException::class);
});

it('never trips when the circuit breaker is disabled', function () {
    $this->configureFakeDriver(['circuit_breaker' => ['enabled' => false]]);
    Http::fake(['*' => Http::response(['error' => true], 500)]);

    failOnce();
    failOnce();
    failOnce();
    failOnce();

    // No CircuitOpenException — every call reached the network.
    expect(fn () => fakeDriver()->unstableGet()->send())->toThrow(RequestException::class);
    Http::assertSentCount(5);
});

it('bypasses an enabled breaker for a single call via withoutCircuitBreaker()', function () {
    Http::fake(['*' => Http::response(['error' => true], 500)]);

    failOnce();
    failOnce();
    failOnce();

    // Breaker is open, but this call opts out and reaches the network (then fails normally).
    expect(fn () => fakeDriver()->unstableGet()->withoutCircuitBreaker()->send())
        ->toThrow(RequestException::class);

    Http::assertSentCount(4);
});

it('persists breaker state in the cache across separate breaker instances', function () {
    $cache = Cache::store('array');

    $first = new CircuitBreaker($cache, 'persist-check', failureThreshold: 2, cooldownSeconds: 30);
    $first->recordOutcome(false);
    $first->recordOutcome(false);

    // A brand-new instance reads the same cache-backed state.
    $second = new CircuitBreaker($cache, 'persist-check', failureThreshold: 2, cooldownSeconds: 30);

    expect($second->isOpen())->toBeTrue();
});
