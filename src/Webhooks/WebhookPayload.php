<?php

namespace Topvendor\Vspay\Webhooks;

use Topvendor\Vspay\Concerns\InteractsWithPaymentAmounts;

/**
 * Immutable accessor for a decoded VSPay webhook payload.
 */
final class WebhookPayload
{
    use InteractsWithPaymentAmounts;

    /**
     * @param  array<string, mixed>  $data  Decoded JSON body.
     */
    public function __construct(
        public readonly array $data,
    ) {}

    public function event(): ?string
    {
        $value = $this->data['event'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function statusValue(): ?string
    {
        $value = $this->data['status'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function merchantPaymentId(): ?string
    {
        $value = $this->data['merchant_payment_id'] ?? null;

        return $value === null ? null : (string) $value;
    }

    public function operationType(): ?string
    {
        $value = $this->data['operation_type'] ?? null;

        return $value === null ? null : (string) $value;
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
}
