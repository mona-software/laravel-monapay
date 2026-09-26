<?php

declare(strict_types=1);

namespace MonaPay\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use MonaPay\Client;
use MonaPay\Laravel\Console\TestWebhookCommand;
use MonaPay\Laravel\Http\Middleware\VerifyMonaPayWebhook;

final class MonaPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/monapay.php', 'monapay');

        $this->app->singleton(Client::class, static function (Application $app): Client {
            $config = $app['config']->get('monapay', []);

            return new Client(
                (string) ($config['username'] ?? ''),
                (string) ($config['password'] ?? ''),
                self::nullableString($config['client_secret'] ?? null),
                (string) ($config['base_url'] ?? 'https://api.monapay.vn'),
                null,
                (int) ($config['timeout'] ?? 30),
                self::nullableString($config['client_id'] ?? null),
            );
        });

        $this->app->singleton(MonaPayManager::class, static fn (Application $app): MonaPayManager => new MonaPayManager(
            $app->make(Client::class),
            (array) $app['config']->get('monapay.qr', []),
        ));

        $this->app->alias(MonaPayManager::class, 'monapay');
    }

    public function boot(Router $router): void
    {
        $this->publishes([
            __DIR__ . '/../config/monapay.php' => config_path('monapay.php'),
        ], 'monapay-config');

        $router->aliasMiddleware('monapay.webhook', VerifyMonaPayWebhook::class);

        if ((bool) config('monapay.webhook.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/webhooks.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([TestWebhookCommand::class]);
        }
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
