<?php

namespace Topvendor\Vspay\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Topvendor\Vspay\VspayServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            VspayServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('vspay.base_url', 'https://api.example.test');
        $app['config']->set('vspay.secret', 'test-terminal-secret');
        $app['config']->set('vspay.webhook_secret', 'test-webhook-secret');
        $app['config']->set('vspay.retries', 1);
        $app['config']->set('vspay.retry_delay_ms', 0);
    }
}
