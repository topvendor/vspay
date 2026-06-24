<?php

namespace Topvendor\Vspay\Resources;

use Topvendor\Vspay\Client\Response;

/**
 * UZ merchant-hosted checkout (ehotpay proxy endpoints).
 */
final class Uz extends Resource
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws \Topvendor\Vspay\Exceptions\VspayException
     */
    public function createPayInOrder(array $payload): Response
    {
        return $this->client->postProvider('uz/create-pay-in-order', $payload);
    }

    /**
     * @throws \Topvendor\Vspay\Exceptions\VspayException
     */
    public function getPayInOrderByMerchantId(string $merchantOrderId): Response
    {
        return $this->client->getProvider(
            'uz/get-pay-in-order-by-merchant-id/'.rawurlencode($merchantOrderId),
        );
    }
}
