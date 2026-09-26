<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Tests\Unit;

use MonaPay\Laravel\Support\PaymentMatcher;
use PHPUnit\Framework\TestCase;

final class PaymentMatcherTest extends TestCase
{
    public function test_matches_order_content_and_sufficient_amount(): void
    {
        $payload = ['amount' => 250000, 'description' => 'Thanh toan DH123 tai ACB'];

        self::assertTrue(PaymentMatcher::matchesOrder($payload, 123, 250000));
        self::assertTrue(PaymentMatcher::matchesOrder($payload, 123, 200000));
    }

    public function test_rejects_underpayment_or_wrong_order_content(): void
    {
        self::assertFalse(PaymentMatcher::matchesOrder(
            ['amount' => 249999, 'description' => 'Thanh toan DH123'],
            123,
            250000,
        ));
        self::assertFalse(PaymentMatcher::matchesOrder(
            ['amount' => 250000, 'description' => 'Thanh toan DH1234'],
            123,
            250000,
        ));
        self::assertFalse(PaymentMatcher::matchesOrder(
            ['amount' => 250000, 'description' => 'ABCDH123'],
            123,
            250000,
        ));
    }
}
