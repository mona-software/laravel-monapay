# MONA Pay for Laravel

Composer package `monapay/laravel` that lets Laravel 10, 11 and 12 applications create dynamic VietQR codes for orders and receive signed MONA Pay bank-transfer webhooks as a Laravel event.

## Requirements

- PHP 8.1 or later
- Laravel 10, 11 or 12
- [`monapay/php-sdk`](https://github.com/mona-software/monapay-php) `^0.4.0` (installed automatically)
- A MONA Pay account with API client credentials and a webhook secret

## Install

```bash
composer require monapay/laravel
php artisan vendor:publish --tag=monapay-config
```

Laravel package discovery registers `MonaPay\Laravel\MonaPayServiceProvider` and the `MonaPay` facade. The publish command copies the config to `config/monapay.php`.

## Configuration

Add the credentials to `.env` (never commit this file):

```dotenv
MONAPAY_CLIENT_ID=client-id
MONAPAY_CLIENT_SECRET=client-secret
MONAPAY_WEBHOOK_SECRET=webhook-secret
MONAPAY_BASE_URL=https://api.monapay.vn

MONAPAY_QR_OWNER_NUMBER=123456789
MONAPAY_QR_OWNER_TYPE=ORG
MONAPAY_QR_MERCHANT_ID=MC00012345
MONAPAY_QR_TERMINAL_ID=TM0001
MONAPAY_QR_VIRTUAL_ACCOUNT_PREFIX=MONA
MONAPAY_QR_BENEFICIARY_NAME="CONG TY MONA"
```

Optional variables and their defaults:

| Variable | Default | Purpose |
| --- | --- | --- |
| `MONAPAY_TIMEOUT` | `30` | HTTP timeout in seconds |
| `MONAPAY_WEBHOOK_ENABLED` | `true` | Register the webhook route |
| `MONAPAY_WEBHOOK_PATH` | `monapay/webhook` | Webhook route path |
| `MONAPAY_WEBHOOK_TOLERANCE` | `300` | Maximum timestamp skew in seconds |
| `MONAPAY_ORDER_PREFIX` | `DH` | Prefix used to build the order code |
| `MONAPAY_USERNAME`, `MONAPAY_PASSWORD` | – | Legacy fallback only; use client credentials on servers |

Extra middleware for the webhook route can be added in `config/monapay.php` under `webhook.middleware`.

## Usage

### Create a dynamic VietQR for an order

```php
use MonaPay\Laravel\Facades\MonaPay;

$qr = MonaPay::createVietQrForOrder(
    orderId: $order->getKey(),
    amount: $order->total,
);
```

The helper sends `orderId=DH{id}`, the amount in VND and the transfer memo `Thanh toan DH{id}` through the official PHP SDK, and returns the API response. The order ID must be a positive integer and the amount must be between 1 and 1,000,000,000 VND. Any extra fields passed in `options` are merged into the QR request body; `orderId`, `amount` and `description` cannot be overridden. For other API calls, use `MonaPay::client()` to get the underlying `MonaPay\Client`.

### Receive webhooks

The package registers `POST /monapay/webhook` (route name `monapay.webhook`). Add this URL in MONA Pay with signature type `HMAC_SHA256`. The middleware verifies the `X-Mona-Signature` header against the raw request body and `X-Mona-Timestamp`, and rejects requests outside the timestamp tolerance (5 minutes by default). Valid incoming transactions dispatch `MonaPay\Laravel\Events\PaymentReceived`.

```php
use Illuminate\Support\Facades\Event;
use MonaPay\Laravel\Events\PaymentReceived;
use MonaPay\Laravel\Support\PaymentMatcher;

Event::listen(PaymentReceived::class, function (PaymentReceived $event): void {
    $orderId = PaymentMatcher::extractOrderId((string) $event->payload['description']);
    $order = $orderId !== null ? Order::find($orderId) : null;

    if (!$order || !$event->matchesOrder($order->getKey(), $order->total)) {
        return;
    }

    // transaction_code needs a unique index so webhook retries are not processed twice.
    Payment::firstOrCreate(
        ['transaction_code' => $event->transactionCode()],
        ['order_id' => $order->getKey(), 'amount' => $event->amount()],
    );
});
```

Only fulfil an order when the memo contains exactly `DH{id}`, the received amount is not lower than the order total, and the `transaction_code` has not been processed before. The webhook payload is a flat JSON object; there is no `event.data` wrapper. If you change `MONAPAY_ORDER_PREFIX`, pass the same prefix to `extractOrderId()` and `matchesOrder()`, which default to `DH`.

To check the route and HMAC setup locally:

```bash
php artisan monapay:test-webhook
```

API reference: [monapay.vn/docs](https://monapay.vn/docs).

## Development

```bash
composer install
composer test
```

CI runs PHPUnit against Laravel 10 (PHP 8.1), 11 and 12 (PHP 8.2); see `.github/workflows/tests.yml`.

## License

MIT. See [LICENSE](LICENSE).

**MONA Pay is part of MONA Cloud by The MONA Group.**
