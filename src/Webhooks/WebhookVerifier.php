<?php

namespace Topvendor\Vspay\Webhooks;

use InvalidArgumentException;

/**
 * Verifies incoming merchant webhooks.
 *
 * The platform signs the raw request body with HMAC-SHA256 using the merchant
 * webhook secret and sends the hex digest in the "X-Webhook-Signature" header.
 */
final class WebhookVerifier
{
    public const SIGNATURE_HEADER = 'X-Webhook-Signature';

    public function __construct(
        private readonly ?string $secret = null,
    ) {}

    /**
     * Compute the expected signature for a raw payload.
     */
    public function sign(string $rawBody, ?string $secret = null): string
    {
        $secret = $secret ?? $this->secret;

        if ($secret === null || $secret === '') {
            throw new InvalidArgumentException('VSPay webhook secret is not configured. Set VSPAY_WEBHOOK_SECRET.');
        }

        return hash_hmac('sha256', $rawBody, $secret);
    }

    /**
     * Constant-time verification of a webhook signature.
     *
     * @param  string  $rawBody  The exact raw request body (do not re-encode).
     * @param  string  $signature  Value of the X-Webhook-Signature header.
     */
    public function verify(string $rawBody, string $signature, ?string $secret = null): bool
    {
        if ($signature === '') {
            return false;
        }

        $expected = $this->sign($rawBody, $secret);

        return hash_equals($expected, trim($signature));
    }
}
