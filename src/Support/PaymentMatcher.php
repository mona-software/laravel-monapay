<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Support;

final class PaymentMatcher
{
    /** @param array<string, mixed> $payload */
    public static function matchesOrder(
        array $payload,
        string|int $orderId,
        int|float $expectedAmount,
        string $prefix = 'DH',
    ): bool {
        if (!isset($payload['amount'], $payload['description']) || !is_numeric($payload['amount'])) {
            return false;
        }

        $expectedAmount = (int) round($expectedAmount);
        if ($expectedAmount <= 0 || (int) round((float) $payload['amount']) < $expectedAmount) {
            return false;
        }

        $matchedOrderId = self::extractOrderId((string) $payload['description'], $prefix);

        return $matchedOrderId !== null && hash_equals((string) $orderId, $matchedOrderId);
    }

    public static function extractOrderId(string $description, string $prefix = 'DH'): ?string
    {
        $prefix = preg_quote($prefix, '/');
        if (preg_match('/(?:^|[^A-Z0-9])' . $prefix . '\s*#?\s*([0-9]+)(?:$|[^0-9])/i', $description, $matches) !== 1) {
            return null;
        }

        $orderId = ltrim($matches[1], '0');

        return $orderId === '' ? null : $orderId;
    }
}
