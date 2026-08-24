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
        $metaTitle = 'Tra cứu đơn hàng - Liên Hoa';
        $metaDescription = 'Tra cứu trạng thái đơn hàng Liên Hoa bằng số điện thoại hoặc Gmail nhận hàng.';
        $trackingResults = [];
        $trackingError = null;
        $trackingSearched = isset($_GET['contact']);
        $contact = trim((string)($_GET['contact'] ?? ''));

        if ($trackingSearched) {
            if ($contact === '') {
                $trackingError = 'Vui lòng nhập số điện thoại hoặc Gmail nhận hàng.';
            } else {
                $trackingResults = (new Order())->findPublicTrackingByContact($contact);
                if (empty($trackingResults)) {
                    $trackingError = 'Không tìm thấy đơn hàng nào phù hợp với số điện thoại hoặc Gmail đã nhập.';
                }
            }
        }

        require __DIR__ . '/../Views/pages/tracking.php';
    }

    public function unsubscribeCartReminder() {
        $success = (new Cart())->unsubscribeByToken((string)($_GET['token'] ?? ''));
        $metaTitle = 'Hủy email nhắc giỏ hàng - Liên Hoa';
        require __DIR__ . '/../Views/pages/cart-reminder-unsubscribe.php';
    }

    public function feedback() {
        $metaTitle = 'Gửi phản hồi - Liên Hoa';
        $metaDescription = 'Gửi phản hồi và ý kiến đóng góp của bạn cho Liên Hoa.';
        require __DIR__ . '/../Views/pages/feedback.php';
    }
}
