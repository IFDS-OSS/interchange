<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | The log channel the default LogHttpAdapterActivity listener writes to.
    | Leave null to fall back to the application's default log channel.
    |
    */
    'log_channel' => env('HTTP_ADAPTER_LOG_CHANNEL'),

    'logging' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | The cache store circuit-breaker state is persisted to. Leave null to
    | fall back to the application's default cache store.
    |
    */
    'circuit_breaker' => [
        'store' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    |
    | Register your outbound HTTP drivers here. Each entry maps a driver name
    | to the client class implementing it (which must declare a matching
    | #[Driver('name')] attribute) plus its own configuration.
    |
    | 'postex' => [
    |     'client' => \App\Adapters\PostexClient::class,
    |     'base_url' => env('POSTEX_API_URL'),
    |     'stage_url' => 'https://stage.postex.ir',
    |     'timeout' => 30,
    |     'mock_enabled' => env('POSTEX_MOCK', false),
    |     'retry' => ['times' => 3, 'backoff_ms' => 200],
    |     'circuit_breaker' => ['enabled' => true, 'failure_threshold' => 5, 'cooldown_seconds' => 30],
    |     'extra' => ['api_key' => env('POSTEX_API_KEY')],
    | ],
    |
    */
    'drivers' => [],

];
