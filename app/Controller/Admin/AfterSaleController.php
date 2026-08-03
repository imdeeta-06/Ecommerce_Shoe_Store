<?php

namespace App\Controller\Admin;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\AfterSale;

class AfterSaleController {
    public function index() {
        AuthMiddleware::requireAdmin();
        $requests = (new AfterSale())->getAdminRequests();
        $flash = SessionHelper::getAllFlash();
        require __DIR__ . '/../../Views/admin/after-sales/index.php';
    }

    public function update() {
        AuthMiddleware::requireAdmin();
        $result = (new AfterSale())->updateStatus(
            (int)($_POST['id'] ?? 0),
            (string)($_POST['status'] ?? 'pending'),
            (string)($_POST['resolution_note'] ?? ''),
            (int)($_POST['approved_quantity'] ?? 0),
            !empty($_POST['restockable']),
            (string)($_POST['refund_transaction_code'] ?? '')
        );
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/admin/after-sales');
    }
}
