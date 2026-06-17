<?php

namespace Topvendor\Vspay\Client;

use InvalidArgumentException;

/**
 * Immutable client configuration. Holds connection details only; never
 * persists endpoints or credentials inside the package itself.
 */
final class Config
{
    public readonly string $baseUrl;

    public function __construct(
        string $baseUrl,
        public readonly string $secret,
        public readonly int $timeout = 15,
        public readonly int $retries = 2,
        public readonly int $retryDelayMs = 200,
        public readonly ?string $webhookSecret = null,
    ) {
        $baseUrl = trim($baseUrl);

        if ($baseUrl === '') {
            throw new InvalidArgumentException('VSPay base_url is not configured. Set VSPAY_BASE_URL.');
        }

        if ($this->secret === '') {
            throw new InvalidArgumentException('VSPay secret is not configured. Set VSPAY_SECRET.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            baseUrl: (string) ($config['base_url'] ?? ''),
            secret: (string) ($config['secret'] ?? ''),
            timeout: (int) ($config['timeout'] ?? 15),
            retries: (int) ($config['retries'] ?? 2),
            retryDelayMs: (int) ($config['retry_delay_ms'] ?? 200),
            webhookSecret: isset($config['webhook_secret']) && $config['webhook_secret'] !== ''
                ? (string) $config['webhook_secret']
                : null,
        );
    }

    /**
     * Full URL for an API v1 path, e.g. "payments" -> {base}/api/v1/payments.
     */
    public function url(string $path): string
    {
        return $this->baseUrl.'/api/v1/'.ltrim($path, '/');
    }
}
