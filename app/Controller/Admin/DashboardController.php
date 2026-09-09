<?php

namespace App\Controller\Admin;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\Report;

class DashboardController {
    public function index(): void {
        AuthMiddleware::requireAdmin();

        $legacyPage = (string)($_GET['page'] ?? '');
        if (in_array($legacyPage, ['users', 'settings'], true)) {
            SessionHelper::redirect('/admin/' . $legacyPage);
        }

        $dashboard = (new Report())->getDashboardData();
        $flash = SessionHelper::getAllFlash();

        require __DIR__ . '/../../Views/admin/dashboard.php';
    }
}
