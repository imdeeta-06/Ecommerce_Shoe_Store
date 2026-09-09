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
        $variants = $this->productModel->getInventoryOverview(200);
        $logs = $this->productModel->getInventoryLogs(80);
        $flash = $this->pullFlash();
        require __DIR__ . '/../../Views/admin/inventory/index.php';
    }

    public function createVariant() {
        $productId = (int)($_POST['product_id'] ?? 0);
        $size = $this->variantSize($_POST['size'] ?? '');
        $color = $this->variantColor($_POST['color'] ?? '');
        $stockQuantity = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $priceModifier = max(0, (float)($_POST['price_modifier'] ?? 0));

        $db = \App\Models\Database::getInstance()->getConnection();
        try {
            if ($productId <= 0 || $size === '' || $color === '' || !$this->productModel->getProductForAdmin($productId)) {
                throw new \RuntimeException('Vui lòng chọn sản phẩm tồn tại, size và màu hợp lệ.');
            }
            if ($this->productModel->productVariantExists($productId, $size, $color)) {
                throw new \RuntimeException('Phân loại size/màu này đã tồn tại.');
            }
            $db->beginTransaction();
            $variantId = $this->productModel->createProductVariant([
                'product_id' => $productId,
                'size' => $size,
                'color' => $color,
                'stock_quantity' => 0,
                'price_modifier' => $priceModifier,
                ...$this->variantOperationsPayload()
            ]);
            $this->ensureVariantCodes($variantId, $productId);
            if ($stockQuantity > 0) {
                $this->productModel->updateStock($variantId, $stockQuantity, 'Tồn đầu kỳ khi tạo phân loại sản phẩm');
            }
            $db->commit();
            $this->setFlash('success', 'Đã tạo phân loại sản phẩm.');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
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

    public function updateVariant() {
        $variantId = (int)($_POST['id'] ?? 0);
        $variant = $this->productModel->getProductVariant($variantId);
        if (!$variant) {
            $this->setFlash('error', 'Không tìm thấy phân loại sản phẩm.');
            $this->redirect('admin/inventory');
        }

        $size = $this->variantSize($_POST['size'] ?? '');
        $color = $this->variantColor($_POST['color'] ?? '');
        try {
            if ($this->productModel->productVariantExists((int)$variant['product_id'], $size, $color, $variantId)) {
                throw new \RuntimeException('Phân loại size/màu này đã tồn tại.');
            }
            $newStock = max(0, (int)($_POST['stock_quantity'] ?? 0));
            $this->productModel->updateProductVariant($variantId, [
                'size' => $size,
                'color' => $color,
                'price_modifier' => max(0, (float)($_POST['price_modifier'] ?? 0)),
                ...$this->variantOperationsPayload()
            ]);
            $this->ensureVariantCodes($variantId, (int)$variant['product_id']);
            $stockDelta = $newStock - (int)$variant['stock_quantity'];
            if ($stockDelta !== 0) {
                $this->productModel->updateStock($variantId, $stockDelta, 'Điều chỉnh từ màn hình kho hàng');
            }
            $this->setFlash('success', 'Đã cập nhật phân loại và tồn kho.');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('admin/inventory');
    }

    public function deleteVariant() {
        $variantId = (int)($_POST['id'] ?? 0);
        try {
            if (!$this->productModel->getProductVariant($variantId)) {
                throw new \RuntimeException('Không tìm thấy phân loại sản phẩm.');
            }
            if ($this->productModel->productVariantHasOrderItems($variantId)) {
                throw new \RuntimeException('Phân loại đã phát sinh trong đơn hàng, không thể xóa để giữ lịch sử.');
            }
            $this->productModel->deleteProductVariant($variantId);
            $this->setFlash('success', 'Đã xóa phân loại sản phẩm.');
        } catch (\Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('admin/inventory');
    }

    private function requireAdmin() {
        \App\Middleware\AuthMiddleware::requireAdmin();
    }

    private function redirect($path) {
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }

    private function variantColor($value) {
        $value = trim((string)$value);
        return $value !== '' ? mb_substr($value, 0, 50, 'UTF-8') : 'Mặc định';
    }

    private function variantSize($value) {
        $value = trim((string)$value);
        return $value !== '' ? mb_substr($value, 0, 50, 'UTF-8') : 'Mặc định';
    }

    private function variantOperationsPayload(): array {
        return [
            'sku' => trim((string)($_POST['sku'] ?? '')) ?: null,
            'barcode' => trim((string)($_POST['barcode'] ?? '')) ?: null,
            'cost_price' => max(0, (float)($_POST['cost_price'] ?? 0)),
            'weight_grams' => max(1, (int)($_POST['weight_grams'] ?? 500)),
            'length_cm' => max(0.1, (float)($_POST['length_cm'] ?? 25)),
            'width_cm' => max(0.1, (float)($_POST['width_cm'] ?? 20)),
            'height_cm' => max(0.1, (float)($_POST['height_cm'] ?? 5))
        ];
    }

    private function ensureVariantCodes(int $variantId, int $productId): void {
        $variant = $this->productModel->getProductVariant($variantId);
        $update = [];
        if (empty($variant['sku'])) $update['sku'] = 'LH-' . $productId . '-' . $variantId;
        if (empty($variant['barcode'])) $update['barcode'] = '893' . str_pad((string)$variantId, 10, '0', STR_PAD_LEFT);
        if ($update) $this->productModel->updateProductVariant($variantId, $update);
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
