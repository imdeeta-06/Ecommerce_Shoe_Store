<?php

namespace App\Controller\Admin;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\Order;

class OrderController {
    public function index() {
        AuthMiddleware::requireAdmin();
        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'keyword' => trim((string)($_GET['keyword'] ?? ''))
        ];
        $model = new Order();
        $orders = $model->getAdminOrders($filters, 1, 100);
        $flash = SessionHelper::getAllFlash();
        require __DIR__ . '/../../Views/admin/orders/index.php';
    }

    public function view() {
        AuthMiddleware::requireAdmin();
        $order = (new Order())->getOrder((int)($_GET['id'] ?? 0));
        if (!$order) {
            SessionHelper::setFlash('error', 'Không tìm thấy đơn hàng.');
            SessionHelper::redirect('/admin/orders');
        }
        $flash = SessionHelper::getAllFlash();
        require __DIR__ . '/../../Views/admin/orders/view.php';
    }

    public function updateStatus() {
        AuthMiddleware::requireAdmin();
        $result = (new Order())->updateStatus(
            (int)($_POST['order_id'] ?? 0),
            (string)($_POST['status'] ?? 'pending'),
            trim((string)($_POST['note'] ?? '')),
            (int)($_SESSION['user_id'] ?? 0)
        );
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/admin/orders/view?id=' . (int)($_POST['order_id'] ?? 0));
    }

    public function updateShipping() {
        AuthMiddleware::requireAdmin();
        $result = (new Order())->updateShipping(
            (int)($_POST['order_id'] ?? 0),
            $_POST['shipping_carrier'] ?? '',
            $_POST['tracking_code'] ?? '',
            $_POST['shipping_status'] ?? '',
            $_POST['shipping_fee'] ?? null
        );
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/admin/orders/view?id=' . (int)($_POST['order_id'] ?? 0));
    }
}
