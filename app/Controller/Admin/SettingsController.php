<?php
namespace App\Controller\Admin;

use App\Middleware\AuthMiddleware;
use App\Services\MailService;
use App\Services\PayPalService;

class SettingsController {
    public function index(): void {
        AuthMiddleware::requireAdmin();
        $store = require __DIR__ . '/../../../config/store.php';
        $mailReady = MailService::isConfigured();
        $paypalReady = (new PayPalService())->isConfigured();
        require __DIR__ . '/../../Views/admin/settings/index.php';
    }
}
