<?php

namespace Topvendor\Vspay\Exceptions;

/**
 * Thrown on HTTP 401 (UNAUTHORIZED) - missing or invalid terminal API key.
 */
class UnauthorizedException extends VspayException {}
