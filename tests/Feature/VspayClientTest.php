<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Topvendor\Vspay\Client\VspayClient;
use Topvendor\Vspay\Exceptions\GatewayException;
use Topvendor\Vspay\Exceptions\RateLimitException;
use Topvendor\Vspay\Exceptions\UnauthorizedException;
use Topvendor\Vspay\Exceptions\ValidationException;
use Topvendor\Vspay\Exceptions\VspayException;
use Topvendor\Vspay\Facades\Vspay;

function client(): VspayClient
{
    return app(VspayClient::class);
}

it('sends a bearer token and json to the correct url', function () {
    Http::fake([
        'api.example.test/api/v1/payments' => Http::response([
            'accepted' => true,
            'http_status' => 200,
            'provider_request_id' => 'req-1',
            'provider' => ['sync_status' => 'Success'],
        ], 200),
    ]);

    $response = client()->payments()->create([
        'merchant_payment_id' => 'order-1',
        'currency' => 'RUB',
        'amount' => 10000,
    ]);

    expect($response->accepted())->toBeTrue()
        ->and($response->providerRequestId())->toBe('req-1')
        ->and($response->chargeOperationUuid())->toBeNull()
        ->and($response->provider())->toBe(['sync_status' => 'Success']);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.example.test/api/v1/payments'
            && $request->hasHeader('Authorization', 'Bearer test-terminal-secret')
            && $request['merchant_payment_id'] === 'order-1';
    });
});

it('exposes charge_operation_uuid for refunds', function () {
    Http::fake([
        'api.example.test/api/v1/payments' => Http::response([
            'accepted' => true,
            'provider_request_id' => 'req-1',
            'charge_operation_uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'provider' => ['sync_status' => 'Success'],
        ], 200),
    ]);

    $charge = client()->payments()->create(['merchant_payment_id' => 'order-1']);

    expect($charge->chargeOperationUuid())->toBe('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11');
});

it('maps 401 to UnauthorizedException', function () {
    Http::fake([
        '*' => Http::response([
            'accepted' => false,
            'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing API key.'],
        ], 401),
    ]);

    client()->payments()->create([]);
})->throws(UnauthorizedException::class);

it('maps 422 to ValidationException with details', function () {
    Http::fake([
        '*' => Http::response([
            'accepted' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The amount field is required.',
                'details' => ['amount' => ['required']],
            ],
        ], 422),
    ]);

    try {
        client()->payments()->create([]);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errorCode)->toBe('VALIDATION_ERROR')
            ->and($e->details)->toBe(['amount' => ['required']])
            ->and($e->statusCode)->toBe(422);
    }
});

it('maps 429 to RateLimitException', function () {
    Http::fake([
        '*' => Http::response(['accepted' => false, 'error' => ['code' => 'RATE_LIMIT']], 429),
    ]);

    client()->gateway()->status(['order_id' => 'x']);
})->throws(RateLimitException::class);

it('maps accepted:false to GatewayException', function () {
    Http::fake([
        '*' => Http::response([
            'accepted' => false,
            'http_status' => 422,
            'error' => ['code' => 'REFUND_NOT_ALLOWED', 'message' => 'Not allowed.'],
        ], 422),
    ]);

    try {
        client()->refunds()->create(['refund_reference' => 'rf-1']);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errorCode)->toBe('REFUND_NOT_ALLOWED');
    }
});

it('throws GatewayException for a 5xx-mapped gateway failure', function () {
    Http::fake([
        '*' => Http::response([
            'accepted' => false,
            'error' => ['code' => 'GATEWAY_ERROR', 'message' => 'Upstream error.'],
        ], 502),
    ]);

    client()->gateway()->capture(['order_id' => 'x']);
})->throws(GatewayException::class);

it('exposes checkout_url from the checkout endpoint', function () {
    Http::fake([
        'api.example.test/api/v1/checkout-url' => Http::response([
            'accepted' => true,
            'checkout_url' => 'https://pay.example.test/p?body=1&signature=abc',
        ], 200),
    ]);

    $response = client()->checkout()->url(['merchant_payment_id' => 'order-1']);

    expect($response->checkoutUrl())->toContain('signature=');
});

it('resolves the facade to the client', function () {
    Http::fake(['*' => Http::response(['accepted' => true], 200)]);

    expect(Vspay::gateway()->convertRate(['params' => ['currency_from' => 'USD', 'currency_to' => 'RUB']])->accepted())
        ->toBeTrue();
});

it('wraps connection failures in VspayException', function () {
    Http::fake(function () {
        throw new ConnectionException('timeout');
    });

    client()->payments()->create([]);
})->throws(VspayException::class);
