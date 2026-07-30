<?php

namespace App\Middleware;

use App\Helpers\SessionHelper;

class AuthMiddleware {
    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            SessionHelper::setFlash('error', 'Vui lòng đăng nhập');
            SessionHelper::redirect('/login');
        }
    }

    public static function requireAdmin() {
        self::requireLogin();

        // Chỉ cho phép tài khoản admin truy cập khu vực quản trị.
        if (($_SESSION['user_role'] ?? null) !== 'admin') {
            SessionHelper::redirect('/');
        }
    }
}
