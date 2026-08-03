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
        try {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                throw new \RuntimeException('Vui lòng nhập tên danh mục.');
            }

            $slug = $this->slugify($_POST['slug'] ?? $name);
            if ($this->productModel->categorySlugExists($slug)) {
                throw new \RuntimeException('Slug danh mục đã tồn tại. Vui lòng chọn slug khác.');
            }

            $this->productModel->createCategory([
                'name' => $name,
                'slug' => $slug,
                'status' => (int)($_POST['status'] ?? 1) === 0 ? 0 : 1
            ]);
            $this->setFlash('success', 'Thêm danh mục thành công.');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
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
                $this->setFlash('success', $newStatus === 1 ? 'Đã hiển thị danh mục.' : 'Đã ẩn danh mục.');
            }
        }
        $this->redirect('admin/categories');
    }

    private function slugify($value) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$value);
        if ($ascii !== false) {
            $value = $ascii;
        }

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
