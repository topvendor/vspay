<?php

namespace Topvendor\Vspay;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Topvendor\Vspay\Client\Config;
use Topvendor\Vspay\Client\VspayClient;

final class VspayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vspay.php', 'vspay');

        $this->app->singleton(Config::class, function (Application $app): Config {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('vspay', []);

            return Config::fromArray($config);
        });

        $this->app->singleton(VspayClient::class, function (Application $app): VspayClient {
            return new VspayClient(
                $app->make(Config::class),
                $app->make(HttpFactory::class),
            );
        });

        $this->app->alias(VspayClient::class, 'vspay');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/vspay.php' => $this->app->configPath('vspay.php'),
            ], 'vspay-config');
        }
    }
}
