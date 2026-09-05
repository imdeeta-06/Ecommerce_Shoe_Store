<?php
namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Helpers\SessionHelper;

class AdminController {
    public function index() {
        AuthMiddleware::requireAdmin();
        // Dashboard quản trị thống nhất bắt đầu ở danh sách đơn hàng; mọi
        // thao tác dữ liệu đi qua controller/model chuyên trách.
        SessionHelper::redirect('/admin/orders');
    }

}
