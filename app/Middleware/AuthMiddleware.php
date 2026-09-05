<?php

namespace App\Middleware;

use App\Helpers\SessionHelper;
use App\Models\UserModel;

class AuthMiddleware {
    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            SessionHelper::setFlash('error', 'Vui lòng đăng nhập');
            SessionHelper::redirect('/login');
        }

        $user = (new UserModel())->findById((int)$_SESSION['user_id']);
        $status = $user ? strtolower(trim((string)($user['status'] ?? ''))) : '';
        if (!$user || !in_array($status, ['1', 'active'], true)) {
            self::clearAuthenticatedSession();
            SessionHelper::setFlash('error', 'Tài khoản không còn hoạt động.');
            SessionHelper::redirect('/login');
        }

        // Đồng bộ quyền ở mỗi request được bảo vệ để việc khóa hoặc đổi role
        // có hiệu lực ngay, không phải chờ người dùng đăng xuất.
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = !empty($user['display_name']) ? $user['display_name'] : $user['full_name'];
        $_SESSION['user_avatar'] = $user['avatar'] ?? null;
    }

    public static function requireAdmin() {
        self::requireLogin();

        // Chỉ cho phép tài khoản admin truy cập khu vực quản trị.
        if (($_SESSION['user_role'] ?? null) !== 'admin') {
            SessionHelper::redirect('/');
        }
    }

    private static function clearAuthenticatedSession(): void {
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_role'],
            $_SESSION['user_name'],
            $_SESSION['user_avatar']
        );
        session_regenerate_id(true);
    }
}
