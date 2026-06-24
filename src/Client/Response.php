<?php

namespace Topvendor\Vspay\Client;

use ArrayAccess;

/**
 * Thin wrapper over the API response envelope.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Response implements ArrayAccess
{
    /**
     * @param  array<string, mixed>  $data  Decoded JSON body.
     * @param  int  $status  HTTP status code.
     */
    public function __construct(
        public readonly array $data,
        public readonly int $status,
    ) {}

    public function accepted(): bool
    {
        return ($this->data['accepted'] ?? false) === true;
    }

    /**
     * Provider sub-object (raw gateway payload), if present.
     *
     * @return array<string, mixed>
     */
    public function provider(): array
    {
        $provider = $this->data['provider'] ?? [];

        return is_array($provider) ? $provider : [];
    }

    /**
     * Gateway-side external request id, if present.
     */
    public function providerRequestId(): ?string
    {
        $value = $this->data['provider_request_id'] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * Internal charge operation uuid. Returned on accepted charge responses
     * and used as `charge_operation_uuid` when issuing a refund.
     */
    public function chargeOperationUuid(): ?string
    {
        $value = $this->data['charge_operation_uuid'] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * Hosted checkout payment URL (orchestrated charge with hosted driver).
     */
    public function paymentUrl(): ?string
    {
        $value = $this->data['payment_url'] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * Signed checkout URL (from /checkout-url).
     */
    public function checkoutUrl(): ?string
    {
        $value = $this->data['checkout_url'] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * Operation status string when returned (e.g. "pending").
     */
    public function statusValue(): ?string
    {
        $value = $this->data['status'] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * Normalized status label from ehotpay proxy responses.
     */
    public function statusLabel(): ?string
    {
        $value = $this->data['status_label'] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * Numeric ehotpay order status (0 = awaiting, 1 = success, ...).
     */
    public function providerStatus(): ?int
    {
        $value = $this->data['status'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public function merchantOrderId(): ?string
    {
        $value = $this->data['merchant_order_id'] ?? null;

        return $value === null ? null : (string) $value;
    }

    public function uid(): ?string
    {
        $value = $this->data['uid'] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentsDetails(): array
    {
        $details = $this->data['payments_details'] ?? [];

        return is_array($details) ? $details : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('Vspay\Client\Response is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Vspay\Client\Response is immutable.');
    }
}
