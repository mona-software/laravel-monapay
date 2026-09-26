<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Tests\Unit;

use MonaPay\Client;
use MonaPay\Laravel\MonaPayManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MonaPayManagerTest extends TestCase
{
    public function test_generates_order_qr_with_immutable_matching_fields(): void
    {
        $requests = [];
        $transport = static function (array $request) use (&$requests): array {
            $requests[] = $request;
            if (str_ends_with($request['url'], '/api/v1/oauth/token')) {
                return ['status' => 200, 'body' => ['success' => true, 'data' => ['access_token' => 'token']]];
            }

            return ['status' => 200, 'body' => ['success' => true, 'data' => ['qr_image_url' => 'https://example.test/qr.png']]];
        };
        $client = new Client('', '', 'secret', 'https://example.test', $transport, 30, 'client');
        $manager = new MonaPayManager($client, [
            'owner_number' => '123456789',
            'owner_type' => 'ORG',
            'merchant_id' => 'MC001',
            'terminal_id' => 'TM001',
            'virtual_account_prefix' => 'MONA',
            'beneficiary_name' => 'CONG TY MONA',
            'order_prefix' => 'DH',
        ]);

        $result = $manager->createVietQrForOrder(123, 250000, [
            'payer_email' => 'buyer@example.test',
            'orderId' => 'ATTACK',
            'amount' => 1,
        ]);

        self::assertSame('https://example.test/qr.png', $result['qr_image_url']);
        $body = json_decode($requests[1]['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('DH123', $body['orderId']);
        self::assertSame(250000, $body['amount']);
        self::assertSame('Thanh toan DH123', $body['description']);
        self::assertSame('buyer@example.test', $body['payer_email']);
    }

    public function test_rejects_an_order_id_that_cannot_be_matched_from_webhook_content(): void
    {
        $client = new Client('', '', 'secret', 'https://example.test', static fn (): array => [], 30, 'client');
        $manager = new MonaPayManager($client, []);

        $this->expectException(InvalidArgumentException::class);
        $manager->createVietQrForOrder('ABC', 250000);
    }
}
