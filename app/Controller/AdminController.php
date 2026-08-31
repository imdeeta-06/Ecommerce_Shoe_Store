<?php
namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Helpers\SessionHelper;

class AdminController {
    public function index() {
        AuthMiddleware::requireAdmin();
        // Dashboard cũ thực hiện SQL trực tiếp trong view. Không cho phép nó
        // tiếp tục là entry-point; các thao tác quản trị phải đi qua controller/model mới.
        SessionHelper::redirect('/admin/orders');
    }

}
