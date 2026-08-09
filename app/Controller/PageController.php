<?php

namespace App\Controller;

use App\Models\Order;
use App\Models\Cart;

class PageController {
    
    public function about() {
        require __DIR__ . '/../Views/pages/about.php';
    }

    public function careers() {
        require __DIR__ . '/../Views/pages/careers.php';
    }

    public function franchise() {
        require __DIR__ . '/../Views/pages/franchise.php';
    }

    public function faqs() {
        require __DIR__ . '/../Views/pages/faqs.php';
    }

    public function privacy() {
        require __DIR__ . '/../Views/pages/privacy.php';
    }

    public function terms() {
        require __DIR__ . '/../Views/pages/terms.php';
    }

    public function tracking() {
        $metaTitle = 'Tra cứu đơn hàng - PaceUp';
        $metaDescription = 'Tra cứu trạng thái đơn hàng PaceUp bằng mã đơn hàng và số điện thoại nhận hàng.';
        $trackingResult = null;
        $trackingError = null;
        $trackingSearched = isset($_GET['order_code']) || isset($_GET['phone']);
        $orderCode = trim((string)($_GET['order_code'] ?? ''));
        $phone = trim((string)($_GET['phone'] ?? ''));

        if ($trackingSearched) {
            if ($orderCode === '' || $phone === '') {
                $trackingError = 'Vui lòng nhập đầy đủ mã đơn hàng và số điện thoại nhận hàng.';
            } else {
                $trackingResult = (new Order())->findPublicTracking($orderCode, $phone);
                if (!$trackingResult) {
                    $trackingError = 'Không tìm thấy đơn hàng phù hợp. Vui lòng kiểm tra lại mã đơn hàng và số điện thoại.';
                }
            }
        }

        require __DIR__ . '/../Views/pages/tracking.php';
    }

    public function unsubscribeCartReminder() {
        $success = (new Cart())->unsubscribeByToken((string)($_GET['token'] ?? ''));
        $metaTitle = 'Hủy email nhắc giỏ hàng - PaceUp';
        require __DIR__ . '/../Views/pages/cart-reminder-unsubscribe.php';
    }
}
