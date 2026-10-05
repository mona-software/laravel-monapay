# MONA Pay for Laravel

Composer package `monapay/laravel` cho Laravel 10, 11 và 12.

**MONA Pay xác nhận chuyển khoản ngân hàng tự động (VietQR động, tài khoản ảo, webhook) — tiền vào thẳng tài khoản của bạn, MONA Pay không giữ tiền.** Xem ngân hàng đang hỗ trợ tại [monapay.vn/ngan-hang](https://monapay.vn/ngan-hang).

## Tiếng Việt

### Cài đặt

```bash
composer require monapay/laravel
php artisan vendor:publish --tag=monapay-config
```

Laravel tự phát hiện `MonaPayServiceProvider` và facade `MonaPay`.

### Cấu hình

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

Không commit `.env`. Username/password chỉ là fallback cũ; server nên dùng client ID và client secret.

### Tạo VietQR động cho đơn

```php
use MonaPay\Laravel\Facades\MonaPay;

$qr = MonaPay::createVietQrForOrder(
    orderId: $order->getKey(),
    amount: $order->total,
    options: ['payer_email' => $order->email],
);

return $qr['qr_image_url'];
```

Helper gửi `orderId=DH{id}`, số tiền VND và nội dung `Thanh toan DH{id}` qua SDK chính thức.

### Webhook và xác nhận đơn

Package đăng ký sẵn `POST /monapay/webhook`. Khai báo URL này trên MONA Pay với kiểu `HMAC_SHA256`. Middleware kiểm chữ ký trên raw body và từ chối timestamp lệch quá 5 phút.

Lắng nghe event trong `EventServiceProvider`:

```php
use MonaPay\Laravel\Events\PaymentReceived;

Event::listen(PaymentReceived::class, function (PaymentReceived $event): void {
    $orderId = (int) \MonaPay\Laravel\Support\PaymentMatcher::extractOrderId(
        (string) $event->payload['description']
    );
    $order = Order::find($orderId);

    if (!$order || !$event->matchesOrder($order->getKey(), $order->total)) {
        return;
    }

    // transaction_code phải có unique index để webhook retry không xử lý hai lần.
    Payment::firstOrCreate(
        ['transaction_code' => $event->transactionCode()],
        ['order_id' => $order->getKey(), 'amount' => $event->amount()],
    );
});
```

Ứng dụng chỉ giao hàng sau khi nội dung khớp đúng `DH{id}`, số tiền nhận không thấp hơn tổng đơn và `transaction_code` chưa được xử lý. Payload webhook là JSON phẳng, không đọc `event.data`.

Kiểm tra cấu hình route và HMAC tại máy:

```bash
php artisan monapay:test-webhook
```

### Chạy test package

```bash
composer install
composer test
```

Ảnh minh họa sẽ bổ sung tại `docs/screenshot-config.png`, `docs/screenshot-webhook.png`, `docs/screenshot-qr.png` (TODO).

## English

### Install and configure

```bash
composer require monapay/laravel
php artisan vendor:publish --tag=monapay-config
```

Set `MONAPAY_CLIENT_ID`, `MONAPAY_CLIENT_SECRET`, `MONAPAY_WEBHOOK_SECRET`, and the `MONAPAY_QR_*` variables shown above. Never commit `.env`.

### Flow

1. Call `MonaPay::createVietQrForOrder($orderId, $amount)` to create a dynamic VietQR tied to `DH{orderId}`.
2. Configure MONA Pay to send an `HMAC_SHA256` webhook to `POST /monapay/webhook`.
3. The package verifies the signature and five-minute timestamp window against the unmodified body.
4. Listen for `PaymentReceived`, require `matchesOrder()` to pass, then persist `transaction_code` uniquely before fulfilling the order.

MONA Pay automatically confirms bank transfers through dynamic VietQR, virtual accounts, and webhooks. Funds go directly to your bank account; MONA Pay does not hold funds.

Documentation: [monapay.vn](https://monapay.vn) · [API docs](https://monapay.vn/docs)

## License

MIT

**MONA Pay is part of MONA Cloud by The MONA Group.**

**MONA Pay thuộc bộ MONA Cloud của The MONA Group.**
