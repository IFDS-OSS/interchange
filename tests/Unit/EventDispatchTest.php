<?php

use Ifds\HttpAdapter\Events\CircuitStateChanged;
use Ifds\HttpAdapter\Events\RequestFailed;
use Ifds\HttpAdapter\Events\RequestSending;
use Ifds\HttpAdapter\Events\ResponseReceived;
use Ifds\HttpAdapter\Support\CircuitState;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

it('dispatches RequestSending and ResponseReceived on a successful live call', function () {
    Event::fake([RequestSending::class, ResponseReceived::class]);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    fakeDriver()->ping()->send();

    Event::assertDispatched(RequestSending::class, fn (RequestSending $e) => $e->driver === 'fake'
        && $e->endpoint === 'ping'
        && $e->method === 'GET'
        && $e->url === 'https://fake.test/ping');

    Event::assertDispatched(ResponseReceived::class, fn (ResponseReceived $e) => $e->driver === 'fake'
        && $e->endpoint === 'ping'
        && $e->mocked === false
        && $e->response->status() === 200);
});

it('marks ResponseReceived as mocked when the mock path is taken', function () {
    $this->configureFakeDriver(['mock_enabled' => true]);
    Event::fake([ResponseReceived::class]);

    fakeDriver()->echoPayload(['a' => 1])->send();

    Event::assertDispatched(ResponseReceived::class, fn (ResponseReceived $e) => $e->mocked === true);
});

it('dispatches RequestFailed with a server-error reason on a 5xx', function () {
    Event::fake([RequestFailed::class]);
    Http::fake(['*' => Http::response(['error' => true], 500)]);

    try {
        fakeDriver()->unstableGet()->send();
    } catch (RequestException) {
    }

    Event::assertDispatched(RequestFailed::class, fn (RequestFailed $e) => $e->endpoint === 'unstable_get'
        && $e->reason->value === 'server_error');
});

it('dispatches CircuitStateChanged when the breaker trips', function () {
    $this->configureFakeDriver([
        'circuit_breaker' => ['enabled' => true, 'failure_threshold' => 2, 'cooldown_seconds' => 30],
    ]);
    Event::fake([CircuitStateChanged::class]);
    Http::fake(['*' => Http::response(['error' => true], 500)]);

    foreach (range(1, 2) as $i) {
        try {
            fakeDriver()->unstableGet()->send();
        } catch (RequestException) {
        }
    }

    Event::assertDispatched(CircuitStateChanged::class, fn (CircuitStateChanged $e) => $e->driver === 'fake'
        && $e->from === CircuitState::Closed
        && $e->to === CircuitState::Open);
});
