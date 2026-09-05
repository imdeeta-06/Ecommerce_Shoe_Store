<?php

namespace App\Controller\User;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\UserModel;
use App\Models\Order;
use App\Models\AfterSale;
use App\Models\SupportTicket;
use App\Models\ElectronicInvoice;
use App\Services\LoggingService;
use App\Services\UploadService;

class ProfileController {
    public function index() {
        AuthMiddleware::requireLogin();

        $userModel = new UserModel();
        $user = $userModel->findById($_SESSION['user_id']);
        $addresses = $userModel->getAddresses($_SESSION['user_id']);
        $orderModel = new Order();
        try { $orderModel->expirePendingOrders(50); } catch (\Throwable $ignored) {}
        $orders = $orderModel->getOrdersByUserId($_SESSION['user_id']);
        foreach ($orders as &$order) {
            $order['items'] = $orderModel->getOrderItems((int)$order['id']);
        }
        unset($order);
        $afterSaleRequests = (new AfterSale())->getByUser($_SESSION['user_id']);
        $supportTickets = (new SupportTicket())->getUserTickets((int)$_SESSION['user_id']);
        $customerInvoices = (new ElectronicInvoice())->listForUser((int)$_SESSION['user_id']);

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

        try {
            $dbPath = UploadService::image($_FILES['avatar'], 'avatars');
        } catch (\Throwable $uploadError) {
            SessionHelper::setFlash('error', $uploadError->getMessage());
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

        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            SessionHelper::setFlash('error', 'Số điện thoại người nhận không hợp lệ');
            SessionHelper::redirect('/account');
        }

        $userModel = new UserModel();
        $userModel->addAddress($_SESSION['user_id'], [
            'recipient_name' => $recipientName,
            'recipient_phone' => $phone,
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

    public function cancelOrder() {
        AuthMiddleware::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        $result = (new Order())->cancelOrder((int)($_POST['order_id'] ?? 0), (int)$_SESSION['user_id']);
        SessionHelper::setFlash($result['success'] ? 'success' : 'error', $result['message']);
        SessionHelper::redirect('/account');
    }
}
