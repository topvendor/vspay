# VSPay Laravel SDK

[![tests](https://github.com/topvendor/vspay/actions/workflows/tests.yml/badge.svg)](https://github.com/topvendor/vspay/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/topvendor/vspay.svg)](https://packagist.org/packages/topvendor/vspay)
[![License](https://img.shields.io/packagist/l/topvendor/vspay.svg)](LICENSE)

Official Laravel SDK for the **VSPay merchant API**. A thin, typed wrapper over
the `/api/v1/*` HTTP endpoints: charges, refunds, gateway operations (authorize,
capture, recurring, payouts, conversion, status), hosted checkout URLs and
webhook signature verification.

The package contains **no endpoints and no credentials** — base URL and your
terminal secret live in your application's environment.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require topvendor/vspay
```

Publish the config file (optional — the package works with env vars alone):

```bash
php artisan vendor:publish --tag=vspay-config
```

## Configuration

Set the following in your `.env`:

```dotenv
VSPAY_BASE_URL=https://api.example.com
VSPAY_SECRET=your-terminal-secret-key
VSPAY_WEBHOOK_SECRET=your-webhook-secret

# Optional
VSPAY_TIMEOUT=15
VSPAY_RETRIES=2
VSPAY_RETRY_DELAY_MS=200
```

| Key | Env | Description |
| --- | --- | --- |
| `base_url` | `VSPAY_BASE_URL` | API root, without `/api/v1`. |
| `secret` | `VSPAY_SECRET` | Terminal secret, sent as `Authorization: Bearer`. |
| `webhook_secret` | `VSPAY_WEBHOOK_SECRET` | Secret for verifying incoming webhooks. |
| `timeout` | `VSPAY_TIMEOUT` | Request timeout in seconds (default `15`). |
| `retries` | `VSPAY_RETRIES` | Retries on connection failures (default `2`). |
| `retry_delay_ms` | `VSPAY_RETRY_DELAY_MS` | Base delay between retries (default `200`). |

## Conventions

- **Auth:** `Authorization: Bearer <secret>` on every request (no request signing).
- **Amounts:** integers in **minor units** (e.g. kopecks for RUB).
- **Currency:** ISO 4217 (e.g. `RUB`, `USD`).
- **Envelope:** every response contains `accepted: bool`. Failures raise typed exceptions.

## Usage

You can use the `Vspay` facade or resolve `Topvendor\Vspay\Client\VspayClient` from
the container.

```php
use Topvendor\Vspay\Facades\Vspay;

$response = Vspay::payments()->create([
    'merchant_payment_id' => 'order-1001',
    'success_redirect_url' => 'https://merchant.com/pay/ok',
    'webhook_url' => 'https://merchant.com/hooks/payment',
    'currency' => 'RUB',
    'amount' => 10000, // 100.00 RUB
    'region' => 'ru',
    'payer' => [
        'id' => 'customer_42',
        'ip' => '198.51.100.47',
        'email' => 'user@merchant.com',
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
    ],
    'instrument' => [
        'method_type' => 'sbp', // card | latam_card | sbp | crypto | uz_p2p
    ],
]);

$response->accepted();             // true
$response->providerRequestId();    // gateway external id
$response->chargeOperationUuid();  // use this to refund the charge later
$response->paymentUrl();           // hosted checkout URL (if any)
$response->provider();             // raw gateway payload
$response->toArray();              // full decoded body
```

### Card charge

```php
Vspay::payments()->create([
    'merchant_payment_id' => 'order-1002',
    'success_redirect_url' => 'https://merchant.com/pay/ok',
    'webhook_url' => 'https://merchant.com/hooks/payment',
    'currency' => 'RUB',
    'amount' => 25000,
    'payer' => ['id' => 'customer_42', 'ip' => '198.51.100.47'],
    'instrument' => [
        'method_type' => 'card',
        'card' => [
            'pan' => '4111111111111111',
            'card_holder' => 'IVAN IVANOV',
            'month' => 12,
            'year' => 2030,
            'cvv' => '123',
        ],
    ],
]);
```

### Hosted-checkout charges

Some method types are settled on the provider's hosted form: the API accepts the
charge with `status: "awaiting"` and returns a `payment_url`. Redirect the payer
there — never collect card data yourself — and wait for the signed webhook to
deliver the final state. Read the URL with `$response->paymentUrl()`.

#### LatAm card (`latam_card`)

Card PayIn across Latin America via the provider's hosted checkout. The payer's
national document and the merchant site URL are required; redirect URLs must be
on the terminal allowlist.

```php
$response = Vspay::payments()->create([
    'merchant_payment_id' => 'cl-order-7001',
    'success_redirect_url' => 'https://merchant.com/pay/ok',  // url_OK, whitelisted
    'error_redirect_url' => 'https://merchant.com/pay/fail',  // url_ERROR, whitelisted
    'currency' => 'CLP',     // CLP and PYG carry the amount in major units
    'amount' => 129000,
    'country' => 'CL',       // ISO 3166-1 alpha-2
    'document_id' => '12345678K', // payer national id (no dots/dashes)
    'merchant_url' => 'https://merchant.com',
    'expiration_minutes' => 240,  // optional, 1..1440 (default 240)
    'payer' => [
        'id' => 'customer_42',
        'ip' => '198.51.100.47',
        'email' => 'john.doe@gmail.com',
        'first_name' => 'John',
        'last_name' => 'Doe',     // or pass `name` for the full name
        'phone' => '+56987654321', // optional, E.164 (recommended for EC, BO, CO, GT)
    ],
    'instrument' => ['method_type' => 'latam_card'], // no `instrument.card`
]);

$response->statusValue(); // "awaiting"
$response->paymentUrl();  // hosted checkout URL — redirect the payer here
```

#### UZ P2P (`uz_p2p`)

P2P PayIn for Uzbekistan. The payer picks the rail (UZCARD, HUMO, Payme, Click, …)
on the hosted form unless you pin one via `instrument.payment_method`.

```php
$response = Vspay::payments()->create([
    'merchant_payment_id' => 'uz-order-5001',
    'success_redirect_url' => 'https://merchant.com/pay/ok',
    'currency' => 'UZS',
    'amount' => 10000000,
    'payer' => ['id' => 'customer_42', 'ip' => '198.51.100.47'],
    'instrument' => [
        'method_type' => 'uz_p2p',         // no `instrument.card`
        'payment_method' => 'UZ_HUMO',     // optional rail; omit to let the payer choose
    ],
]);

$response->paymentUrl(); // hosted form URL — redirect the payer here
```

### Refunds

A refund references the parent charge by `charge_operation_uuid`, which is
returned on every accepted charge response via `$response->chargeOperationUuid()`.

```php
$charge = Vspay::payments()->create([/* ... */]);

Vspay::refunds()->create([
    'refund_reference' => 'rf-1',
    'charge_operation_uuid' => $charge->chargeOperationUuid(),
    'amount' => 10000,
    'payer' => ['id' => 'customer_42', 'ip' => '198.51.100.47'],
]);
```

### Gateway operations (thin pass-through)

These use the thin dialect: `order_id`, `customer`, `payment`, `payment_data`.

```php
Vspay::gateway()->authorize([
    'order_id' => 'ord-1',
    'customer' => ['id' => 'c1', 'ip_address' => '198.51.100.47'],
    'payment' => ['currency' => 'RUB', 'amount' => 10000],
    'payment_data' => ['method_type' => 'card', 'card' => [/* ... */]],
]);

Vspay::gateway()->authorizeIncrement([...]);
Vspay::gateway()->authorizeReversal(['order_id' => 'ord-1']); // optional partial `payment`
Vspay::gateway()->capture(['order_id' => 'ord-1', 'customer' => [...], 'payment' => [...]]);
Vspay::gateway()->recurring([...]);
Vspay::gateway()->recurringCancel(['order_id' => 'ord-1']);
Vspay::gateway()->payout([...]);

// Conversion rate
Vspay::gateway()->convertRate([
    'params' => ['currency_from' => 'USD', 'currency_to' => 'RUB'],
]);

// Status — pass exactly one of order_id / request_uuid
Vspay::gateway()->status(['order_id' => 'ord-1']);
Vspay::gateway()->status(['request_uuid' => '2222...']);
```

### Hosted checkout URL

```php
$response = Vspay::checkout()->url([
    'merchant_payment_id' => 'order-1003',
    'success_redirect_url' => 'https://merchant.com/pay/ok',
    'webhook_url' => 'https://merchant.com/hooks/payment',
    'currency' => 'RUB',
    'amount' => 10000,
    'customer' => ['id' => 'customer_42'],
    'payment_data' => ['method_type' => 'sbp'],
]);

$response->checkoutUrl(); // https://pay.../payment?body=...&signature=...
```

## Error handling

All failures throw a `Topvendor\Vspay\Exceptions\VspayException` subclass:

| Exception | When |
| --- | --- |
| `UnauthorizedException` | HTTP 401 — missing/invalid terminal key |
| `ValidationException` | HTTP 422 — inspect `$e->details` |
| `RateLimitException` | HTTP 429 — see `$e->retryAfter` |
| `GatewayException` | `accepted: false` from the gateway/business rules |
| `VspayException` | base class / connection failures |

```php
use Topvendor\Vspay\Exceptions\ValidationException;
use Topvendor\Vspay\Exceptions\VspayException;

try {
    Vspay::payments()->create($payload);
} catch (ValidationException $e) {
    report($e->details);     // ['amount' => ['required'], ...]
} catch (VspayException $e) {
    report($e->errorCode);   // e.g. GATEWAY_NOT_CONFIGURED
    report($e->statusCode);  // HTTP status
    report($e->response);    // full decoded body
}
```

## Verifying webhooks

The platform signs the **raw request body** with HMAC-SHA256 and sends the hex
digest in the `X-Webhook-Signature` header.

```php
use Topvendor\Vspay\Facades\Vspay;
use Topvendor\Vspay\Webhooks\WebhookVerifier;

Route::post('/hooks/payment', function (Illuminate\Http\Request $request) {
    $valid = Vspay::webhooks()->verify(
        $request->getContent(),                                  // raw body — do not re-encode
        $request->header(WebhookVerifier::SIGNATURE_HEADER, ''), // X-Webhook-Signature
    );

    abort_unless($valid, 401);

    $payload = $request->json()->all();
    // $payload['request_uuid'], $payload['order_id'], $payload['status']

    return response()->json(['accepted' => true]);
});
```

## Versioning

This package follows [Semantic Versioning](https://semver.org). Pin with a
caret constraint:

```json
"topvendor/vspay": "^1.0"
```

The package version tracks the merchant API surface it covers:

| Package version | API coverage |
| --- | --- |
| `1.2.x` | adds hosted-checkout charge method types `latam_card` (LatAm) and `uz_p2p` (Uzbekistan) |
| `1.1.x` | adds `charge_operation_uuid` on charge responses (refund without out-of-band lookup) |
| `1.0.x` | payments, refunds, authorize/increment/reversal, capture, recurring(+cancel), payouts, convert/rate, status, checkout-url, webhook verification |

See [CHANGELOG.md](CHANGELOG.md) for details.

## Development

```bash
composer install
composer test     # Pest
composer pint     # format
composer stan     # PHPStan
```

## Releasing

1. Update `CHANGELOG.md` and bump the version notes.
2. Tag and push:
   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```
3. On first release, submit the package at https://packagist.org/packages/submit
   (`https://github.com/topvendor/vspay`) and enable the GitHub service hook so
   Packagist auto-updates on every push/tag.

## License

MIT — see [LICENSE](LICENSE).
