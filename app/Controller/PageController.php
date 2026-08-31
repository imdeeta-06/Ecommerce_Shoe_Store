<?php

namespace App\Controller;

use App\Models\Order;
use App\Models\Cart;
use App\Models\Analytics;
use App\Models\Newsletter;
use App\Models\NewsletterCampaign;
use App\Helpers\SessionHelper;

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
        $metaDescription = 'Tra cứu trạng thái đơn hàng Liên Hoa bằng mã đơn và số điện thoại nhận hàng.';
        $trackingResult = null;
        $trackingError = null;
        $trackingSearched = isset($_GET['order_code']) || isset($_GET['phone']);
        $orderCode = strtoupper(trim((string)($_GET['order_code'] ?? '')));
        $phone = trim((string)($_GET['phone'] ?? ''));

        if ($trackingSearched) {
            if ($orderCode === '' || $phone === '') {
                $trackingError = 'Vui lòng nhập đầy đủ mã đơn hàng và số điện thoại nhận hàng.';
            } else {
                $trackingResult = (new Order())->findPublicTracking($orderCode, $phone);
                if (!$trackingResult) {
                    $trackingError = 'Không tìm thấy đơn hàng khớp đồng thời với mã đơn và số điện thoại đã nhập.';
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
        $flash = SessionHelper::getAllFlash();
        require __DIR__ . '/../Views/pages/feedback.php';
    }

    public function subscribeNewsletter() {
        if (empty($_POST['marketing_consent'])) {
            SessionHelper::setFlash('error', 'Bạn cần chủ động đồng ý nhận email marketing.');
        } else {
            $store = require __DIR__ . '/../../config/store.php';
            $result = (new Newsletter())->subscribe(
                (string)($_POST['email'] ?? ''),
                (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                (string)$store['privacy_version']
            );
            SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        }
        SessionHelper::redirect('/?newsletter=1');
    }

    public function unsubscribeNewsletter() {
        $success = (new Newsletter())->unsubscribe((string)($_GET['token'] ?? ''));
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Hủy nhận tin</title><body style="font-family:Arial;max-width:650px;margin:80px auto;padding:24px"><h1>'
            . ($success ? 'Đã hủy nhận tin' : 'Liên kết không hợp lệ hoặc đã được sử dụng')
            . '</h1><p><a href="' . htmlspecialchars(\App\Core\App::url('/'), ENT_QUOTES, 'UTF-8') . '">Về trang chủ</a></p></body></html>';
    }

    public function confirmNewsletter() {
        $success=(new Newsletter())->confirm((string)($_GET['token']??''));
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Xác nhận nhận tin</title><body style="font-family:Arial;max-width:650px;margin:80px auto;padding:24px"><h1>'.($success?'Đã xác nhận nhận tin':'Liên kết không hợp lệ hoặc đã hết hạn').'</h1><p><a href="'.htmlspecialchars(\App\Core\App::url('/'),ENT_QUOTES,'UTF-8').'">Về trang chủ</a></p></body></html>';
    }

    public function openNewsletter() {
        (new NewsletterCampaign())->markOpen((string)($_GET['token']??''));
        header('Content-Type: image/gif');
        header('Cache-Control: no-store, max-age=0');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
    }

    public function clickNewsletter() {
        $url=(new NewsletterCampaign())->clickTarget((string)($_GET['token']??''));
        if(!$url||(!str_starts_with($url,'/')&&!str_starts_with($url,'https://'))){http_response_code(404);echo 'Liên kết không hợp lệ.';return;}
        header('Location: '.$url);exit;
    }

    public function recordAnalytics() {
        header('Content-Type: application/json; charset=UTF-8');
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $saved = (new Analytics())->record($input, !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null, session_id());
        echo json_encode(['success' => $saved], JSON_UNESCAPED_UNICODE);
    }
}
