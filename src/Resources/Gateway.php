<?php

namespace Topvendor\Vspay\Resources;

use Topvendor\Vspay\Client\Response;

/**
 * Thin pass-through gateway operations.
 *
 * Field naming (thin dialect): order_id, customer, payment, payment_data /
 * payout_data. These map directly onto the upstream gateway body.
 */
final class Gateway extends Resource
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function authorize(array $payload): Response
    {
        return $this->client->post('payments/authorize', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function authorizeIncrement(array $payload): Response
    {
        return $this->client->post('payments/authorize/increment', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function authorizeReversal(array $payload): Response
    {
        return $this->client->post('payments/authorize/reversal', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function capture(array $payload): Response
    {
        return $this->client->post('payments/capture', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recurring(array $payload): Response
    {
        return $this->client->post('payments/recurring', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recurringCancel(array $payload): Response
    {
        return $this->client->post('payments/recurring/cancel', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function payout(array $payload): Response
    {
        return $this->client->post('payouts', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function convertRate(array $payload): Response
    {
        return $this->client->post('convert/rate', $payload);
    }

    /**
     * Query operation status by order_id or request_uuid (exactly one).
     *
     * @param  array<string, mixed>  $payload
     */
    public function status(array $payload): Response
    {
        return $this->client->post('status', $payload);
    }
}
