<?php

namespace App\Controller\Admin;

use App\Models\Product;
use App\Services\UploadService;

class ProductController {
    private $productModel;

    public function __construct() {
        $this->requireAdmin();
        $this->productModel = new Product();
    }

    public function index() {
        $filters = [
            'keyword' => $_GET['keyword'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'gender' => $_GET['gender'] ?? ''
        ];

        $products = $this->productModel->getAllProducts($filters);
        $categories = $this->productModel->getAllCategories();
        $flash = $this->pullFlash();

        require __DIR__ . '/../../Views/admin/products/index.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $productId = $this->productModel->createProduct($this->productPayload());

                if (!empty($_FILES['image']['name'])) {
                    $imagePath = UploadService::image($_FILES['image'], 'products');
                    $this->productModel->createProductImage([
                        'product_id' => $productId,
                        'image_url' => $imagePath,
                        'is_primary' => 1
                    ]);
                }

                $this->setFlash('success', 'Product created.');
                $this->redirect('admin/products/edit?id=' . $productId);
            } catch (\Exception $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }

        $product = null;
        $variants = [];
        $images = [];
        $categories = $this->productModel->getActiveCategories();
        $flash = $this->pullFlash();

        require __DIR__ . '/../../Views/admin/products/form.php';
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $product = $this->productModel->getProductForAdmin($id);

        if (!$product) {
            $this->setFlash('error', 'Product not found.');
            $this->redirect('admin/products');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->productModel->updateProduct($id, $this->productPayload());

                if (!empty($_FILES['image']['name'])) {
                    $imagePath = UploadService::image($_FILES['image'], 'products');
                    $this->productModel->createProductImage([
                        'product_id' => $id,
                        'image_url' => $imagePath,
                        'is_primary' => empty($this->productModel->getProductImages($id)) ? 1 : 0
                    ]);
                }

                $this->setFlash('success', 'Product updated.');
                $this->redirect('admin/products/edit?id=' . $id);
            } catch (\Exception $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }

        $product = $this->productModel->getProductForAdmin($id);
        $variants = $this->productModel->getProductVariants($id);
        $images = $this->productModel->getProductImages($id);
        $categories = $this->productModel->getActiveCategories();
        $flash = $this->pullFlash();

        require __DIR__ . '/../../Views/admin/products/form.php';
    }

    public function delete() {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        if ($id > 0) {
            $this->productModel->setProductStatus($id, $status);
            $this->setFlash('success', $status === 1 ? 'Product shown.' : 'Product hidden.');
        }
        $this->redirect('admin/products');
    }

    public function destroy() {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $images = $this->productModel->getProductImages($id);
                $this->productModel->destroyProduct($id);

                foreach ($images as $image) {
                    UploadService::delete($image['image_url']);
                }

                $this->setFlash('success', 'Product deleted.');
            } catch (\Exception $e) {
                $this->setFlash('error', 'Could not delete product: ' . $e->getMessage());
            }
        }

        $this->redirect('admin/products');
    }

    public function addVariant() {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            $this->productModel->createProductVariant([
                'product_id' => $productId,
                'size' => $this->variantSize($_POST['size'] ?? ''),
                'color' => $this->variantColor($_POST['color'] ?? ''),
                'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
                'price_modifier' => (float)($_POST['price_modifier'] ?? 0)
            ]);
            $this->setFlash('success', 'Variant added.');
        }
        $this->redirect('admin/products/edit?id=' . $productId);
    }

    public function updateVariant() {
        $id = (int)($_POST['id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($id > 0) {
            $this->productModel->updateProductVariant($id, [
                'size' => $this->variantSize($_POST['size'] ?? ''),
                'color' => $this->variantColor($_POST['color'] ?? ''),
                'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
                'price_modifier' => (float)($_POST['price_modifier'] ?? 0)
            ]);
            $this->setFlash('success', 'Variant updated.');
        }

        $this->redirect('admin/products/edit?id=' . $productId);
    }

    public function deleteVariant() {
        $id = (int)($_POST['id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($id > 0) {
            $this->productModel->deleteProductVariant($id);
            $this->setFlash('success', 'Variant deleted.');
        }

        $this->redirect('admin/products/edit?id=' . $productId);
    }

    public function setPrimaryImage() {
        $productId = (int)($_POST['product_id'] ?? 0);
        $imageId = (int)($_POST['image_id'] ?? 0);

        if ($productId > 0 && $imageId > 0) {
            $this->productModel->setPrimaryImage($productId, $imageId);
            $this->setFlash('success', 'Primary image updated.');
        }

        $this->redirect('admin/products/edit?id=' . $productId);
    }

    public function deleteImage() {
        $productId = (int)($_POST['product_id'] ?? 0);
        $imageId = (int)($_POST['image_id'] ?? 0);
        $image = $this->productModel->getProductImage($imageId);

        if ($image) {
            $this->productModel->deleteProductImage($imageId);
            UploadService::delete($image['image_url']);
            $this->setFlash('success', 'Image deleted.');
        }

        $this->redirect('admin/products/edit?id=' . $productId);
    }

    private function productPayload() {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            throw new \Exception('Product name is required.');
        }

        return [
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'name' => $name,
            'slug' => $this->slugify($_POST['slug'] ?? $name),
            'description' => trim($_POST['description'] ?? ''),
            'base_price' => (float)($_POST['base_price'] ?? 0),
            'type' => trim($_POST['type'] ?? ''),
            'gender' => $_POST['gender'] ?? null,
            'status' => (int)($_POST['status'] ?? 1)
        ];
    }

    private function slugify($value) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $value), '-'));
        return $slug ?: strtolower(uniqid('product-'));
    }

    private function variantColor($value) {
        $allowed = ['Black', 'Red', 'White'];
        return in_array($value, $allowed, true) ? $value : 'Black';
    }

    private function variantSize($value) {
        $value = trim((string)$value);
        if (preg_match('/^\d{2}$/', $value)) {
            $value = 'EU ' . $value;
        }

        $allowed = ['EU 36', 'EU 37', 'EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44', 'EU 45'];
        return in_array($value, $allowed, true) ? $value : 'EU 42';
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

