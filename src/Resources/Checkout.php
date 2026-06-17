<?php

namespace Topvendor\Vspay\Resources;

use Topvendor\Vspay\Client\Response;

/**
 * Hosted checkout URL endpoint.
 *
 * Uses orchestrated naming: merchant_payment_id, success_redirect_url,
 * webhook_url, customer, payment_data. Returns a signed checkout_url.
 */
final class Checkout extends Resource
{
    /**
     * Build a signed checkout URL.
     *
     * @param  array<string, mixed>  $payload
     */
    public function url(array $payload): Response
    {
        return $this->client->post('checkout-url', $payload);
    }
}
