<?php

namespace App\Controller\Admin;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\SupportTicket;
use App\Services\CustomerCareService;

class SupportController {
    public function index() {
        AuthMiddleware::requireAdmin();
        $status = trim((string)($_GET['status'] ?? ''));
        $tickets = (new SupportTicket())->getAdminTickets($status, 100);
        $flash = SessionHelper::getAllFlash();
        require __DIR__ . '/../../Views/admin/support/index.php';
    }

    public function updateStatus() {
        AuthMiddleware::requireAdmin();
        $result = (new SupportTicket())->updateStatus(
            (int)($_POST['id'] ?? 0),
            trim((string)($_POST['status'] ?? 'pending'))
        );
        SessionHelper::setFlash($result ? 'success' : 'error', $result ? 'Đã cập nhật trạng thái yêu cầu.' : 'Trạng thái yêu cầu không hợp lệ.');
        SessionHelper::redirect('/admin/support');
    }

    public function sendAutoReplies() {
        AuthMiddleware::requireAdmin();
        $result = (new CustomerCareService())->process(50);
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/admin/support');
    }
}
