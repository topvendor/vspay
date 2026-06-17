<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Root URL of the VSPay merchant API, without the "/api/v1" suffix.
    | Example: https://api.example.com
    |
    */
    'base_url' => env('VSPAY_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Terminal API secret
    |--------------------------------------------------------------------------
    |
    | Plain terminal secret key sent as "Authorization: Bearer <secret>".
    | Keep this out of version control.
    |
    */
    'secret' => env('VSPAY_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook secret
    |--------------------------------------------------------------------------
    |
    | Secret used to verify the "X-Webhook-Signature" header (HMAC-SHA256 over
    | the raw request body). Issued during onboarding.
    |
    */
    'webhook_secret' => env('VSPAY_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | HTTP options
    |--------------------------------------------------------------------------
    |
    | timeout  - request timeout in seconds.
    | retries  - number of retries for network / 5xx failures (idempotent only).
    | retry_delay_ms - base delay between retries in milliseconds.
    |
    */
    'timeout' => (int) env('VSPAY_TIMEOUT', 15),
    'retries' => (int) env('VSPAY_RETRIES', 2),
    'retry_delay_ms' => (int) env('VSPAY_RETRY_DELAY_MS', 200),
];
