<?php

$mode = strtolower(trim((string)(getenv('PAYPAL_MODE') ?: 'sandbox')));
if (!in_array($mode, ['sandbox', 'live'], true)) {
    $mode = 'sandbox';
}

return [
    'mode' => $mode,
    'client_id' => trim((string)(getenv('PAYPAL_CLIENT_ID') ?: '')),
    'client_secret' => trim((string)(getenv('PAYPAL_CLIENT_SECRET') ?: '')),
    'webhook_id' => trim((string)(getenv('PAYPAL_WEBHOOK_ID') ?: '')),
    'currency' => 'USD',
    'vnd_per_usd' => max(0, (float)(getenv('PAYPAL_VND_PER_USD') ?: 0)),
    'api_base' => $mode === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com',
];
