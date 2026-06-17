<?php

namespace Topvendor\Vspay\Resources;

use Topvendor\Vspay\Client\Response;

/**
 * Orchestrated charge endpoint.
 *
 * Field naming (orchestrated dialect): merchant_payment_id, payer, instrument.
 * Amounts are integers in minor units; currency is ISO 4217.
 */
final class Payments extends Resource
{
    /**
     * Create a charge.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Response
    {
        return $this->client->post('payments', $payload);
    }
}
