# Changelog

All notable changes to `topvendor/vspay` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.0.0] - 2026-06-25

### Changed
- **BREAKING:** `Gateway::status()` now queries the processing platform, not the upstream gateway.
  - Request: use `merchant_payment_id` (same id as `Payments::create` / `refund_reference` on refunds),
    optionally `subscription_id` or `refund_id` (+ `merchant_payment_id` of the charge).
  - Removed: `order_id` and `request_uuid` as status lookup keys.
  - Response: normalized `status` / `status_label` / `merchant_payment_id` / `operation_type` /
    `charge_operation_uuid`; no longer includes raw `provider` gateway payload.
  - Not found → HTTP 404, `error.code` = `OPERATION_NOT_FOUND`.

### Added
- `Response::merchantPaymentId()`, `operationType()`, `subscriptionId()` for `/status` payloads.

## [2.2.0] - 2026-06-25

### Added
- Merchant return redirect helpers for hosted payments (success / error / pending):
  - `ReturnQuery` — parse `vspay_status`, `merchant_payment_id` and
    `operation_uuid` from the payer's return URL (`fromArray`, `fromRequest`,
    `isSuccess` / `isFailed` / `isPending`).
  - `MerchantRedirect::withStatus()` — build redirect URLs with the same query
    contract (e.g. default `error_redirect_url` to
    `success_redirect_url?vspay_status=failed`).
- README section on handling payer return redirects and optional
  `error_redirect_url` for all hosted payment methods.

## [2.1.0] - 2026-06-23

### Added
- `VspayClient::uz()` resource for merchant-hosted UZ checkout (ehotpay proxy):
  - `Uz::createPayInOrder()` → `POST /api/v1/uz/create-pay-in-order`
  - `Uz::getPayInOrderByMerchantId()` → `GET /api/v1/uz/get-pay-in-order-by-merchant-id/{id}`
- `VspayClient::postProvider()` / `getProvider()` for provider-shaped JSON responses
  (HTTP 2xx without the standard `{accepted: true}` envelope).
- `Response` helpers for ehotpay proxy payloads: `providerStatus()`, `statusLabel()`,
  `paymentsDetails()`, `uid()`, `merchantOrderId()`.

## [2.0.0] - 2026-06-23

### Changed
- **BREAKING (API contract).** The "method block" entitlement concept was renamed
  to **scope** across the merchant API. The error codes returned in
  `error.code` were renamed accordingly:
  - `METHOD_BLOCK_NOT_ENABLED` → `SCOPE_NOT_ENABLED` (HTTP 403)
  - `METHOD_BLOCK_NOT_ROUTED` → `SCOPE_NOT_ROUTED` (HTTP 422)

  Exception mapping is unchanged (403 → `GatewayException`, 422 →
  `ValidationException`). Only code consumers that match on the literal
  `error.code` string need to update from `METHOD_BLOCK_*` to `SCOPE_*`.

## [1.2.1] - 2026-06-19

### Changed
- Documentation only: per-method request formats for methods other than SBP and
  cards (e.g. regional hosted-checkout PayIns) are no longer enumerated here.
  Their `instrument.method_type` values and required fields are documented in the
  merchant cabinet, per terminal. The SDK still passes any payload through
  unchanged, so no upgrade is needed to use a method your terminal supports.
- The Uzbekistan P2P charge method type was renamed `uz_p2p` → `uz` in the API.

## [1.2.0] - 2026-06-19

### Added
- Hosted-checkout charge method types on `Payments::create`:
  - `latam_card` — LatAm card PayIn (Zippy). New request fields `error_redirect_url`,
    `country`, `document_id`, `merchant_url`, `expiration_minutes`, `payer.name` and
    `payer.phone`; the charge is accepted with `status: "awaiting"` and a `payment_url`.
  - `uz_p2p` — Uzbekistan P2P PayIn (ehotpay), with optional `instrument.payment_method`
    to pin the payment rail.
- README examples for both hosted-checkout method types.

These are backward-compatible additions (the new fields are required only for the
respective method type); the response shape is unchanged and already exposed via
`Response::paymentUrl()` / `Response::statusValue()`.

## [1.1.0] - 2026-06-17

### Added
- `Response::chargeOperationUuid()` exposing the `charge_operation_uuid` now
  returned by the API on accepted charge responses, removing the need to look
  up the parent operation out of band before issuing a refund.

## [1.0.0] - 2026-06-17

### Added
- Initial release of the VSPay Laravel SDK.
- `VspayClient` with Bearer authentication, JSON envelope parsing, idempotent retries on connection failures, and typed exceptions.
- Resources covering all `/api/v1` endpoints:
  - `Payments::create` (`POST /payments`)
  - `Refunds::create` (`POST /refunds`)
  - `Gateway::authorize`, `authorizeIncrement`, `authorizeReversal`, `capture`, `recurring`, `recurringCancel`, `payout`, `convertRate`, `status`
  - `Checkout::url` (`POST /checkout-url`)
- `WebhookVerifier` for `X-Webhook-Signature` (HMAC-SHA256) verification.
- `Vspay` facade and auto-discovered service provider.
- Publishable `config/vspay.php` driven entirely by environment variables.

[Unreleased]: https://github.com/topvendor/vspay/compare/v2.2.0...HEAD
[2.2.0]: https://github.com/topvendor/vspay/compare/v2.1.0...v2.2.0
[1.2.1]: https://github.com/topvendor/vspay/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/topvendor/vspay/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/topvendor/vspay/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/topvendor/vspay/releases/tag/v1.0.0
