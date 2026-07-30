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
        $priceModifier = (float)($_POST['price_modifier'] ?? 0);

        if ($productId > 0 && $size !== '' && $color !== '') {
            $this->productModel->createProductVariant([
                'product_id' => $productId,
                'size' => $size,
                'color' => $color,
                'stock_quantity' => $stockQuantity,
                'price_modifier' => $priceModifier
            ]);
            $this->setFlash('success', 'Variant created. You can choose it now.');
        } else {
            $this->setFlash('error', 'Please choose product, size, and color to create a variant.');
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
            $this->productModel->updateStock($variantId, $quantityChanged, $reason ?: 'Manual inventory update');
            $this->setFlash('success', 'Inventory updated.');
        } else {
            $this->setFlash('error', 'Please choose a variant and quantity.');
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

    private function setFlash($type, $message) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash() {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}

