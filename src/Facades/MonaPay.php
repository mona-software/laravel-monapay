<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use MonaPay\Client;

/**
 * @method static Client client()
 * @method static mixed createVietQrForOrder(string|int $orderId, int|float $amount, array<string, mixed> $options = [])
 *
 * @see \MonaPay\Laravel\MonaPayManager
 */
final class MonaPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'monapay';
    }
}
