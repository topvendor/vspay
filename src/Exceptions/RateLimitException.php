<?php

namespace Topvendor\Vspay\Exceptions;

/**
 * Thrown on HTTP 429 (rate limit exceeded for the terminal API key).
 */
class RateLimitException extends VspayException
{
    /**
     * Seconds to wait before retrying, from the Retry-After header, if present.
     */
    public ?int $retryAfter = null;
}
