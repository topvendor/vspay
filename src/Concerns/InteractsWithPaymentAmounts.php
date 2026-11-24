<?php

namespace Topvendor\Vspay\Concerns;

use UnexpectedValueException;

trait InteractsWithPaymentAmounts
{
    abstract public function get(string $key, mixed $default = null): mixed;

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * Actually settled amount in minor currency units.
     */
    public function amountMinor(): ?int
    {
        return $this->minorAmount('amount');
    }

    /**
     * Originally requested amount in minor currency units.
     *
     * Legacy payloads omit requested_amount; for those, amount is the requested amount.
     */
    public function requestedAmountMinor(): ?int
    {
        if (! array_key_exists('requested_amount', $this->toArray())) {
            return $this->amountMinor();
        }

        return $this->minorAmount('requested_amount', false);
    }

    public function currency(): ?string
    {
        $value = $this->get('currency');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Expected crypto amount as an exact decimal string.
     */
    public function cryptoExpectedAmount(): ?string
    {
        return $this->decimalAmount('crypto_expected_amount');
    }

    /**
     * Actually received crypto amount as an exact decimal string.
     */
    public function cryptoReceivedAmount(): ?string
    {
        return $this->decimalAmount('crypto_received_amount');
    }

    public function isUnderpaid(): bool
    {
        $amount = $this->amountMinor();
        $requested = $this->requestedAmountMinor();

        return $amount !== null
            && $requested !== null
            && $amount > 0
            && $amount < $requested;
    }

    private function minorAmount(string $key, bool $allowNull = true): ?int
    {
        $value = $this->get($key);

        if ($value === null && $allowNull) {
            return null;
        }

        if (! is_int($value) || $value < 0) {
            throw new UnexpectedValueException(sprintf(
                'VSPay field [%s] must be a non-negative integer amount in minor units.',
                $key,
            ));
        }

        return $value;
    }

    private function decimalAmount(string $key): ?string
    {
        $value = $this->get($key);

        if ($value === null) {
            return null;
        }

        if (! is_string($value) || preg_match('/\A(?:0|[1-9][0-9]*)(?:\.[0-9]+)?\z/', $value) !== 1) {
            throw new UnexpectedValueException(sprintf(
                'VSPay field [%s] must be a canonical non-negative decimal string without an exponent.',
                $key,
            ));
        }

        return $value;
    }
}
