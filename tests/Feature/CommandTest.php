<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Tests\Feature;

use MonaPay\Laravel\Tests\TestCase;

final class CommandTest extends TestCase
{
    public function test_webhook_command_checks_the_local_route(): void
    {
        $this->artisan('monapay:test-webhook')
            ->expectsOutput('OK: route webhook và HMAC-SHA256 hoạt động.')
            ->assertSuccessful();
    }
}
