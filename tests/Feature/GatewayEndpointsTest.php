<?php

use Illuminate\Support\Facades\Http;
use Topvendor\Vspay\Client\VspayClient;

dataset('gateway endpoints', [
    'authorize' => [fn (VspayClient $c) => $c->gateway()->authorize(['order_id' => 'o']), 'payments/authorize'],
    'authorize increment' => [fn (VspayClient $c) => $c->gateway()->authorizeIncrement(['order_id' => 'o']), 'payments/authorize/increment'],
    'authorize reversal' => [fn (VspayClient $c) => $c->gateway()->authorizeReversal(['order_id' => 'o']), 'payments/authorize/reversal'],
    'capture' => [fn (VspayClient $c) => $c->gateway()->capture(['order_id' => 'o']), 'payments/capture'],
    'recurring' => [fn (VspayClient $c) => $c->gateway()->recurring(['order_id' => 'o']), 'payments/recurring'],
    'recurring cancel' => [fn (VspayClient $c) => $c->gateway()->recurringCancel(['order_id' => 'o']), 'payments/recurring/cancel'],
    'payout' => [fn (VspayClient $c) => $c->gateway()->payout(['order_id' => 'o']), 'payouts'],
    'convert rate' => [fn (VspayClient $c) => $c->gateway()->convertRate(['params' => []]), 'convert/rate'],
    'status' => [fn (VspayClient $c) => $c->gateway()->status(['merchant_payment_id' => 'o']), 'status'],
    'checkout url' => [fn (VspayClient $c) => $c->checkout()->url(['merchant_payment_id' => 'o']), 'checkout-url'],
    'refunds' => [fn (VspayClient $c) => $c->refunds()->create(['refund_reference' => 'r']), 'refunds'],
]);

it('posts to the expected endpoint', function (Closure $call, string $path) {
    Http::fake(['*' => Http::response(['accepted' => true], 200)]);

    $call(app(VspayClient::class));

    Http::assertSent(fn ($request) => $request->url() === "https://api.example.test/api/v1/{$path}");
})->with('gateway endpoints');
