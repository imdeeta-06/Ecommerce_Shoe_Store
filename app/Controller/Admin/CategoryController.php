<?php

namespace App\Controller\Admin;

use App\Models\Product;

class CategoryController {
    private $productModel;

    public function __construct() {
        $this->requireAdmin();
        $this->productModel = new Product();
    }

    public function index() {
        $categories = $this->productModel->getAllCategories();
        $flash = $this->pullFlash();
        require __DIR__ . '/../../Views/admin/categories/index.php';
    }

    public function create() {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $this->productModel->createCategory([
                'name' => $name,
                'slug' => $this->slugify($_POST['slug'] ?? $name),
                'status' => (int)($_POST['status'] ?? 1)
            ]);
            $this->setFlash('success', 'Category created.');
        }
        $this->redirect('admin/categories');
    }

    public function delete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $category = $this->productModel->getCategory($id);
            if ($category) {
                $newStatus = (int)$category['status'] === 1 ? 0 : 1;
                $this->productModel->updateCategory($id, ['status' => $newStatus]);
                $this->setFlash('success', $newStatus === 1 ? 'Category shown.' : 'Category hidden.');
            }
        }
        $this->redirect('admin/categories');
    }

    private function slugify($value) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $value), '-'));
        return $slug ?: strtolower(uniqid('category-'));
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

    private function setFlash($type, $message) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash() {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
