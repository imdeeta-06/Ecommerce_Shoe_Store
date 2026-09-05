<?php

namespace App\Services;

use RuntimeException;

class PayPalService {
    private array $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/paypal.php';
    }

    public function isConfigured(): bool {
        $configured = $this->config['client_id'] !== ''
            && $this->config['client_secret'] !== ''
            && $this->config['currency'] === 'USD'
            && (float)$this->config['vnd_per_usd'] > 0;
        if (!$configured) {
            return false;
        }

        if ($this->config['mode'] === 'live') {
            $publicUrl = trim((string)(\App\Core\App::env('APP_PUBLIC_URL') ?: ''));
            $forceHttps = in_array(strtolower(trim((string)\App\Core\App::env('FORCE_HTTPS'))), ['1', 'true', 'yes', 'on'], true);
            return preg_match('#^https://[^/]+(?:/.*)?$#i', $publicUrl) === 1
                && $forceHttps
                && !empty($this->config['live_credentials_rotated'])
                && $this->config['webhook_id'] !== '';
        }

        return true;
    }

    public function checkoutConfig(): array {
        return [
            'enabled' => $this->isConfigured(),
            'mode' => $this->config['mode'],
            'currency' => $this->config['currency'],
            'vnd_per_usd' => (float)$this->config['vnd_per_usd'],
        ];
    }

    public function isWebhookConfigured(): bool {
        return $this->isConfigured() && $this->config['webhook_id'] !== '';
    }

    public function quote(float $amountVnd): array {
        if ((float)$this->config['vnd_per_usd'] <= 0) {
            throw new RuntimeException('Chưa cấu hình tỷ giá PayPal VND/USD.');
        }

        $amountVnd = round(max(0, $amountVnd), 2);
        $providerAmount = round($amountVnd / (float)$this->config['vnd_per_usd'], 2, PHP_ROUND_HALF_UP);
        if ($providerAmount <= 0) {
            throw new RuntimeException('Số tiền quy đổi PayPal phải lớn hơn 0 USD.');
        }

        return [
            'currency' => 'USD',
            'value' => number_format($providerAmount, 2, '.', ''),
            'exchange_rate' => (float)$this->config['vnd_per_usd'],
            'amount_vnd' => $amountVnd,
        ];
    }

    public function createOrder(
        int $localOrderId,
        string $orderCode,
        float $amountVnd,
        string $returnUrl,
        string $cancelUrl
    ): array {
        $this->assertConfigured();
        $quote = $this->quote($amountVnd);
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'LIENHOA-' . $localOrderId,
                'custom_id' => (string)$localOrderId,
                'invoice_id' => $orderCode,
                'description' => 'Don hang ' . $orderCode . ' tai Lien Hoa',
                'amount' => [
                    'currency_code' => $quote['currency'],
                    'value' => $quote['value'],
                ],
            ]],
            'application_context' => [
                'brand_name' => 'Lien Hoa',
                'landing_page' => 'LOGIN',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ];

        $result = $this->request(
            'POST',
            '/v2/checkout/orders',
            $payload,
            'create-' . hash('sha256', $orderCode)
        );
        $approvalUrl = '';
        foreach (($result['links'] ?? []) as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                $approvalUrl = (string)($link['href'] ?? '');
                break;
            }
        }
        if (empty($result['id']) || $approvalUrl === '') {
            throw new RuntimeException('PayPal không trả về liên kết xác nhận thanh toán.');
        }

        return [
            'id' => (string)$result['id'],
            'status' => (string)($result['status'] ?? ''),
            'approval_url' => $approvalUrl,
            'currency' => $quote['currency'],
            'value' => $quote['value'],
            'exchange_rate' => $quote['exchange_rate'],
        ];
    }

    public function captureOrder(string $paypalOrderId): array {
        $this->assertConfigured();
        if (!preg_match('/^[A-Z0-9]{8,30}$/i', $paypalOrderId)) {
            throw new RuntimeException('Mã đơn PayPal không hợp lệ.');
        }

        return $this->request(
            'POST',
            '/v2/checkout/orders/' . rawurlencode($paypalOrderId) . '/capture',
            new \stdClass(),
            'capture-' . hash('sha256', $paypalOrderId)
        );
    }

    public function showOrder(string $paypalOrderId): array {
        $this->assertConfigured();
        if (!preg_match('/^[A-Z0-9]{8,30}$/i', $paypalOrderId)) {
            throw new RuntimeException('Mã đơn PayPal không hợp lệ.');
        }
        return $this->request('GET', '/v2/checkout/orders/' . rawurlencode($paypalOrderId), null, 'show-' . hash('sha256', $paypalOrderId));
    }

    public function refundCapture(
        string $captureId,
        float $amount,
        string $currency,
        string $merchantReference,
        string $note = ''
    ): array {
        $this->assertConfigured();
        if (!preg_match('/^[A-Z0-9]{8,40}$/i', $captureId) || $amount <= 0 || !preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new RuntimeException('Dữ liệu hoàn tiền PayPal không hợp lệ.');
        }
        $payload = [
            'amount' => ['value' => number_format($amount, 2, '.', ''), 'currency_code' => $currency],
            'invoice_id' => substr($merchantReference, 0, 127),
        ];
        if (trim($note) !== '') {
            $payload['note_to_payer'] = mb_substr(trim($note), 0, 255);
        }
        return $this->request(
            'POST',
            '/v2/payments/captures/' . rawurlencode($captureId) . '/refund',
            $payload,
            'refund-' . hash('sha256', $merchantReference)
        );
    }

    public function verifyWebhookSignature(array $headers, array $event): bool {
        if (!$this->isWebhookConfigured()) {
            throw new RuntimeException('Chưa cấu hình PAYPAL_WEBHOOK_ID.');
        }
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower((string)$name)] = trim((string)$value);
        }
        $required = [
            'paypal-auth-algo', 'paypal-cert-url', 'paypal-transmission-id',
            'paypal-transmission-sig', 'paypal-transmission-time'
        ];
        foreach ($required as $name) {
            if (($normalized[$name] ?? '') === '') {
                return false;
            }
        }
        $result = $this->request('POST', '/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $normalized['paypal-auth-algo'],
            'cert_url' => $normalized['paypal-cert-url'],
            'transmission_id' => $normalized['paypal-transmission-id'],
            'transmission_sig' => $normalized['paypal-transmission-sig'],
            'transmission_time' => $normalized['paypal-transmission-time'],
            'webhook_id' => $this->config['webhook_id'],
            'webhook_event' => $event,
        ], 'verify-' . hash('sha256', $normalized['paypal-transmission-id']));
        return strtoupper((string)($result['verification_status'] ?? '')) === 'SUCCESS';
    }

    private function assertConfigured(): void {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PayPal chưa được cấu hình đầy đủ; chế độ Live bắt buộc khóa mới đã được xác nhận, APP_PUBLIC_URL HTTPS, FORCE_HTTPS=true và PAYPAL_WEBHOOK_ID hợp lệ.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL chưa được bật nên không thể kết nối PayPal.');
        }
    }

    private function accessToken(): string {
        $curl = curl_init($this->config['api_base'] . '/v1/oauth2/token');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $this->config['client_id'] . ':' . $this->config['client_secret'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException($error !== ''
                ? 'Không kết nối được PayPal.'
                : 'PayPal từ chối Client ID hoặc Secret của môi trường ' . $this->config['mode'] . '.');
        }
        $decoded = json_decode((string)$body, true);
        if (!is_array($decoded) || empty($decoded['access_token'])) {
            throw new RuntimeException('PayPal không trả về access token hợp lệ.');
        }
        return (string)$decoded['access_token'];
    }

    private function request(string $method, string $path, $payload, string $requestId): array {
        $token = $this->accessToken();
        $curl = curl_init($this->config['api_base'] . $path);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'PayPal-Request-Id: ' . substr($requestId, 0, 72),
                'Prefer: return=representation',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ];
        if ($payload !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES);
        }
        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        $decoded = json_decode((string)$body, true);
        if ($body === false || $status < 200 || $status >= 300 || !is_array($decoded)) {
            if ($error !== '') {
                throw new RuntimeException('Không kết nối được PayPal. Vui lòng thử lại.');
            }
            $message = is_array($decoded) ? trim((string)($decoded['message'] ?? '')) : '';
            throw new RuntimeException($message !== ''
                ? 'PayPal báo lỗi: ' . $message
                : 'PayPal không xử lý được yêu cầu thanh toán.');
        }
        return $decoded;
    }
}
