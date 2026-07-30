<?php
namespace App\Controller;

use App\Models\Order;

class AccountController {
    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: login');
            exit;
        }

        require_once __DIR__ . '/../../config/db.php';
        
        $user_id = $_SESSION['user_id'];
        
        // Đảm bảo có cột avatar
        try {
            $pdo->exec("ALTER TABLE user ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
        } catch (\PDOException $e) { }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'update_avatar' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Get extension
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $fileName = time() . '_' . uniqid() . '.' . $ext;
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                    $avatarPath = 'public/uploads/avatars/' . $fileName;
                    $stmt = $pdo->prepare("UPDATE user SET avatar = ? WHERE id = ?");
                    $stmt->execute([$avatarPath, $user_id]);
                    $_SESSION['user_avatar'] = $avatarPath;
                }
                header('Location: account?tab=account&success=1');
                exit;
            } elseif ($action === 'update_address') {
                $addressScope = $_POST['address_scope'] ?? '';
                $addressLine = trim($_POST['address_line'] ?? '');
                $wardDistrictCity = trim($_POST['ward_district_city'] ?? '');

                $isDefault = $addressScope === 'shipping' ? 1 : 0;

                if (in_array($addressScope, ['billing', 'shipping'], true) && $addressLine !== '' && $wardDistrictCity !== '') {
                    $stmtExisting = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id = ? AND is_default = ? LIMIT 1");
                    $stmtExisting->execute([$user_id, $isDefault]);
                    $existingAddressId = $stmtExisting->fetchColumn();

                    if ($existingAddressId) {
                        $stmt = $pdo->prepare("UPDATE user_addresses SET address_line = ?, ward_district_city = ? WHERE id = ? AND user_id = ?");
                        $stmt->execute([$addressLine, $wardDistrictCity, $existingAddressId, $user_id]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, address_line, ward_district_city, is_default) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$user_id, $addressLine, $wardDistrictCity, $isDefault]);
                    }
                }

                header('Location: account?tab=address');
                exit;
            } elseif ($action === 'update_account') {
                $fullName = trim($_POST['full_name'] ?? '');
                $displayName = trim($_POST['display_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $oldPassword = $_POST['old_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $repeatPassword = $_POST['repeat_password'] ?? '';

                if ($newPassword !== '' || $oldPassword !== '' || $repeatPassword !== '') {
                    $stmtUser = $pdo->prepare("SELECT password FROM user WHERE id = ?");
                    $stmtUser->execute([$user_id]);
                    $currentUser = $stmtUser->fetch(\PDO::FETCH_ASSOC);

                    if (!$currentUser || !password_verify($oldPassword, $currentUser['password'])) {
                        $_SESSION['account_error'] = 'Old password is incorrect';
                        header('Location: account?tab=account');
                        exit;
                    }

                    if ($newPassword !== '' && $newPassword === $repeatPassword) {
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmtPassword = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
                        $stmtPassword->execute([$passwordHash, $user_id]);
                        $_SESSION['account_success'] = 'Password changed successfully';
                    }
                }

                if ($fullName !== '' && $email !== '' && $phone !== '') {
                    $stmt = $pdo->prepare("UPDATE user SET full_name = ?, display_name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$fullName, $displayName, $email, $phone, $user_id]);
                    $_SESSION['user_name'] = $displayName !== '' ? $displayName : $fullName;
                }

                header('Location: account?tab=account&success=1');
                exit;
            }
        }
        
        // Lấy thông tin user
        $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Fetch user addresses if any
        $stmt_addr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id ASC");
        $stmt_addr->execute([$user_id]);
        $addresses = $stmt_addr->fetchAll(\PDO::FETCH_ASSOC);
        $addressByScope = [];

        foreach ($addresses as $address) {
            $scope = !empty($address['is_default']) ? 'shipping' : 'billing';
            if (!isset($addressByScope[$scope])) {
                $addressByScope[$scope] = $address;
            }
        }
        
        // Fetch orders if any
        $orderStatus = $_GET['status'] ?? 'all';
        $orderModel = new Order();
        $orders = $orderModel->getOrdersByUserId($user_id, $orderStatus);

        require __DIR__ . '/../Views/account.php';
    }
}
