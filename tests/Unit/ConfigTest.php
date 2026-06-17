<?php

use Topvendor\Vspay\Client\Config;

it('builds api v1 urls and trims slashes', function () {
    $config = new Config('https://api.example.test/', 'secret');

    expect($config->url('payments'))->toBe('https://api.example.test/api/v1/payments')
        ->and($config->url('/payments/authorize'))->toBe('https://api.example.test/api/v1/payments/authorize');
});

it('requires a base url', function () {
    new Config('', 'secret');
})->throws(InvalidArgumentException::class);

it('requires a secret', function () {
    new Config('https://api.example.test', '');
})->throws(InvalidArgumentException::class);

it('hydrates from an array', function () {
    $config = Config::fromArray([
        'base_url' => 'https://api.example.test',
        'secret' => 'sk',
        'timeout' => 30,
        'retries' => 3,
        'webhook_secret' => 'wh',
    ]);

    expect($config->timeout)->toBe(30)
        ->and($config->retries)->toBe(3)
        ->and($config->webhookSecret)->toBe('wh');
});
