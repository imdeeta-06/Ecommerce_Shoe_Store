<?php

namespace App\Controller;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\Review;

class ReviewController {
    public function store() {
        AuthMiddleware::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        $result = (new Review())->createVerifiedReview(
            (int)$_SESSION['user_id'],
            (int)($_POST['order_item_id'] ?? 0),
            (int)($_POST['rating'] ?? 5),
            trim((string)($_POST['comment'] ?? ''))
        );
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/account');
    }
}
