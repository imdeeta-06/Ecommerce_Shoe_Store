<?php

require_once __DIR__ . '/../app/Core/App.php';
\App\Core\App::bootstrap();

$expiration = (new \App\Models\Order())->expirePendingOrders(500);
$orderNotifications = (new \App\Services\OrderNotificationService())->process(100);
$cartReminders = (new \App\Services\AbandonedCartReminderService())->process(100);
$customerCare = (new \App\Services\CustomerCareService())->process(100);
$newsletter = (new \App\Models\NewsletterCampaign())->processQueued(10, 100);
echo 'Đã tự hủy ' . (int)$expiration['expired'] . ' đơn quá hạn; lỗi: ' . count($expiration['failed']) . '.' . PHP_EOL;
echo 'PayPal đã đối soát: ' . (int)($expiration['paypal_reconciled'] ?? 0)
    . '; tạm hoãn hủy do chưa kết nối được PayPal: ' . (int)($expiration['paypal_deferred'] ?? 0) . '.' . PHP_EOL;
echo 'Thông báo đơn: ' . $orderNotifications['message'] . PHP_EOL;
echo 'Nhắc giỏ hàng: ' . $cartReminders['message'] . PHP_EOL;
echo 'Phản hồi CSKH: ' . $customerCare['message'] . PHP_EOL;
echo 'Newsletter: ' . $newsletter['message'] . PHP_EOL;

$success = empty($expiration['failed'])
    && !empty($orderNotifications['success'])
    && !empty($cartReminders['success'])
    && !empty($customerCare['success'])
    && !empty($newsletter['success']);
exit($success ? 0 : 1);
