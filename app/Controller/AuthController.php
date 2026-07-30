<?php
namespace App\Controller;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\UserModel;
use App\Services\LoggingService;

class AuthController {
    public function login() {
        if (isset($_SESSION['user_id'])) {
            SessionHelper::redirect('/');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                SessionHelper::setFlash('error', 'Vui lòng nhập email và mật khẩu');
                SessionHelper::redirect('/login');
            }

            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);

            if (!$user) {
                SessionHelper::setFlash('error', 'Email hoặc mật khẩu không đúng');
                SessionHelper::redirect('/login');
            }

            if ((int) $user['status'] === 0) {
                SessionHelper::setFlash('error', 'Tài khoản đã bị khóa');
                SessionHelper::redirect('/login');
            }

            if (!password_verify($password, $user['password'])) {
                SessionHelper::setFlash('error', 'Email hoặc mật khẩu không đúng');
                SessionHelper::redirect('/login');
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = !empty($user['display_name']) ? $user['display_name'] : $user['full_name'];

            LoggingService::write($user['id'], 'login', 'Đăng nhập thành công');

            if ($user['role'] === 'admin') {
                SessionHelper::redirect('/admin');
            }

            SessionHelper::redirect('/');
        }

        require __DIR__ . '/../Views/login.php';
    }

    public function register() {
        if (isset($_SESSION['user_id'])) {
            SessionHelper::redirect('/');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $_SESSION['register_old'] = [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone
            ];

            if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
                SessionHelper::setFlash('error', 'Vui lòng nhập đầy đủ thông tin');
                SessionHelper::redirect('/register');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                SessionHelper::setFlash('error', 'Email không hợp lệ');
                SessionHelper::redirect('/register');
            }

            if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
                SessionHelper::setFlash('error', 'Số điện thoại không hợp lệ');
                SessionHelper::redirect('/register');
            }

            if (strlen($password) < 8) {
                SessionHelper::setFlash('error', 'Mật khẩu phải có ít nhất 8 ký tự');
                SessionHelper::redirect('/register');
            }

            if ($password !== $confirmPassword) {
                SessionHelper::setFlash('error', 'Mật khẩu xác nhận không khớp');
                SessionHelper::redirect('/register');
            }

            $userModel = new UserModel();

            if ($userModel->findByEmail($email)) {
                SessionHelper::setFlash('error', 'Email đã được sử dụng');
                SessionHelper::redirect('/register');
            }

            $newId = $userModel->create([
                'full_name' => $fullName,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => $phone
            ]);

            unset($_SESSION['register_old']);
            LoggingService::write($newId, 'register', 'Đăng ký tài khoản mới');
            SessionHelper::redirect('/login?registered=1');
        }

        require __DIR__ . '/../Views/register.php';
    }

    public function logout() {
        if (isset($_SESSION['user_id'])) {
            LoggingService::write($_SESSION['user_id'], 'logout', 'Đăng xuất');
        }

        session_destroy();
        SessionHelper::redirect('/login');
    }

    public function changePassword() {
        AuthMiddleware::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionHelper::redirect('/account');
        }

        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmNewPassword = trim($_POST['confirm_new_password'] ?? '');

        $userModel = new UserModel();
        $user = $userModel->findById($_SESSION['user_id']);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            SessionHelper::setFlash('error', 'Mật khẩu hiện tại không đúng');
            SessionHelper::redirect('/account');
        }

        if (strlen($newPassword) < 8) {
            SessionHelper::setFlash('error', 'Mật khẩu mới phải có ít nhất 8 ký tự');
            SessionHelper::redirect('/account');
        }

        if ($newPassword !== $confirmNewPassword) {
            SessionHelper::setFlash('error', 'Mật khẩu mới xác nhận không khớp');
            SessionHelper::redirect('/account');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $userModel->updatePassword($_SESSION['user_id'], $hash);

        LoggingService::write($_SESSION['user_id'], 'change_password', 'Đổi mật khẩu thành công');
        SessionHelper::setFlash('success', 'Đổi mật khẩu thành công');
        SessionHelper::redirect('/account');
    }

    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                SessionHelper::setFlash('error', 'Email không hợp lệ');
                SessionHelper::redirect('/forgot-password');
            }

            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);

            if (!$user) {
                SessionHelper::setFlash('error', 'Email không tồn tại');
                SessionHelper::redirect('/forgot-password');
            }

            $otp = rand(100000, 999999);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $userModel->createOtp($email, $otp, $expiresAt);

            $_SESSION['dev_otp'] = $otp;
            $_SESSION['otp_email'] = $email;
            SessionHelper::setFlash('info', "OTP của bạn là: $otp (chỉ dùng khi dev)");
            SessionHelper::redirect('/verify-otp');
        }

        require __DIR__ . '/../Views/forgot_password.php';
    }

    public function verifyOtp() {
        if (empty($_SESSION['otp_email'])) {
            SessionHelper::redirect('/forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $otp = trim($_POST['otp'] ?? '');
            $userModel = new UserModel();
            $result = $userModel->verifyOtp($_SESSION['otp_email'], $otp);

            if (!$result) {
                SessionHelper::setFlash('error', 'OTP không hợp lệ hoặc đã hết hạn');
                SessionHelper::redirect('/verify-otp');
            }

            $_SESSION['reset_verified_email'] = $_SESSION['otp_email'];
            unset($_SESSION['otp_email']);
            SessionHelper::redirect('/reset-password');
        }

        require __DIR__ . '/../Views/verify_otp.php';
    }

    public function resetPassword() {
        if (empty($_SESSION['reset_verified_email'])) {
            SessionHelper::redirect('/forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if (strlen($newPassword) < 8) {
                SessionHelper::setFlash('error', 'Mật khẩu phải có ít nhất 8 ký tự');
                SessionHelper::redirect('/reset-password');
            }

            if ($newPassword !== $confirmPassword) {
                SessionHelper::setFlash('error', 'Mật khẩu xác nhận không khớp');
                SessionHelper::redirect('/reset-password');
            }

            $userModel = new UserModel();
            $email = $_SESSION['reset_verified_email'];
            $user = $userModel->findByEmail($email);

            if (!$user) {
                SessionHelper::setFlash('error', 'Email không tồn tại');
                SessionHelper::redirect('/forgot-password');
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $userModel->updatePassword($user['id'], $hash);
            $userModel->deleteOtp($email);
            unset($_SESSION['reset_verified_email']);

            LoggingService::write(null, 'reset_password', 'Đặt lại mật khẩu qua OTP');
            SessionHelper::setFlash('success', 'Đặt lại mật khẩu thành công');
            SessionHelper::redirect('/login');
        }

        require __DIR__ . '/../Views/reset_password.php';
    }

}
