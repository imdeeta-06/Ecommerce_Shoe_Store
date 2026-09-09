<?php

namespace App\Controller\Admin;

use App\Models\UserModel;

class UserController {
    private $userModel;

    public function __construct() {
        $this->requireAdmin();
        $this->userModel = new UserModel();
    }

    public function index() {
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->userModel->getAll(['keyword' => $keyword], $page, 20);
        $users = $result['data'];
        $totalPages = max(1, (int)ceil($result['total'] / 20));
        $flash = \App\Helpers\SessionHelper::getAllFlash();
        require __DIR__ . '/../../Views/admin/users/index.php';
    }

    public function updateStatus() {
        $id = (int)($_POST['user_id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        $user = $this->userModel->findById($id);
        if (!$user || $user['role'] === 'admin' || !in_array($status, ['0', '1'], true)) {
            \App\Helpers\SessionHelper::setFlash('error', 'Chỉ được khóa hoặc mở khóa tài khoản khách hàng hợp lệ.');
        } else {
            $this->userModel->updateStatus($id, (int)$status);
            \App\Helpers\SessionHelper::setFlash('success', $status === '1' ? 'Đã mở khóa khách hàng.' : 'Đã khóa khách hàng.');
        }
        $this->redirect('admin/users');
    }

    public function create() {
        $errors = [];
        $old = [
            'full_name' => '',
            'display_name' => '',
            'email' => '',
            'phone' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old = [
                'full_name' => trim($_POST['full_name'] ?? ''),
                'display_name' => trim($_POST['display_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? '')
            ];
            $password = (string)($_POST['password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            if ($old['full_name'] === '') {
                $errors[] = 'Full Name is required.';
            }

            if ($old['email'] === '') {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email is invalid.';
            } elseif ($this->userModel->findByEmail($old['email'])) {
                $errors[] = 'Email is already in use.';
            }

            if (strlen($password) < 8 || strlen($password) > 72) {
                $errors[] = 'Password must be between 8 and 72 characters.';
            }

            if ($confirmPassword === '') {
                $errors[] = 'Confirm Password is required.';
            }

            if ($password !== '' && $confirmPassword !== '' && $password !== $confirmPassword) {
                $errors[] = 'Password and Confirm Password must match.';
            }

            if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone'])) {
                $errors[] = 'Phone Number is invalid.';
            }

            if (empty($errors)) {
                $this->userModel->createAdmin([
                    'full_name' => $old['full_name'],
                    'display_name' => $old['display_name'],
                    'email' => $old['email'],
                    'phone' => $old['phone'],
                    'password' => password_hash($password, PASSWORD_DEFAULT)
                ]);

                \App\Helpers\SessionHelper::setFlash('success', 'Đã tạo tài khoản quản trị.');
                $this->redirect('admin/users');
            }
        }

        require __DIR__ . '/../../Views/admin/users/create.php';
    }

    private function requireAdmin() {
        \App\Middleware\AuthMiddleware::requireAdmin();
    }

    private function redirect($path) {
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }
}
