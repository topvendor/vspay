<?php

namespace Topvendor\Vspay\Resources;

use Topvendor\Vspay\Client\VspayClient;

abstract class Resource
{
    public function __construct(
        protected readonly VspayClient $client,
    ) {}
}
