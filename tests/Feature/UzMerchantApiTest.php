<?php

use Topvendor\Vspay\Exceptions\GatewayException;

it('creates a uz pay-in order via the ehotpay proxy', function () {
    Http::fake([
        'api.example.test/api/v1/uz/create-pay-in-order' => Http::response([
            'status' => 0,
            'status_label' => 'pending',
            'merchant_order_id' => 'uz-order-5001',
            'uid' => 'ehp-1',
            'payments_details' => [
                'bank_card_number' => '8600 1111 2222 3333',
                'recipient_name' => 'AZIZ KARIMOV',
            ],
            'charge_operation_uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        ], 201),
    ]);

    $response = client()->uz()->createPayInOrder([
        'merchant_order_id' => 'uz-order-5001',
        'amount' => '100000.00',
        'currency' => 'UZS',
        'pay_in_details' => ['payment_method' => 'UZ_UZCARD'],
        'payer' => ['id' => 'c1', 'ip' => '198.51.100.47'],
    ]);

    expect($response->providerStatus())->toBe(0)
        ->and($response->statusLabel())->toBe('pending')
        ->and($response->uid())->toBe('ehp-1')
        ->and($response->chargeOperationUuid())->toBe('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11')
        ->and($response->paymentsDetails()['recipient_name'])->toBe('AZIZ KARIMOV');
});

it('fetches a uz pay-in order by merchant id', function () {
    Http::fake([
        'api.example.test/api/v1/uz/get-pay-in-order-by-merchant-id/uz-order-5001' => Http::response([
            'status' => 1,
            'status_label' => 'succeeded',
            'merchant_order_id' => 'uz-order-5001',
            'uid' => 'ehp-1',
            'charge_operation_uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        ], 200),
    ]);

    $response = client()->uz()->getPayInOrderByMerchantId('uz-order-5001');

    expect($response->providerStatus())->toBe(1)
        ->and($response->statusLabel())->toBe('succeeded')
        ->and($response->merchantOrderId())->toBe('uz-order-5001');
});

it('maps uz flow conflict to gateway exception', function () {
    Http::fake([
        'api.example.test/api/v1/uz/create-pay-in-order' => Http::response([
            'accepted' => false,
            'error' => ['code' => 'FLOW_CONFLICT', 'message' => 'Conflict.'],
        ], 409),
    ]);

    client()->uz()->createPayInOrder([
        'merchant_order_id' => 'uz-order-5001',
        'amount' => '100.00',
        'currency' => 'UZS',
        'pay_in_details' => ['payment_method' => 'UZ_UZCARD'],
        'payer' => ['id' => 'c1', 'ip' => '198.51.100.47'],
    ]);
})->throws(GatewayException::class);
