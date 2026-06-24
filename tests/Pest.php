<?php

use Topvendor\Vspay\Client\VspayClient;
use Topvendor\Vspay\Tests\TestCase;

uses(TestCase::class)->in('Feature');

function client(): VspayClient
{
    return app(VspayClient::class);
}
