<?php

require_once __DIR__ . '/../app/Core/App.php';
\App\Core\App::bootstrap();

$expiration = (new \App\Models\Order())->expirePendingOrders(500);
$result = (new \App\Services\OrderNotificationService())->process(100);
echo 'Đã tự hủy ' . (int)$expiration['expired'] . ' đơn quá hạn; lỗi: ' . count($expiration['failed']) . '.' . PHP_EOL;
echo 'PayPal đã đối soát: ' . (int)($expiration['paypal_reconciled'] ?? 0)
    . '; tạm hoãn hủy do chưa kết nối được PayPal: ' . (int)($expiration['paypal_deferred'] ?? 0) . '.' . PHP_EOL;
echo $result['message'] . PHP_EOL;
exit($result['success'] && empty($expiration['failed']) ? 0 : 1);
