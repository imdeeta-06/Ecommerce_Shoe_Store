<?php

namespace App\Controller\Admin;

use App\Models\UserModel;
use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;

class UserController {
    private $userModel;

    public function __construct() {
        AuthMiddleware::requireAdmin();
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
                    SessionHelper::setFlash(
                            'success',
                            'Admin account created successfully.'
                        );

                        SessionHelper::redirect('/admin?page=users');
                    }
        }

        require __DIR__ . '/../../Views/admin/users/create.php';
    }
    
}
