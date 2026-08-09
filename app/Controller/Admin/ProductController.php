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

                $this->setFlash('success', 'Thêm sản phẩm thành công.');
                $this->redirect('admin/products/edit?id=' . $productId);
            } catch (\Throwable $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }

        $product = null;
        $variants = [];
        $images = [];
        $categories = $this->productModel->getAllCategories();
        $flash = $this->pullFlash();

        require __DIR__ . '/../../Views/admin/products/form.php';
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $product = $this->productModel->getProductForAdmin($id);

        if (!$product) {
            $this->setFlash('error', 'Không tìm thấy sản phẩm.');
            $this->redirect('admin/products');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->productModel->updateProduct($id, $this->productPayload($id));

                if (!empty($_FILES['image']['name'])) {
                    $imagePath = UploadService::image($_FILES['image'], 'products');
                    $this->productModel->createProductImage([
                        'product_id' => $id,
                        'image_url' => $imagePath,
                        'is_primary' => empty($this->productModel->getProductImages($id)) ? 1 : 0
                    ]);
                }

                $this->setFlash('success', 'Cập nhật sản phẩm thành công.');
                $this->redirect('admin/products/edit?id=' . $id);
            } catch (\Throwable $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }

        $product = $this->productModel->getProductForAdmin($id);
        $variants = $this->productModel->getProductVariants($id);
        $images = $this->productModel->getProductImages($id);
        $categories = $this->productModel->getAllCategories();
        $flash = $this->pullFlash();

        require __DIR__ . '/../../Views/admin/products/form.php';
    }

    public function delete() {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        if ($id > 0) {
            $this->productModel->setProductStatus($id, $status);
            $this->setFlash('success', $status === 1 ? 'Đã hiển thị sản phẩm.' : 'Đã ẩn sản phẩm.');
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

                $this->setFlash('success', 'Đã xóa vĩnh viễn sản phẩm và dữ liệu liên quan.');
            } catch (\Throwable $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }

        $this->redirect('admin/products');
    }

    public function addVariant() {
        $productId = (int)($_POST['product_id'] ?? 0);
        $redirectPath = $productId > 0 ? 'admin/products/edit?id=' . $productId : 'admin/products';

        try {
            if (!$this->productModel->getProductForAdmin($productId)) {
                throw new \RuntimeException('Sản phẩm không tồn tại.');
            }

            $size = $this->variantSize($_POST['size'] ?? '');
            $color = $this->variantColor($_POST['color'] ?? '');

            if ($this->productModel->productVariantExists($productId, $size, $color)) {
                throw new \RuntimeException('Phân loại size/màu này đã tồn tại.');
            }

            $stockQuantity = max(0, (int)($_POST['stock_quantity'] ?? 0));
            $variantId = $this->productModel->createProductVariant([
                'product_id' => $productId,
                'size' => $size,
                'color' => $color,
                'stock_quantity' => 0,
                'price_modifier' => max(0, (float)($_POST['price_modifier'] ?? 0))
            ]);
            if ($stockQuantity > 0) {
                $this->productModel->updateStock($variantId, $stockQuantity, 'Tồn đầu kỳ khi tạo phân loại sản phẩm');
            }
            $this->setFlash('success', 'Đã thêm phân loại sản phẩm.');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($redirectPath);
    }

    public function updateVariant() {
        $id = (int)($_POST['id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $redirectPath = $productId > 0 ? 'admin/products/edit?id=' . $productId : 'admin/products';

        try {
            $variant = $this->productModel->getProductVariant($id);
            if (!$variant || (int)$variant['product_id'] !== $productId) {
                throw new \RuntimeException('Phân loại không thuộc sản phẩm này.');
            }

            $size = $this->variantSize($_POST['size'] ?? '');
            $color = $this->variantColor($_POST['color'] ?? '');

            if ($this->productModel->productVariantExists($productId, $size, $color, $id)) {
                throw new \RuntimeException('Phân loại size/màu này đã tồn tại.');
            }

            $newStock = max(0, (int)($_POST['stock_quantity'] ?? 0));
            $stockDelta = $newStock - (int)$variant['stock_quantity'];

            $this->productModel->updateProductVariant($id, [
                'size' => $size,
                'color' => $color,
                'price_modifier' => max(0, (float)($_POST['price_modifier'] ?? 0))
            ]);
            if ($stockDelta !== 0) {
                $this->productModel->updateStock($id, $stockDelta, 'Điều chỉnh tồn kho từ màn hình sản phẩm');
            }
            $this->setFlash('success', 'Đã cập nhật phân loại.');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($redirectPath);
    }

    public function deleteVariant() {
        $id = (int)($_POST['id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $redirectPath = $productId > 0 ? 'admin/products/edit?id=' . $productId : 'admin/products';

        try {
            $variant = $this->productModel->getProductVariant($id);
            if (!$variant || (int)$variant['product_id'] !== $productId) {
                throw new \RuntimeException('Phân loại không thuộc sản phẩm này.');
            }

            if ($this->productModel->productVariantHasOrderItems($id)) {
                throw new \RuntimeException('Phân loại đã phát sinh trong đơn hàng, không thể xóa để giữ lịch sử. Hãy đưa tồn kho về 0 hoặc ẩn sản phẩm.');
            }

            $this->productModel->deleteProductVariant($id);
            $this->setFlash('success', 'Đã xóa phân loại.');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($redirectPath);
    }

    public function setPrimaryImage() {
        $productId = (int)($_POST['product_id'] ?? 0);
        $imageId = (int)($_POST['image_id'] ?? 0);
        $redirectPath = $productId > 0 ? 'admin/products/edit?id=' . $productId : 'admin/products';

        try {
            $this->productModel->setPrimaryImage($productId, $imageId);
            $this->setFlash('success', 'Đã cập nhật ảnh đại diện sản phẩm.');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($redirectPath);
    }

    public function deleteImage() {
        $productId = (int)($_POST['product_id'] ?? 0);
        $imageId = (int)($_POST['image_id'] ?? 0);
        $image = $this->productModel->getProductImage($imageId);
        $redirectPath = $productId > 0 ? 'admin/products/edit?id=' . $productId : 'admin/products';

        if ($image && (int)$image['product_id'] === $productId) {
            $this->productModel->deleteProductImage($imageId);
            UploadService::delete($image['image_url']);
            $this->setFlash('success', 'Đã xóa ảnh sản phẩm.');
        } else {
            $this->setFlash('error', 'Ảnh không thuộc sản phẩm này.');
        }

        $this->redirect($redirectPath);
    }

    private function productPayload($productId = null) {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            throw new \RuntimeException('Vui lòng nhập tên sản phẩm.');
        }

        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        if ($categoryId && !$this->productModel->getCategory($categoryId)) {
            throw new \RuntimeException('Danh mục không hợp lệ.');
        }

        $basePrice = (float)($_POST['base_price'] ?? 0);
        if ($basePrice < 0) {
            throw new \RuntimeException('Giá gốc không được âm.');
        }

        $status = (int)($_POST['status'] ?? 1);
        if (!in_array($status, [0, 1], true)) {
            $status = 1;
        }

        $gender = $_POST['gender'] ?? null;
        $gender = in_array($gender, ['men', 'women'], true) ? $gender : null;

        $slug = $this->slugify($_POST['slug'] ?? $name);
        if ($this->productModel->productSlugExists($slug, $productId)) {
            throw new \RuntimeException('Slug sản phẩm đã tồn tại. Vui lòng chọn slug khác.');
        }

        return [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => trim($_POST['description'] ?? ''),
            'base_price' => $basePrice,
            'type' => trim($_POST['type'] ?? ''),
            'gender' => $gender,
            'status' => $status,
            'is_featured' => !empty($_POST['is_featured']) ? 1 : 0
        ];
    }

    private function slugify($value) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$value);
        if ($ascii !== false) {
            $value = $ascii;
        }

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
