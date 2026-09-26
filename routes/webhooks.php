<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use MonaPay\Laravel\Http\Controllers\WebhookController;
use MonaPay\Laravel\Http\Middleware\VerifyMonaPayWebhook;

Route::post((string) config('monapay.webhook.path', 'monapay/webhook'), WebhookController::class)
    ->middleware(array_merge(
        [VerifyMonaPayWebhook::class],
        (array) config('monapay.webhook.middleware', [])
    ))
    ->name('monapay.webhook');
