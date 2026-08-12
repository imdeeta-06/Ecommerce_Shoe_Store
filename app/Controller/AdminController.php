<?php
namespace App\Controller;

use App\Middleware\AuthMiddleware;

class AdminController {
    public function index() {
        AuthMiddleware::requireAdmin();
        header('Location: ' . BASE_URL . 'admin/products');
        exit;
    }

}
