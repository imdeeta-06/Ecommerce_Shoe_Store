<?php

namespace App\Controller\Admin;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\AfterSale;

class AfterSaleController {
    public function index() {
        AuthMiddleware::requireAdmin();
        $model = new AfterSale();
        $requests = $model->getAdminRequests();
        $replacementVariants = $model->getReplacementVariants();
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
            (string)($_POST['refund_transaction_code'] ?? ''),
            (int)($_POST['replacement_variant_id'] ?? 0),
            (int)($_POST['replacement_quantity'] ?? 0),
            (string)($_POST['replacement_shipping_carrier'] ?? ''),
            (string)($_POST['replacement_tracking_code'] ?? '')
        );
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/admin/after-sales');
    }
}
