<?php

use Topvendor\Vspay\Client\Response;

it('reads a legacy succeeded response without requested amount', function () {
    $response = new Response([
        'status' => 'succeeded',
        'amount' => 10000,
    ], 200);

    expect($response->amountMinor())->toBe(10000)
        ->and($response->requestedAmountMinor())->toBe(10000)
        ->and($response->currency())->toBeNull()
        ->and($response->cryptoExpectedAmount())->toBeNull()
        ->and($response->cryptoReceivedAmount())->toBeNull()
        ->and($response->isUnderpaid())->toBeFalse();
});

it('reads a fully settled succeeded response', function () {
    $response = new Response([
        'status' => 'succeeded',
        'amount' => 10000,
        'requested_amount' => 10000,
        'currency' => 'RUB',
        'crypto_expected_amount' => '1.25000000',
        'crypto_received_amount' => '1.25000000',
    ], 200);

    expect($response->amountMinor())->toBe(10000)
        ->and($response->requestedAmountMinor())->toBe(10000)
        ->and($response->currency())->toBe('RUB')
        ->and($response->cryptoExpectedAmount())->toBe('1.25000000')
        ->and($response->cryptoReceivedAmount())->toBe('1.25000000')
        ->and($response->isUnderpaid())->toBeFalse();
});

it('detects an underpaid succeeded response', function () {
    $response = new Response([
        'status' => 'succeeded',
        'amount' => 7500,
        'requested_amount' => 10000,
        'currency' => 'RUB',
        'crypto_expected_amount' => '1.25000000',
        'crypto_received_amount' => '0.93750000',
    ], 200);

    expect($response->amountMinor())->toBe(7500)
        ->and($response->requestedAmountMinor())->toBe(10000)
        ->and($response->currency())->toBe('RUB')
        ->and($response->cryptoExpectedAmount())->toBe('1.25000000')
        ->and($response->cryptoReceivedAmount())->toBe('0.93750000')
        ->and($response->isUnderpaid())->toBeTrue();
});

it('returns null for absent amount fields', function () {
    $response = new Response(['status' => 'succeeded'], 200);

    expect($response->amountMinor())->toBeNull()
        ->and($response->requestedAmountMinor())->toBeNull()
        ->and($response->cryptoExpectedAmount())->toBeNull()
        ->and($response->cryptoReceivedAmount())->toBeNull()
        ->and($response->isUnderpaid())->toBeFalse();
});

it('does not classify zero, overpaid, or missing requested amounts as underpaid', function (array $payload) {
    $response = new Response($payload, 200);

    expect($response->isUnderpaid())->toBeFalse();
})->with([
    'zero settled' => [['amount' => 0, 'requested_amount' => 10000]],
    'overpaid' => [['amount' => 10001, 'requested_amount' => 10000]],
    'requested missing' => [['amount' => 7500]],
]);

it('rejects malformed response money fields', function (array $payload, string $method) {
    $response = new Response($payload, 200);

    $response->{$method}();
})->with([
    'float amount' => [['amount' => 100.5], 'amountMinor'],
    'whole float amount' => [['amount' => 100.0], 'amountMinor'],
    'negative amount' => [['amount' => -1], 'amountMinor'],
    'string amount' => [['amount' => '10000'], 'amountMinor'],
    'boolean amount' => [['amount' => true], 'amountMinor'],
    'null requested amount' => [['amount' => 100, 'requested_amount' => null], 'requestedAmountMinor'],
    'negative requested amount' => [['amount' => 100, 'requested_amount' => -1], 'requestedAmountMinor'],
    'float requested amount' => [['amount' => 100, 'requested_amount' => 100.5], 'requestedAmountMinor'],
])->throws(UnexpectedValueException::class);

it('preserves canonical response crypto decimal strings', function (string $amount) {
    $response = new Response([
        'crypto_expected_amount' => $amount,
        'crypto_received_amount' => $amount,
    ], 200);

    expect($response->cryptoExpectedAmount())->toBe($amount)
        ->and($response->cryptoReceivedAmount())->toBe($amount);
})->with(['0', '0.00000001', '10', '10.25000000', '12345678901234567890.123456789']);

it('rejects non-canonical response crypto decimals', function (mixed $amount) {
    $response = new Response(['crypto_expected_amount' => $amount], 200);

    $response->cryptoExpectedAmount();
})->with([
    'negative' => ['-0.1'],
    'explicit plus' => ['+1.0'],
    'exponent' => ['1e-8'],
    'float' => [0.1],
    'integer' => [1],
    'leading zeroes' => ['01.0'],
    'missing integer part' => ['.1'],
    'missing fraction' => ['1.'],
    'whitespace' => [' 1.0'],
    'decimal comma' => ['1,0'],
])->throws(UnexpectedValueException::class);
