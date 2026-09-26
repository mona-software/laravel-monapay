<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Events;

use MonaPay\Laravel\Support\PaymentMatcher;

final class PaymentReceived
{
    /** @param array<string, mixed> $payload */
    public function __construct(public readonly array $payload)
    {
    }

    public function transactionCode(): string
    {
        return (string) $this->payload['transaction_code'];
    }

    public function amount(): int
    {
        return (int) round((float) $this->payload['amount']);
    }

    public function matchesOrder(string|int $orderId, int|float $expectedAmount, string $prefix = 'DH'): bool
    {
        return PaymentMatcher::matchesOrder($this->payload, $orderId, $expectedAmount, $prefix);
    }
}
