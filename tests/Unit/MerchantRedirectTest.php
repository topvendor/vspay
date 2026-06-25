<?php

use Topvendor\Vspay\Redirects\MerchantRedirect;
use Topvendor\Vspay\Redirects\ReturnQuery;

it('appends status to a url without an existing query string', function () {
    $url = MerchantRedirect::withStatus(
        'https://merchant.com/pay/ok',
        ReturnQuery::STATUS_FAILED,
    );

    expect($url)->toBe('https://merchant.com/pay/ok?vspay_status=failed');
});

it('preserves an existing query string', function () {
    $url = MerchantRedirect::withStatus(
        'https://merchant.com/pay/ok?order=42',
        ReturnQuery::STATUS_SUCCESS,
        [
            'merchant_payment_id' => 'order-42',
            'operation_uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        ],
    );

    expect($url)->toBe(
        'https://merchant.com/pay/ok?order=42&vspay_status=success'
        .'&merchant_payment_id=order-42'
        .'&operation_uuid=a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
    );
});

it('returns an empty string for an empty url', function () {
    expect(MerchantRedirect::withStatus('   ', ReturnQuery::STATUS_FAILED))->toBe('');
});

it('skips null and empty extra parameters', function () {
    $url = MerchantRedirect::withStatus(
        'https://merchant.com/pay/fail',
        ReturnQuery::STATUS_FAILED,
        ['merchant_payment_id' => null, 'operation_uuid' => ''],
    );

    expect($url)->toBe('https://merchant.com/pay/fail?vspay_status=failed');
});
