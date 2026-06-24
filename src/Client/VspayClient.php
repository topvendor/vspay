<?php

namespace Topvendor\Vspay\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response as HttpResponse;
use Topvendor\Vspay\Exceptions\GatewayException;
use Topvendor\Vspay\Exceptions\RateLimitException;
use Topvendor\Vspay\Exceptions\UnauthorizedException;
use Topvendor\Vspay\Exceptions\ValidationException;
use Topvendor\Vspay\Exceptions\VspayException;
use Topvendor\Vspay\Resources\Checkout;
use Topvendor\Vspay\Resources\Gateway;
use Topvendor\Vspay\Resources\Payments;
use Topvendor\Vspay\Resources\Refunds;
use Topvendor\Vspay\Resources\Uz;
use Topvendor\Vspay\Webhooks\WebhookVerifier;

/**
 * HTTP client for the VSPay merchant API.
 *
 * Authenticates with the terminal secret via Bearer header, posts JSON to
 * /api/v1/* endpoints, parses the {accepted, error, provider, ...} envelope
 * and maps failures to typed exceptions.
 */
final class VspayClient
{
    public function __construct(
        private readonly Config $config,
        private readonly HttpFactory $http,
    ) {}

    public function payments(): Payments
    {
        return new Payments($this);
    }

    public function refunds(): Refunds
    {
        return new Refunds($this);
    }

    public function gateway(): Gateway
    {
        return new Gateway($this);
    }

    public function checkout(): Checkout
    {
        return new Checkout($this);
    }

    public function uz(): Uz
    {
        return new Uz($this);
    }

    public function webhooks(): WebhookVerifier
    {
        return new WebhookVerifier($this->config->webhookSecret);
    }

    public function config(): Config
    {
        return $this->config;
    }

    /**
     * Perform a POST to an /api/v1 path and return the parsed envelope.
     *
     * @param  array<string, mixed>  $body
     *
     * @throws VspayException
     */
    public function post(string $path, array $body): Response
    {
        try {
            $http = $this->http
                ->acceptJson()
                ->asJson()
                ->withToken($this->config->secret)
                ->timeout($this->config->timeout)
                ->retry(
                    times: max(1, $this->config->retries + 1),
                    sleepMilliseconds: $this->config->retryDelayMs,
                    when: fn ($exception, $request) => $exception instanceof ConnectionException,
                    throw: false,
                )
                ->post($this->config->url($path), $body);
        } catch (ConnectionException $e) {
            throw new VspayException(
                message: 'Failed to connect to VSPay API: '.$e->getMessage(),
                previous: $e,
            );
        }

        return $this->handle($http);
    }

    /**
     * POST to a provider-shaped endpoint (ehotpay proxy) where success is HTTP 2xx
     * without the standard `{accepted: true}` envelope.
     *
     * @param  array<string, mixed>  $body
     *
     * @throws VspayException
     */
    public function postProvider(string $path, array $body): Response
    {
        try {
            $http = $this->http
                ->acceptJson()
                ->asJson()
                ->withToken($this->config->secret)
                ->timeout($this->config->timeout)
                ->retry(
                    times: max(1, $this->config->retries + 1),
                    sleepMilliseconds: $this->config->retryDelayMs,
                    when: fn ($exception, $request) => $exception instanceof ConnectionException,
                    throw: false,
                )
                ->post($this->config->url($path), $body);
        } catch (ConnectionException $e) {
            throw new VspayException(
                message: 'Failed to connect to VSPay API: '.$e->getMessage(),
                previous: $e,
            );
        }

        return $this->handleProvider($http);
    }

    /**
     * GET from a provider-shaped endpoint (ehotpay proxy).
     *
     * @throws VspayException
     */
    public function getProvider(string $path): Response
    {
        try {
            $http = $this->http
                ->acceptJson()
                ->withToken($this->config->secret)
                ->timeout($this->config->timeout)
                ->retry(
                    times: max(1, $this->config->retries + 1),
                    sleepMilliseconds: $this->config->retryDelayMs,
                    when: fn ($exception, $request) => $exception instanceof ConnectionException,
                    throw: false,
                )
                ->get($this->config->url($path));
        } catch (ConnectionException $e) {
            throw new VspayException(
                message: 'Failed to connect to VSPay API: '.$e->getMessage(),
                previous: $e,
            );
        }

        return $this->handleProvider($http);
    }

    private function handle(HttpResponse $http): Response
    {
        $data = $http->json();

        if (! is_array($data)) {
            $data = [];
        }

        /** @var array<string, mixed> $data */
        $response = new Response($data, $http->status());

        if ($http->successful() && $response->accepted()) {
            return $response;
        }

        $this->throwForResponse($response);
    }

    private function handleProvider(HttpResponse $http): Response
    {
        $data = $http->json();

        if (! is_array($data)) {
            $data = [];
        }

        /** @var array<string, mixed> $data */
        $response = new Response($data, $http->status());

        if (($data['accepted'] ?? null) === false) {
            $this->throwForResponse($response);
        }

        if ($http->successful()) {
            return $response;
        }

        $this->throwForResponse($response);
    }

    /**
     * @return never
     *
     * @throws VspayException
     */
    private function throwForResponse(Response $response): void
    {
        $error = is_array($response->data['error'] ?? null) ? $response->data['error'] : [];
        $code = isset($error['code']) ? (string) $error['code'] : null;
        $message = isset($error['message']) && $error['message'] !== ''
            ? (string) $error['message']
            : 'VSPay API request failed.';
        $details = is_array($error['details'] ?? null) ? $error['details'] : [];
        $status = $response->status;

        $args = [
            'message' => $message,
            'errorCode' => $code,
            'statusCode' => $status,
            'details' => $details,
            'response' => $response->data,
        ];

        throw match (true) {
            $status === 401 => new UnauthorizedException(...$args),
            $status === 422 => new ValidationException(...$args),
            $status === 429 => $this->rateLimit($args),
            default => new GatewayException(...$args),
        };
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function rateLimit(array $args): RateLimitException
    {
        $exception = new RateLimitException(...$args);

        $retryAfter = $args['response']['retry_after'] ?? null;
        if (is_numeric($retryAfter)) {
            $exception->retryAfter = (int) $retryAfter;
        }

        return $exception;
    }
}
