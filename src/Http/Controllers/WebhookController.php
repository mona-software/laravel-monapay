<?php

declare(strict_types=1);

namespace MonaPay\Laravel\Http\Controllers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MonaPay\Laravel\Events\PaymentReceived;

final class WebhookController
{
    public function __construct(private readonly Dispatcher $events)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->attributes->get('monapay_payload');
        if (!is_array($payload)) {
            return $this->response(400, false, 'Payload webhook không hợp lệ.');
        }

        if (($payload['transaction_code'] ?? null) === 'DUMMY123') {
            return $this->response(200, true, 'Webhook thử hợp lệ.');
        }

        if (!$this->isIncomingTransaction($payload)) {
            return $this->response(400, false, 'Giao dịch tiền vào không hợp lệ.');
        }

        $this->events->dispatch(new PaymentReceived($payload));

        return $this->response(200, true, 'Đã nhận webhook.');
    }

    /** @param array<string, mixed> $payload */
    private function isIncomingTransaction(array $payload): bool
    {
        foreach (['amount', 'description', 'transaction_code', 'account_number'] as $field) {
            if (!array_key_exists($field, $payload)) {
                return false;
            }
        }

        return is_numeric($payload['amount'])
            && (float) $payload['amount'] > 0
            && trim((string) $payload['transaction_code']) !== ''
            && trim((string) $payload['account_number']) !== ''
            && (!isset($payload['type']) || $payload['type'] === 'income');
    }

    private function response(int $status, bool $success, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => $success,
            'message' => $message,
            'data' => null,
        ], $status);
    }
}
