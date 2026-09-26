<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MonaPay\Webhook;
use Symfony\Component\HttpFoundation\Response;

final class VerifyMonaPayWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('monapay.webhook.secret', '');
        if ($secret === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Webhook MONA Pay chưa được cấu hình.',
                'data' => null,
            ], 503);
        }

        $result = Webhook::verify(
            $request->getContent(),
            $request->headers->all(),
            $secret,
            (int) config('monapay.webhook.tolerance', 300),
        );

        if (!$result['ok']) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Chữ ký webhook không hợp lệ.',
                'reason' => $result['reason'] ?? 'verification_failed',
                'data' => null,
            ], 401);
        }

        $request->attributes->set('monapay_payload', $result['payload']);

        return $next($request);
    }
}
