<?php

use Topvendor\Vspay\Webhooks\WebhookPayload;

it('parses a legacy succeeded webhook without requested amount', function () {
    $payload = [
        'event' => 'payment.succeeded',
        'status' => 'succeeded',
        'merchant_payment_id' => 'order-legacy',
        'operation_type' => 'charge',
        'amount' => 10000,
    ];
    $webhook = new WebhookPayload($payload);

    expect($webhook->event())->toBe('payment.succeeded')
        ->and($webhook->statusValue())->toBe('succeeded')
        ->and($webhook->merchantPaymentId())->toBe('order-legacy')
        ->and($webhook->operationType())->toBe('charge')
        ->and($webhook->amountMinor())->toBe(10000)
        ->and($webhook->requestedAmountMinor())->toBe(10000)
        ->and($webhook->currency())->toBeNull()
        ->and($webhook->cryptoExpectedAmount())->toBeNull()
        ->and($webhook->cryptoReceivedAmount())->toBeNull()
        ->and($webhook->isUnderpaid())->toBeFalse()
        ->and($webhook->get('status'))->toBe('succeeded')
        ->and($webhook->get('missing', 'fallback'))->toBe('fallback')
        ->and($webhook->toArray())->toBe($payload)
        ->and($webhook->data)->toBe($payload);
});

it('parses a fully settled succeeded webhook', function () {
    $webhook = new WebhookPayload([
        'event' => 'payment.succeeded',
        'status' => 'succeeded',
        'merchant_payment_id' => 'order-full',
        'operation_type' => 'charge',
        'amount' => 10000,
        'requested_amount' => 10000,
        'currency' => 'RUB',
        'crypto_expected_amount' => '1.25000000',
        'crypto_received_amount' => '1.25000000',
    ]);

    expect($webhook->amountMinor())->toBe(10000)
        ->and($webhook->requestedAmountMinor())->toBe(10000)
        ->and($webhook->currency())->toBe('RUB')
        ->and($webhook->cryptoExpectedAmount())->toBe('1.25000000')
        ->and($webhook->cryptoReceivedAmount())->toBe('1.25000000')
        ->and($webhook->isUnderpaid())->toBeFalse();
});

it('parses an underpaid succeeded webhook', function () {
    $webhook = new WebhookPayload([
        'event' => 'payment.succeeded',
        'status' => 'succeeded',
        'merchant_payment_id' => 'order-underpaid',
        'operation_type' => 'charge',
        'amount' => 7500,
        'requested_amount' => 10000,
        'currency' => 'RUB',
        'crypto_expected_amount' => '1.25000000',
        'crypto_received_amount' => '0.93750000',
    ]);

    expect($webhook->amountMinor())->toBe(7500)
        ->and($webhook->requestedAmountMinor())->toBe(10000)
        ->and($webhook->currency())->toBe('RUB')
        ->and($webhook->cryptoExpectedAmount())->toBe('1.25000000')
        ->and($webhook->cryptoReceivedAmount())->toBe('0.93750000')
        ->and($webhook->isUnderpaid())->toBeTrue();
});

it('returns null for absent webhook fields', function () {
    $webhook = new WebhookPayload([]);

    expect($webhook->event())->toBeNull()
        ->and($webhook->statusValue())->toBeNull()
        ->and($webhook->merchantPaymentId())->toBeNull()
        ->and($webhook->operationType())->toBeNull()
        ->and($webhook->amountMinor())->toBeNull()
        ->and($webhook->requestedAmountMinor())->toBeNull()
        ->and($webhook->currency())->toBeNull()
        ->and($webhook->cryptoExpectedAmount())->toBeNull()
        ->and($webhook->cryptoReceivedAmount())->toBeNull()
        ->and($webhook->isUnderpaid())->toBeFalse();
});

it('requires a positive settled amount below the requested webhook amount', function (array $payload, bool $expected) {
    $webhook = new WebhookPayload($payload);

    expect($webhook->isUnderpaid())->toBe($expected);
})->with([
    'underpaid' => [['amount' => 1, 'requested_amount' => 2], true],
    'zero settled' => [['amount' => 0, 'requested_amount' => 10000], false],
    'equal' => [['amount' => 10000, 'requested_amount' => 10000], false],
    'overpaid' => [['amount' => 10001, 'requested_amount' => 10000], false],
    'requested missing' => [['amount' => 7500], false],
]);

it('rejects malformed webhook money fields', function (array $payload, string $method) {
    $webhook = new WebhookPayload($payload);

    $webhook->{$method}();
})->with([
    'float amount' => [['amount' => 100.5], 'amountMinor'],
    'negative amount' => [['amount' => -1], 'amountMinor'],
    'string amount' => [['amount' => '10000'], 'amountMinor'],
    'null requested amount' => [['amount' => 100, 'requested_amount' => null], 'requestedAmountMinor'],
    'negative requested amount' => [['amount' => 100, 'requested_amount' => -1], 'requestedAmountMinor'],
    'float requested amount' => [['amount' => 100, 'requested_amount' => 100.5], 'requestedAmountMinor'],
])->throws(UnexpectedValueException::class);

it('preserves canonical webhook crypto decimal strings', function () {
    $webhook = new WebhookPayload([
        'crypto_expected_amount' => '12345678901234567890.123456789',
        'crypto_received_amount' => '0.00000001',
    ]);

    expect($webhook->cryptoExpectedAmount())->toBe('12345678901234567890.123456789')
        ->and($webhook->cryptoReceivedAmount())->toBe('0.00000001');
});

it('rejects non-canonical webhook crypto decimals', function (mixed $amount, string $method) {
    $webhook = new WebhookPayload([
        'crypto_expected_amount' => $amount,
        'crypto_received_amount' => $amount,
    ]);

    $webhook->{$method}();
})->with([
    'negative expected' => ['-0.1', 'cryptoExpectedAmount'],
    'exponent received' => ['1e-8', 'cryptoReceivedAmount'],
    'float expected' => [0.1, 'cryptoExpectedAmount'],
    'leading zeroes received' => ['01.0', 'cryptoReceivedAmount'],
    'whitespace expected' => [' 1.0', 'cryptoExpectedAmount'],
])->throws(UnexpectedValueException::class);
