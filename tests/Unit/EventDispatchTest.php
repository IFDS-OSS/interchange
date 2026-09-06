<?php

use Ifds\HttpAdapter\Enums\CircuitState;
use Ifds\HttpAdapter\Events\CircuitStateChanged;
use Ifds\HttpAdapter\Events\RequestFailed;
use Ifds\HttpAdapter\Events\RequestSending;
use Ifds\HttpAdapter\Events\ResponseReceived;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

it('dispatches RequestSending and ResponseReceived on a successful live call', function () {
    Event::fake([RequestSending::class, ResponseReceived::class]);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    sampleDriver()->posts()->send();

    Event::assertDispatched(RequestSending::class, fn (RequestSending $e) => $e->driver === 'sample'
        && $e->endpoint === 'posts'
        && $e->method === 'GET'
        && $e->url === 'https://jsonplaceholder.typicode.com/posts');

    Event::assertDispatched(ResponseReceived::class, fn (ResponseReceived $e) => $e->driver === 'sample'
        && $e->endpoint === 'posts'
        && $e->mocked === false
        && $e->response->status() === 200);
});

it('marks ResponseReceived as mocked when the mock path is taken', function () {
    $this->configureSampleDriver(['mock_enabled' => true]);
    Event::fake([ResponseReceived::class]);

    sampleDriver()->createPost(['a' => 1])->send();

    Event::assertDispatched(ResponseReceived::class, fn (ResponseReceived $e) => $e->mocked === true);
});

it('dispatches RequestFailed with a server-error reason on a 5xx', function () {
    Event::fake([RequestFailed::class]);
    Http::fake(['*' => Http::response(['error' => true], 500)]);

    try {
        sampleDriver()->posts()->send();
    } catch (RequestException) {
    }

    Event::assertDispatched(RequestFailed::class, fn (RequestFailed $e) => $e->endpoint === 'posts'
        && $e->reason->value === 'server_error');
});

it('dispatches CircuitStateChanged when the breaker trips', function () {
    $this->configureSampleDriver([
        'circuit_breaker' => ['enabled' => true, 'failure_threshold' => 2, 'cooldown_seconds' => 30],
    ]);
    Event::fake([CircuitStateChanged::class]);
    Http::fake(['*' => Http::response(['error' => true], 500)]);

    foreach (range(1, 2) as $i) {
        try {
            sampleDriver()->posts()->send();
        } catch (RequestException) {
        }
    }

    Event::assertDispatched(CircuitStateChanged::class, fn (CircuitStateChanged $e) => $e->driver === 'sample'
        && $e->from === CircuitState::Closed
        && $e->to === CircuitState::Open);
});
