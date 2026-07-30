<?php

namespace App\Controller\User;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\UserModel;
use App\Services\LoggingService;

class ProfileController {
    public function index() {
        AuthMiddleware::requireLogin();

        $userModel = new UserModel();
        $user = $userModel->findById($_SESSION['user_id']);
        $addresses = $userModel->getAddresses($_SESSION['user_id']);

        require __DIR__ . '/../../Views/account/profile.php';
    }

    public function update() {
        AuthMiddleware::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($fullName === '') {
            SessionHelper::setFlash('error', 'Vui lòng nhập họ và tên');
            SessionHelper::redirect('/account');
        }

        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            SessionHelper::setFlash('error', 'Số điện thoại không hợp lệ');
            SessionHelper::redirect('/account');
        }

        $userModel = new UserModel();
        $userModel->updateProfile($_SESSION['user_id'], [
            'full_name' => $fullName,
            'phone' => $phone
        ]);

        $_SESSION['user_name'] = $fullName;

        LoggingService::write($_SESSION['user_id'], 'update_profile', 'Cập nhật thông tin cá nhân');
        SessionHelper::setFlash('success', 'Cập nhật thông tin thành công');
        SessionHelper::redirect('/account');
    }

    public function uploadAvatar() {
        AuthMiddleware::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            SessionHelper::setFlash('error', 'Vui lòng chọn ảnh đại diện');
            SessionHelper::redirect('/account');
        }

        $file = $_FILES['avatar'];
        $maxSize = 2 * 1024 * 1024;
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = mime_content_type($file['tmp_name']);

        if (!in_array($ext, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
            SessionHelper::setFlash('error', 'Ảnh đại diện chỉ chấp nhận jpg, jpeg, png, webp');
            SessionHelper::redirect('/account');
        }

        if ($file['size'] > $maxSize) {
            SessionHelper::setFlash('error', 'Ảnh đại diện tối đa 2MB');
            SessionHelper::redirect('/account');
        }

        $uploadDir = __DIR__ . '/../../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid('avatar_') . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
        $dbPath = 'public/uploads/avatars/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            SessionHelper::setFlash('error', 'Không thể lưu ảnh đại diện');
            SessionHelper::redirect('/account');
        }

        $userModel = new UserModel();
        $userModel->updateAvatar($_SESSION['user_id'], $dbPath);

        LoggingService::write($_SESSION['user_id'], 'upload_avatar', 'Cập nhật ảnh đại diện');
        SessionHelper::setFlash('success', 'Cập nhật ảnh đại diện thành công');
        SessionHelper::redirect('/account');
    }

    public function addAddress() {
        AuthMiddleware::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        $recipientName = trim($_POST['recipient_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;

        if ($recipientName === '' || $phone === '' || $address === '' || $city === '') {
            SessionHelper::setFlash('error', 'Vui lòng nhập đầy đủ thông tin địa chỉ');
            SessionHelper::redirect('/account');
        }

        $userModel = new UserModel();
        $userModel->addAddress($_SESSION['user_id'], [
            'recipient_name' => $recipientName,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'is_default' => $isDefault
        ]);

        LoggingService::write($_SESSION['user_id'], 'add_address', 'Thêm địa chỉ mới');
        SessionHelper::setFlash('success', 'Thêm địa chỉ thành công');
        SessionHelper::redirect('/account');
    }

    public function setDefaultAddress() {
        AuthMiddleware::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        $addressId = (int) ($_POST['address_id'] ?? 0);

        if ($addressId > 0) {
            $userModel = new UserModel();
            $userModel->setDefaultAddress($_SESSION['user_id'], $addressId);
        }

        SessionHelper::redirect('/account');
    }

    public function deleteAddress() {
        AuthMiddleware::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        $addressId = (int) ($_POST['address_id'] ?? 0);

        if ($addressId > 0) {
            $userModel = new UserModel();
            $userModel->deleteAddress($addressId, $_SESSION['user_id']);
        }

        LoggingService::write($_SESSION['user_id'], 'delete_address', 'Xóa địa chỉ');
        SessionHelper::setFlash('success', 'Đã xóa địa chỉ');
        SessionHelper::redirect('/account');
    }
}
