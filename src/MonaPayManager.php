<?php

declare(strict_types=1);

namespace MonaPay\Laravel;

use InvalidArgumentException;
use LogicException;
use MonaPay\Client;

final class MonaPayManager
{
    /** @param array<string, mixed> $qrConfig */
    public function __construct(
        private readonly Client $client,
        private readonly array $qrConfig,
    ) {
    }

    public function client(): Client
    {
        return $this->client;
    }

    /**
     * Tạo VietQR động cho một đơn. Mã đơn, số tiền và nội dung luôn được khóa
     * theo đơn để webhook có thể đối chiếu an toàn.
     *
     * @param array<string, mixed> $options Các trường QR tùy chọn như payer_email, additionalInfo.
     * @return mixed
     */
    public function createVietQrForOrder(string|int $orderId, int|float $amount, array $options = []): mixed
    {
        $orderId = trim((string) $orderId);
        $amount = (int) round($amount);

        if (preg_match('/^[1-9][0-9]*$/', $orderId) !== 1) {
            throw new InvalidArgumentException('Mã đơn phải là số nguyên dương.');
        }
        if ($amount <= 0 || $amount > 1_000_000_000) {
            throw new InvalidArgumentException('Số tiền phải từ 1 đến 1.000.000.000 VND.');
        }

        $required = [
            'owner_number',
            'owner_type',
            'merchant_id',
            'terminal_id',
            'virtual_account_prefix',
            'beneficiary_name',
        ];
        $missing = array_values(array_filter($required, fn (string $key): bool => empty($this->qrConfig[$key])));
        if ($missing !== []) {
            throw new LogicException('Thiếu cấu hình MONA Pay QR: ' . implode(', ', $missing));
        }

        $orderCode = (string) ($this->qrConfig['order_prefix'] ?? 'DH') . $orderId;
        $payload = array_merge([
            'ownerNumber' => (string) $this->qrConfig['owner_number'],
            'ownerType' => (string) $this->qrConfig['owner_type'],
            'merchantId' => (string) $this->qrConfig['merchant_id'],
            'terminalId' => (string) $this->qrConfig['terminal_id'],
            'virtualAccountPrefix' => (string) $this->qrConfig['virtual_account_prefix'],
            'beneficiaryName' => (string) $this->qrConfig['beneficiary_name'],
        ], $options, [
            'orderId' => $orderCode,
            'amount' => $amount,
            'description' => 'Thanh toan ' . $orderCode,
        ]);

        return $this->client->qr->generate($payload);
    }
}
