<?php

namespace Topvendor\Vspay\Resources;

use Topvendor\Vspay\Client\Response;

/**
 * Refund endpoint.
 *
 * Requires charge_operation_uuid identifying the parent charge operation.
 * Note: the charge response does not currently expose this uuid; merchants
 * must obtain it from the cabinet or a future API field.
 */
final class Refunds extends Resource
{
    /**
     * Create a refund.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Response
    {
        return $this->client->post('refunds', $payload);
    }
}
