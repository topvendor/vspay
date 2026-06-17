<?php

namespace Topvendor\Vspay\Exceptions;

/**
 * Thrown on HTTP 422 (VALIDATION_ERROR). Inspect $details for field errors.
 */
class ValidationException extends VspayException {}
