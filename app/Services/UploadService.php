<?php

namespace App\Services;

use Exception;

class UploadService {
    public static function image($file, $folder = 'products') {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Tải ảnh lên thất bại.');
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        $maxSize = 2 * 1024 * 1024;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception('Chỉ chấp nhận ảnh JPG, JPEG, PNG, WEBP hoặc AVIF.');
        }

        if ((int)$file['size'] > $maxSize) {
            throw new Exception('Dung lượng ảnh không được vượt quá 2MB.');
        }

        $uploadDir = __DIR__ . '/../../public/uploads/' . trim($folder, '/') . '/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new Exception('Không thể tạo thư mục lưu ảnh.');
        }

        $fileName = uniqid('img_', true) . '.' . $extension;
        $target = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new Exception('Không thể lưu ảnh đã tải lên.');
        }

        return 'public/uploads/' . trim($folder, '/') . '/' . $fileName;
    }

    public static function delete($path) {
        $path = ltrim((string)$path, '/');
        if ($path === '') {
            return;
        }

        if (strpos($path, 'public/uploads/') === 0) {
            $path = substr($path, strlen('public/'));
        }

        $fullPath = realpath(__DIR__ . '/../../public/' . $path);
        $publicDir = realpath(__DIR__ . '/../../public');

        if ($fullPath && $publicDir && strpos($fullPath, $publicDir) === 0 && is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}
