<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Event;
use MonaPay\Laravel\Events\PaymentReceived;
use MonaPay\Laravel\Tests\TestCase;

final class WebhookTest extends TestCase
{
    public function test_valid_signature_dispatches_payment_received(): void
    {
        Event::fake([PaymentReceived::class]);
        $payload = [
            'amount' => 250000,
            'description' => 'Thanh toan DH123',
            'transaction_code' => 'FT123',
            'account_number' => 'MONA00000123',
            'type' => 'income',
        ];

        $response = $this->signedPost($payload);

        $response->assertOk()->assertJson(['success' => true]);
        Event::assertDispatched(
            PaymentReceived::class,
            fn (PaymentReceived $event): bool => $event->transactionCode() === 'FT123'
                && $event->matchesOrder(123, 250000),
        );
    }

    public function test_invalid_signature_is_rejected_without_dispatching_event(): void
    {
        Event::fake([PaymentReceived::class]);
        $body = json_encode(['amount' => 250000], JSON_THROW_ON_ERROR);

        $this->call('POST', '/monapay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_MONA_TIMESTAMP' => (string) time(),
            'HTTP_X_MONA_SIGNATURE' => 'sha256=' . str_repeat('0', 64),
        ], $body)->assertUnauthorized()->assertJson(['reason' => 'invalid_signature']);

        Event::assertNotDispatched(PaymentReceived::class);
    }

    public function test_expired_timestamp_is_rejected(): void
    {
        $payload = [
            'amount' => 250000,
            'description' => 'Thanh toan DH123',
            'transaction_code' => 'FT123',
            'account_number' => 'MONA00000123',
        ];

        $this->signedPost($payload, time() - 301)
            ->assertUnauthorized()
            ->assertJson(['reason' => 'timestamp_out_of_tolerance']);
    }

    /** @param array<string, mixed> $payload */
    private function signedPost(array $payload, ?int $timestamp = null)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = $timestamp ?? time();
        $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, 'test-webhook-secret');

        return $this->call('POST', '/monapay/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_MONA_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_MONA_SIGNATURE' => $signature,
        ], $body);
    }
}
