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

### Installing without Packagist (VCS repository)

If the package is not published on Packagist (or you prefer to pull it straight
from GitHub), point Composer at the repository via a `repositories` entry in your
project's `composer.json`, then require it as usual:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/topvendor/vspay"
        }
    ],
    "require": {
        "topvendor/vspay": "^1.2"
    }
}
```

Then install / update:

```bash
composer update topvendor/vspay
```

Composer reads the repository's Git tags, so version constraints like `^1.2`
resolve to the latest matching release (e.g. `v1.2.1`). Pull future updates the
same way you would for any package — `composer update topvendor/vspay` — as soon
as a new tag is pushed to GitHub.

Notes:

- The `repositories` block must be added **before** running `composer require`;
  without it Composer cannot locate `topvendor/vspay`.
- For a **private** mirror of this repository, Composer will authenticate over
  SSH (`"url": "git@github.com:topvendor/vspay.git"`) or prompt for a GitHub
  token. Unauthenticated GitHub API calls are rate-limited, so configure a token
  via `composer config --global github-oauth.github.com <token>` if you hit limits.
- To track a branch instead of a release tag, require `dev-main` (requires
  `"minimum-stability": "dev"` in your project); prefer tagged versions for
  production.

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
        'method_type' => 'sbp', // 'sbp' or 'card' — see "Other payment methods" below
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

### Other payment methods

Beyond SBP and cards, a terminal may support additional payment methods (for
example regional hosted-checkout PayIns). Those are settled on the provider's
hosted form: the charge is accepted with `status: "awaiting"` and a `payment_url`
— redirect the payer there and wait for the signed webhook.

```php
$response->statusValue(); // "awaiting"
$response->paymentUrl();  // hosted checkout URL — redirect the payer here
```

The exact `instrument.method_type` values and the request fields each one
requires depend on what is enabled for your terminal, and are documented in your
**merchant cabinet → Terminals**, under the relevant terminal. This SDK passes
the payload through unchanged, so no SDK upgrade is needed to use a method that
your terminal already supports.

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

// Status — processing operation status (not raw gateway data)
$response = Vspay::gateway()->status(['merchant_payment_id' => 'order-1001']);
$response->statusValue();        // pending | in_progress | succeeded | failed
$response->statusLabel();        // e.g. "Успех"
$response->merchantPaymentId(); // echoes your id
$response->operationType();      // charge | refund
$response->chargeOperationUuid();

// SBP subscriptions: subscription-only or paired with merchant_payment_id
Vspay::gateway()->status(['subscription_id' => 'sub-1']);
Vspay::gateway()->status(['subscription_id' => 'sub-1', 'merchant_payment_id' => 'order-1001']);
```

### UZ merchant form (ehotpay proxy)

When you host the payment UI yourself, use the ehotpay-shaped proxy endpoints
instead of `Payments::create` with `instrument.method_type: uz` (which returns
our hosted `payment_url`).

```php
$order = Vspay::uz()->createPayInOrder([
    'merchant_order_id' => 'uz-order-5001',
    'amount' => '100000.00',
    'currency' => 'UZS',
    'pay_in_details' => ['payment_method' => 'UZ_UZCARD'], // or UZ_HUMO
    'webhook_url' => 'https://merchant.com/hooks/payment',
    'payer' => ['id' => 'customer_42', 'ip' => '198.51.100.47'],
]);

$order->paymentsDetails();      // trader requisites from ehotpay
$order->chargeOperationUuid();  // for refunds

$status = Vspay::uz()->getPayInOrderByMerchantId('uz-order-5001');
$status->statusLabel();         // e.g. "succeeded"
```

Errors such as `FLOW_CONFLICT` (409) are thrown as `GatewayException`.

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

### Payer return redirects (hosted payments)

After a hosted payment (SBP, crypto, UZ, LatAm card, etc.) the payer is sent
back to your store. Pass `success_redirect_url` on every charge; optionally pass
`error_redirect_url` for a dedicated failure page. If you omit
`error_redirect_url`, the platform defaults to `success_redirect_url` with
`vspay_status=failed`.

On return, VSPay appends query parameters (the signed webhook remains the source
of truth — use the redirect only for UX):

| Query param | Values / meaning |
| --- | --- |
| `vspay_status` | `success`, `failed`, or `pending` (payment still settling) |
| `merchant_payment_id` | Your order id from the charge request |
| `operation_uuid` | VSPay charge operation uuid |

Handle the return in your Laravel route:

```php
use Illuminate\Http\Request;
use Topvendor\Vspay\Redirects\ReturnQuery;

Route::get('/pay/return', function (Request $request) {
    $return = ReturnQuery::fromRequest($request);

    if ($return->isSuccess()) {
        return view('pay.ok', ['orderId' => $return->merchantPaymentId]);
    }

    if ($return->isFailed()) {
        return view('pay.fail', ['orderId' => $return->merchantPaymentId]);
    }

    if ($return->isPending()) {
        return view('pay.pending', ['orderId' => $return->merchantPaymentId]);
    }

    abort(400);
});
```

To mirror the platform default for `error_redirect_url` when creating a charge:

```php
use Topvendor\Vspay\Redirects\MerchantRedirect;
use Topvendor\Vspay\Redirects\ReturnQuery;

$errorRedirect = MerchantRedirect::withStatus(
    'https://merchant.com/pay/ok',
    ReturnQuery::STATUS_FAILED,
);
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
| `3.0.x` | `/status` returns processing operation status (`merchant_payment_id` lookup; no `provider` passthrough) |
| `2.2.x` | payer return redirects (`vspay_status` query contract, optional `error_redirect_url`) |
| `2.1.x` | UZ merchant-hosted checkout via `Vspay::uz()` (ehotpay proxy endpoints) |
| `2.0.x` | scope rename (`SCOPE_NOT_ENABLED` / `SCOPE_NOT_ROUTED`) |
| `1.2.x` | hosted-checkout charges accepted as `status: "awaiting"` + `payment_url`; per-method request formats documented in the merchant cabinet |
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
