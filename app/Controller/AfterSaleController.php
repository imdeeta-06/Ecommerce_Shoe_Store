<?php

namespace App\Controller;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\AfterSale;
use App\Services\UploadService;

class AfterSaleController {
    public function store() {
        AuthMiddleware::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        $evidencePaths = [];
        try {
            $files = $_FILES['evidence'] ?? null;
            if (is_array($files) && is_array($files['name'] ?? null)) {
                $total = min(5, count($files['name']));
                for ($i = 0; $i < $total; $i++) {
                    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $evidencePaths[] = UploadService::image([
                        'name' => $files['name'][$i] ?? '',
                        'type' => $files['type'][$i] ?? '',
                        'tmp_name' => $files['tmp_name'][$i] ?? '',
                        'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $files['size'][$i] ?? 0
                    ], 'after-sales');
                }
            }

            $result = (new AfterSale())->createRequest(
                (int)$_SESSION['user_id'],
                (int)($_POST['order_item_id'] ?? 0),
                (string)($_POST['request_type'] ?? 'return'),
                (string)($_POST['reason'] ?? ''),
                (int)($_POST['requested_quantity'] ?? 1),
                $evidencePaths
            );
            if (!$result['success']) {
                foreach ($evidencePaths as $path) {
                    UploadService::delete($path);
                }
            }
        } catch (\Throwable $e) {
            foreach ($evidencePaths as $path) {
                UploadService::delete($path);
            }
            $result = ['success' => false, 'message' => $e->getMessage()];
        }
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/account');
    }
}
