<?php

namespace Topvendor\Vspay\Resources;

use Topvendor\Vspay\Client\Response;
use Topvendor\Vspay\Exceptions\VspayException;

/**
 * UZ merchant-hosted checkout (ehotpay proxy endpoints).
 *
 * Create pay-in orders with amount in RUB; the processing platform rounds the amount
 * up to whole rubles, converts to UZS at the CBR daily rate, and forwards UZS to
 * ehotpay. Response `amount` fields are in UZS.
 */
final class Uz extends Resource
{
    /**
     * @param  array<string, mixed>  $payload  Required: merchant_order_id, amount (RUB string),
     *                                         currency ("RUB"), pay_in_details.payment_method,
     *                                         payer.id, payer.ip. Optional: webhook_url.
     *
     * @throws VspayException
     */
    public function createPayInOrder(array $payload): Response
    {
        return $this->client->postProvider('uz/create-pay-in-order', $payload);
    }

    /**
     * @throws VspayException
     */
    public function getPayInOrderByMerchantId(string $merchantOrderId): Response
    {
        return $this->client->getProvider(
            'uz/get-pay-in-order-by-merchant-id/'.rawurlencode($merchantOrderId),
        );
    }
}
