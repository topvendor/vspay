<?php

namespace Topvendor\Vspay\Redirects;

use Illuminate\Http\Request;

/**
 * Query parameters appended when VSPay redirects the payer back to the merchant
 * after a hosted payment (success, error or pending). The signed webhook remains
 * the source of truth; this payload is UX-only for rendering the return page.
 */
final class ReturnQuery
{
    public const STATUS_PARAM = 'vspay_status';

    public const MERCHANT_PAYMENT_ID_PARAM = 'merchant_payment_id';

    public const OPERATION_UUID_PARAM = 'operation_uuid';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'pending';

    public function __construct(
        public readonly ?string $status,
        public readonly ?string $merchantPaymentId,
        public readonly ?string $operationUuid,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public static function fromArray(array $query): self
    {
        return new self(
            status: self::stringOrNull($query[self::STATUS_PARAM] ?? null),
            merchantPaymentId: self::stringOrNull($query[self::MERCHANT_PAYMENT_ID_PARAM] ?? null),
            operationUuid: self::stringOrNull($query[self::OPERATION_UUID_PARAM] ?? null),
        );
    }

    public static function fromRequest(Request $request): self
    {
        /** @var array<string, mixed> $query */
        $query = $request->query();

        return self::fromArray($query);
    }

    public function hasStatus(): bool
    {
        return $this->status !== null && $this->status !== '';
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
