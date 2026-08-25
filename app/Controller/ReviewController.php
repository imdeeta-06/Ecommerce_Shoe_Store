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

    public function storeDirect() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá.']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $productId = (int)($_POST['product_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim((string)($_POST['comment'] ?? ''));

        if ($productId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ.']);
            exit;
        }

        $result = (new Review())->createDirectReview($userId, $productId, $rating, $comment);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
