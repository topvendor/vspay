<?php

namespace Topvendor\Vspay\Exceptions;

use RuntimeException;
use Throwable;

class VspayException extends RuntimeException
{
    /**
     * Machine-readable error code returned by the API (error.code), if any.
     */
    public readonly ?string $errorCode;

    /**
     * HTTP status code of the response, if the error originated from one.
     */
    public readonly ?int $statusCode;

    /**
     * Additional error details (error.details), if any.
     *
     * @var array<string, mixed>
     */
    public readonly array $details;

    /**
     * Decoded response body, if available.
     *
     * @var array<string, mixed>
     */
    public readonly array $response;

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        string $message,
        ?string $errorCode = null,
        ?int $statusCode = null,
        array $details = [],
        array $response = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);

        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->details = $details;
        $this->response = $response;
    }
}
