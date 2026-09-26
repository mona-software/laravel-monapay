# AGENTS.md

Gói này tích hợp MONA Pay vào Laravel 10/11/12 và dùng `monapay/php-sdk`; không tự viết HTTP client hoặc bịa endpoint.

- Nguồn API: `https://monapay.vn/openapi.json` và `https://monapay.vn/llms.txt`.
- Webhook phải kiểm trên raw body với `X-Mona-Timestamp`, `X-Mona-Signature`; cửa sổ mặc định 300 giây.
- Payload giao dịch là JSON phẳng. Chống xử lý trùng bằng `transaction_code` trong ứng dụng nghe event.
- Chỉ xác nhận đơn khi `PaymentReceived::matchesOrder($orderId, $amount)` trả `true`.
- Không ghi secret vào code/test/tài liệu. Chạy `composer test` trước khi gửi thay đổi.
