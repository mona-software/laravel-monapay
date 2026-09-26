<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Tests;

use MonaPay\Laravel\MonaPayServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MonaPayServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('monapay.webhook.secret', 'test-webhook-secret');
        $app['config']->set('monapay.webhook.tolerance', 300);
        $app['config']->set('monapay.webhook.path', 'monapay/webhook');
    }
}
