<?php

use Illuminate\Http\Request;
use Topvendor\Vspay\Redirects\ReturnQuery;

it('parses redirect query parameters from an array', function () {
    $query = ReturnQuery::fromArray([
        'vspay_status' => 'success',
        'merchant_payment_id' => 'order-42',
        'operation_uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        'noise' => 'ignored',
    ]);

    expect($query->status)->toBe('success')
        ->and($query->merchantPaymentId)->toBe('order-42')
        ->and($query->operationUuid)->toBe('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11')
        ->and($query->hasStatus())->toBeTrue()
        ->and($query->isSuccess())->toBeTrue()
        ->and($query->isFailed())->toBeFalse()
        ->and($query->isPending())->toBeFalse();
});

it('parses redirect query parameters from a request', function () {
    $request = Request::create(
        '/pay/return',
        'GET',
        [
            'vspay_status' => 'pending',
            'merchant_payment_id' => 'order-1',
        ],
    );

    $query = ReturnQuery::fromRequest($request);

    expect($query->isPending())->toBeTrue()
        ->and($query->merchantPaymentId)->toBe('order-1')
        ->and($query->operationUuid)->toBeNull();
});

it('treats empty strings as missing values', function () {
    $query = ReturnQuery::fromArray([
        'vspay_status' => '   ',
        'merchant_payment_id' => '',
    ]);

    expect($query->status)->toBeNull()
        ->and($query->merchantPaymentId)->toBeNull()
        ->and($query->hasStatus())->toBeFalse();
});

it('detects failed status', function () {
    expect(ReturnQuery::fromArray(['vspay_status' => 'failed'])->isFailed())->toBeTrue();
});
