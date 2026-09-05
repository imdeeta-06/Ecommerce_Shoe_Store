<?php

$mode = strtolower(trim((string)(getenv('PAYPAL_MODE') ?: 'sandbox')));
if (!in_array($mode, ['sandbox', 'live'], true)) {
    $mode = 'sandbox';
}
$liveCredentialsRotated = filter_var(
    (string)(getenv('PAYPAL_LIVE_CREDENTIALS_ROTATED') ?: 'false'),
    FILTER_VALIDATE_BOOLEAN
);

return [
    'mode' => $mode,
    'client_id' => trim((string)(getenv('PAYPAL_CLIENT_ID') ?: '')),
    'client_secret' => trim((string)(getenv('PAYPAL_CLIENT_SECRET') ?: '')),
    'webhook_id' => trim((string)(getenv('PAYPAL_WEBHOOK_ID') ?: '')),
    // Khóa từng xuất hiện trong ảnh/chat không được phép dùng để bật Live.
    // Chỉ đặt true sau khi đã thu hồi khóa cũ và tạo bộ Live mới trên PayPal.
    'live_credentials_rotated' => $liveCredentialsRotated,
    'currency' => 'USD',
    'vnd_per_usd' => max(0, (float)(getenv('PAYPAL_VND_PER_USD') ?: 0)),
    'api_base' => $mode === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com',
];
