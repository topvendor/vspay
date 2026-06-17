# Changelog

All notable changes to `topvendor/vspay` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/topvendor/vspay/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/topvendor/vspay/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/topvendor/vspay/releases/tag/v1.0.0
