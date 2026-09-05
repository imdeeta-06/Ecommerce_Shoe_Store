<?php

namespace App\Helpers;

use App\Core\App;

class SessionHelper {
    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8')
            . '">';
    }

    public static function validateCsrfRequest(): bool {
        $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        $expected = $_SESSION['csrf_token'] ?? '';

        return is_string($submitted)
            && is_string($expected)
            && $submitted !== ''
            && $expected !== ''
            && hash_equals($expected, $submitted);
    }

    public static function setFlash($type, $message) {
        // Chỉ giữ kết quả mới nhất của một thao tác. Điều này tránh trường hợp
        // thông báo lỗi cũ và thông báo thành công mới xuất hiện cùng lúc,
        // đặc biệt khi người dùng quay lại từ cổng thanh toán ở nhiều tab.
        if ($type === 'success') {
            unset($_SESSION['flash']['error']);
        } elseif ($type === 'error') {
            unset($_SESSION['flash']['success']);
        }

        $_SESSION['flash'][$type] = $message;
    }

    public static function getFlash($type) {
        if (!isset($_SESSION['flash'][$type])) {
            return null;
        }

        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);

        return $message;
    }

    public static function getAllFlash() {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return $flash;
    }

    public static function redirect($path) {
        header('Location: ' . App::url($path));
        exit;
    }
}
