<?php

namespace App\Controller\Admin;

use App\Models\Product;

class InventoryController {
    private $productModel;

    public function __construct() {
        $this->requireAdmin();
        $this->productModel = new Product();
    }

    public function index() {
        $variants = $this->productModel->getInventoryOverview();
        $logs = $this->productModel->getInventoryLogs(80);
        $products = $this->productModel->getAllProducts(['status' => 1]);
        $flash = $this->pullFlash();
        require __DIR__ . '/../../Views/admin/inventory/index.php';
    }

    public function createVariant() {
        $productId = (int)($_POST['product_id'] ?? 0);
        $size = $this->variantSize($_POST['size'] ?? '');
        $color = $this->variantColor($_POST['color'] ?? '');
        $stockQuantity = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $priceModifier = max(0, (float)($_POST['price_modifier'] ?? 0));

        try {
            if ($productId <= 0 || $size === '' || $color === '') {
                throw new \RuntimeException('Vui lòng chọn sản phẩm, size và màu để tạo phân loại.');
            }
            if (!$this->productModel->getProductForAdmin($productId)) {
                throw new \RuntimeException('Sản phẩm không tồn tại.');
            }
            if ($this->productModel->productVariantExists($productId, $size, $color)) {
                throw new \RuntimeException('Phân loại size/màu này đã tồn tại.');
            }

            $variantId = $this->productModel->createProductVariant([
                'product_id' => $productId,
                'size' => $size,
                'color' => $color,
                'stock_quantity' => 0,
                'price_modifier' => $priceModifier
            ]);
            if ($stockQuantity > 0) {
                $this->productModel->updateStock($variantId, $stockQuantity, 'Tồn đầu kỳ khi tạo phân loại sản phẩm');
            }
            $this->setFlash('success', 'Đã tạo phân loại sản phẩm.');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('admin/inventory');
    }

    public function update() {
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $quantity = abs((int)($_POST['quantity'] ?? 0));
        $type = $_POST['change_type'] ?? 'in';
        $reason = trim($_POST['reason'] ?? '');

        if ($variantId > 0 && $quantity > 0) {
            $quantityChanged = $type === 'out' ? -$quantity : $quantity;
            try {
                $this->productModel->updateStock($variantId, $quantityChanged, $reason ?: 'Nhập/xuất kho thủ công');
                $this->setFlash('success', 'Đã cập nhật tồn kho và lưu lịch sử nhập/xuất.');
            } catch (\Throwable $e) {
                $this->setFlash('error', $e->getMessage());
            }
        } else {
            $this->setFlash('error', 'Vui lòng chọn phân loại và nhập số lượng hợp lệ.');
        }

        $this->redirect('admin/inventory');
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

    private function variantColor($value) {
        $value = trim((string)$value);
        return $value !== '' ? mb_substr($value, 0, 50) : 'Mặc định';
    }

    private function variantSize($value) {
        $value = trim((string)$value);
        return $value !== '' ? mb_substr($value, 0, 50) : 'Mặc định';
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
