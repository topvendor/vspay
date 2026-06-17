<?php

use Topvendor\Vspay\Webhooks\WebhookVerifier;

it('verifies a valid signature', function () {
    $verifier = new WebhookVerifier('shhh');
    $body = '{"request_uuid":"abc","status":"Success"}';
    $signature = hash_hmac('sha256', $body, 'shhh');

    expect($verifier->verify($body, $signature))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $verifier = new WebhookVerifier('shhh');

    expect($verifier->verify('{"a":1}', 'deadbeef'))->toBeFalse();
});

it('rejects an empty signature', function () {
    $verifier = new WebhookVerifier('shhh');

    expect($verifier->verify('{"a":1}', ''))->toBeFalse();
});

it('allows overriding the secret per call', function () {
    $verifier = new WebhookVerifier(null);
    $body = '{"a":1}';
    $signature = hash_hmac('sha256', $body, 'other');

    expect($verifier->verify($body, $signature, 'other'))->toBeTrue();
});

it('throws when no secret is configured', function () {
    $verifier = new WebhookVerifier(null);

    $verifier->sign('{"a":1}');
})->throws(InvalidArgumentException::class);
