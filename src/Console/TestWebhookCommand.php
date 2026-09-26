<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

final class TestWebhookCommand extends Command
{
    protected $signature = 'monapay:test-webhook';

    protected $description = 'Kiểm tra route và chữ ký webhook MONA Pay ngay trong ứng dụng';

    public function handle(Kernel $kernel): int
    {
        $secret = (string) config('monapay.webhook.secret', '');
        if ($secret === '') {
            $this->error('Thiếu MONAPAY_WEBHOOK_SECRET.');

            return self::FAILURE;
        }
        if (!(bool) config('monapay.webhook.enabled', true)) {
            $this->error('Webhook đang tắt bởi MONAPAY_WEBHOOK_ENABLED.');

            return self::FAILURE;
        }

        $body = json_encode([
            'amount' => 10000,
            'description' => 'MONA Pay webhook test',
            'transaction_code' => 'DUMMY123',
            'account_number' => 'TEST',
            'type' => 'income',
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
        $request = Request::create(
            '/' . ltrim((string) config('monapay.webhook.path', 'monapay/webhook'), '/'),
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_MONA_TIMESTAMP' => $timestamp,
                'HTTP_X_MONA_SIGNATURE' => $signature,
            ],
            $body,
        );

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        if ($response->getStatusCode() !== 200) {
            $this->error('Webhook trả HTTP ' . $response->getStatusCode() . ': ' . $response->getContent());

            return self::FAILURE;
        }

        $this->info('OK: route webhook và HMAC-SHA256 hoạt động.');

        return self::SUCCESS;
    }
}
