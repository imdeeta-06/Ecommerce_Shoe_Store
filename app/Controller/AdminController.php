<?php
namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Models\Report;

class AdminController {
    public function index() {
        AuthMiddleware::requireAdmin();
        $dashboard = (new Report())->getDashboardData();
        require __DIR__ . '/../Views/admin.php';
    }

}
