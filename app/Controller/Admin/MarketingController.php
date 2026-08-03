<?php

namespace App\Controller\Admin;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\Cart;
use App\Models\Database;
use App\Services\AbandonedCartReminderService;
use App\Services\OrderNotificationService;

class MarketingController {
    public function index() {
        AuthMiddleware::requireAdmin();
        $db = Database::getInstance()->getConnection();
        $banners = $db->query('SELECT * FROM banner ORDER BY id DESC')->fetchAll(\PDO::FETCH_ASSOC);
        $reminders = (new Cart())->getAbandonedReminders(100, false);
        $orderNotifications = (new OrderNotificationService())->getQueue(100);
        $flash = SessionHelper::getAllFlash();
        require __DIR__ . '/../../Views/admin/marketing/index.php';
    }

    public function storeBanner() {
        AuthMiddleware::requireAdmin();
        $imageUrl = trim((string)($_POST['image_url'] ?? ''));
        if ($imageUrl === '') {
            SessionHelper::setFlash('error', 'Vui lòng nhập đường dẫn ảnh banner.');
            SessionHelper::redirect('/admin/marketing');
        }
        $stmt = Database::getInstance()->getConnection()->prepare('INSERT INTO banner (image_url, link_url, status) VALUES (:image_url, :link_url, 1)');
        $stmt->execute(['image_url' => $imageUrl, 'link_url' => trim((string)($_POST['link_url'] ?? '')) ?: null]);
        SessionHelper::setFlash('success', 'Đã thêm banner marketing.');
        SessionHelper::redirect('/admin/marketing');
    }

    public function updateBannerStatus() {
        AuthMiddleware::requireAdmin();
        $stmt = Database::getInstance()->getConnection()->prepare('UPDATE banner SET status = IF(status = 1, 0, 1) WHERE id = :id');
        $stmt->execute(['id' => (int)($_POST['id'] ?? 0)]);
        SessionHelper::setFlash('success', 'Đã cập nhật trạng thái banner.');
        SessionHelper::redirect('/admin/marketing');
    }

    public function deleteBanner() {
        AuthMiddleware::requireAdmin();
        $stmt = Database::getInstance()->getConnection()->prepare('DELETE FROM banner WHERE id = :id');
        $stmt->execute(['id' => (int)($_POST['id'] ?? 0)]);
        SessionHelper::setFlash('success', 'Đã xóa banner.');
        SessionHelper::redirect('/admin/marketing');
    }

    public function sendAbandonedReminders() {
        AuthMiddleware::requireAdmin();
        $result = (new AbandonedCartReminderService())->process(50);
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/admin/marketing');
    }

    public function sendOrderNotifications() {
        AuthMiddleware::requireAdmin();
        $result = (new OrderNotificationService())->process(50);
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/admin/marketing');
    }
}
