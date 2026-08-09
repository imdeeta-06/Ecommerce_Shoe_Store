<?php
namespace App\Controller;

use App\Middleware\AuthMiddleware;

class AdminController {
    public function index() {
        AuthMiddleware::requireAdmin();
        require __DIR__ . '/../Views/admin.php';
    }

}
