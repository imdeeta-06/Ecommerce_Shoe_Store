<?php

namespace App\Controller\Admin;

use App\Models\UserModel;

class UserController {
    private $userModel;

    public function __construct() {
        $this->requireAdmin();
        $this->userModel = new UserModel();
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

            if ($password === '') {
                $errors[] = 'Password is required.';
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

                $_SESSION['admin_success'] = 'Admin account created successfully.';
                $this->redirect('admin?page=users');
            }
        }

        require __DIR__ . '/../../Views/admin/users/create.php';
    }

    private function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }

    private function redirect($path) {
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }
}
