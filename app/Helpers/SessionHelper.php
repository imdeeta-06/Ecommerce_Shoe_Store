<?php

namespace App\Helpers;

class SessionHelper {
    public static function setFlash($type, $message) {
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
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }
}
