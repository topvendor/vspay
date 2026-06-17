<?php

namespace Topvendor\Vspay\Facades;

use Illuminate\Support\Facades\Facade;
use Topvendor\Vspay\Client\Config;
use Topvendor\Vspay\Client\Response;
use Topvendor\Vspay\Client\VspayClient;
use Topvendor\Vspay\Resources\Checkout;
use Topvendor\Vspay\Resources\Gateway;
use Topvendor\Vspay\Resources\Payments;
use Topvendor\Vspay\Resources\Refunds;
use Topvendor\Vspay\Webhooks\WebhookVerifier;

/**
 * @method static Payments payments()
 * @method static Refunds refunds()
 * @method static Gateway gateway()
 * @method static Checkout checkout()
 * @method static WebhookVerifier webhooks()
 * @method static Config config()
 * @method static Response post(string $path, array $body)
 *
 * @see VspayClient
 */
final class Vspay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VspayClient::class;
    }
}
