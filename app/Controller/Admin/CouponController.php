<?php
namespace App\Controller\Admin;

use App\Models\Coupons;
use App\Models\Product;

class CouponController {
    public function index() {
        $model = new Coupons();
        $coupons = $model->getAll('coupons');
        
        $flash = null;
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }
        
        require __DIR__ . '/../../Views/admin/coupons/index.php';
    }
    
    public function create() {
        $productModel = new Product();
        $categories = $productModel->getAllCategories();
        $products = $productModel->getAllProducts(['status' => 1]);
        require __DIR__ . '/../../Views/admin/coupons/create.php';
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discountPercent = max(0, floatval($_POST['discount_percent'] ?? 0));
        $maxDiscount = max(0, floatval($_POST['max_discount'] ?? 0));
        $minOrderAmount = max(0, floatval($_POST['min_order_amount'] ?? 0));
        $usageLimit = max(0, intval($_POST['usage_limit'] ?? 0));
        $usageLimitPerUser = max(1, intval($_POST['usage_limit_per_user'] ?? 1));
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $productId = (int)($_POST['product_id'] ?? 0) ?: null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        
        if (empty($code) || empty($expiryDate) || ($discountPercent <= 0 && $maxDiscount <= 0) || ($categoryId && $productId)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Vui lòng nhập mã, hạn dùng, mức giảm hợp lệ và chỉ chọn một phạm vi áp dụng.'];
            header('Location: ' . BASE_URL . 'admin/coupons/create');
            exit;
        }
        
        $model = new Coupons();
        $existing = $model->getCouponByCode($code);
        if ($existing) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Mã giảm giá đã tồn tại.'];
            header('Location: ' . BASE_URL . 'admin/coupons/create');
            exit;
        }
        
        $model->createCoupon([
            'code' => $code,
            'discount_percent' => $discountPercent > 0 ? $discountPercent : null,
            'max_discount' => $maxDiscount > 0 ? $maxDiscount : null,
            'min_order_amount' => $minOrderAmount,
            'usage_limit' => $usageLimit,
            'usage_limit_per_user' => $usageLimitPerUser,
            'start_date' => $startDate,
            'expiry_date' => $expiryDate,
            'category_id' => $categoryId,
            'product_id' => $productId,
            'status' => 1
        ]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Đã thêm mã giảm giá thành công.'];
        header('Location: ' . BASE_URL . 'admin/coupons');
        exit;
    }
    
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        $model = new Coupons();
        $coupon = $model->getCoupon($id);
        
        if (!$coupon) {
            header('Location: ' . BASE_URL . 'admin/coupons');
            exit;
        }

        $productModel = new Product();
        $categories = $productModel->getAllCategories();
        $products = $productModel->getAllProducts(['status' => 1]);
        
        require __DIR__ . '/../../Views/admin/coupons/edit.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discountPercent = max(0, floatval($_POST['discount_percent'] ?? 0));
        $maxDiscount = max(0, floatval($_POST['max_discount'] ?? 0));
        $minOrderAmount = max(0, floatval($_POST['min_order_amount'] ?? 0));
        $usageLimit = max(0, intval($_POST['usage_limit'] ?? 0));
        $usageLimitPerUser = max(1, intval($_POST['usage_limit_per_user'] ?? 1));
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $productId = (int)($_POST['product_id'] ?? 0) ?: null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        
        if (empty($code) || empty($expiryDate) || ($discountPercent <= 0 && $maxDiscount <= 0) || ($categoryId && $productId)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Vui lòng nhập mã, hạn dùng, mức giảm hợp lệ và chỉ chọn một phạm vi áp dụng.'];
            header('Location: ' . BASE_URL . 'admin/coupons/edit?id=' . $id);
            exit;
        }
        
        $model = new Coupons();
        $existing = $model->getCouponByCode($code);
        if ($existing && $existing['id'] != $id) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Mã giảm giá đã tồn tại.'];
            header('Location: ' . BASE_URL . 'admin/coupons/edit?id=' . $id);
            exit;
        }
        
        $model->updateCoupon($id, [
            'code' => $code,
            'discount_percent' => $discountPercent > 0 ? $discountPercent : null,
            'max_discount' => $maxDiscount > 0 ? $maxDiscount : null,
            'min_order_amount' => $minOrderAmount,
            'usage_limit' => $usageLimit,
            'usage_limit_per_user' => $usageLimitPerUser,
            'start_date' => $startDate,
            'expiry_date' => $expiryDate,
            'category_id' => $categoryId,
            'product_id' => $productId
        ]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Đã cập nhật mã giảm giá thành công.'];
        header('Location: ' . BASE_URL . 'admin/coupons');
        exit;
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)($_POST['id'] ?? 0);
        $model = new Coupons();
        
        if ($model->deleteCoupon($id)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Đã xóa mã giảm giá.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Không thể xóa mã giảm giá.'];
        }
        
        header('Location: ' . BASE_URL . 'admin/coupons');
        exit;
    }
}
