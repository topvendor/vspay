<?php

namespace Topvendor\Vspay\Exceptions;

/**
 * Thrown when the API responds with "accepted": false (gateway/business
 * rejection) without a more specific HTTP status mapping. Examples:
 * GATEWAY_ERROR, GATEWAY_NOT_CONFIGURED, TERMINAL_BLOCKED, URL_NOT_ALLOWED,
 * REFUND_NOT_ALLOWED, OPERATION_IN_PROGRESS.
 */
class GatewayException extends VspayException {}
