<?php

declare(strict_types=1);

return [
    'client_id' => env('MONAPAY_CLIENT_ID'),
    'client_secret' => env('MONAPAY_CLIENT_SECRET'),
    'base_url' => env('MONAPAY_BASE_URL', 'https://api.monapay.vn'),
    'timeout' => (int) env('MONAPAY_TIMEOUT', 30),

    // Chỉ dùng làm fallback tương thích cũ. Server nên dùng client credentials.
    'username' => env('MONAPAY_USERNAME'),
    'password' => env('MONAPAY_PASSWORD'),

    'webhook' => [
        'enabled' => (bool) env('MONAPAY_WEBHOOK_ENABLED', true),
        'path' => env('MONAPAY_WEBHOOK_PATH', 'monapay/webhook'),
        'secret' => env('MONAPAY_WEBHOOK_SECRET'),
        'tolerance' => (int) env('MONAPAY_WEBHOOK_TOLERANCE', 300),
        'middleware' => [],
    ],

    'qr' => [
        'owner_number' => env('MONAPAY_QR_OWNER_NUMBER'),
        'owner_type' => env('MONAPAY_QR_OWNER_TYPE', 'ORG'),
        'merchant_id' => env('MONAPAY_QR_MERCHANT_ID'),
        'terminal_id' => env('MONAPAY_QR_TERMINAL_ID'),
        'virtual_account_prefix' => env('MONAPAY_QR_VIRTUAL_ACCOUNT_PREFIX'),
        'beneficiary_name' => env('MONAPAY_QR_BENEFICIARY_NAME'),
        'order_prefix' => env('MONAPAY_ORDER_PREFIX', 'DH'),
    ],
];
