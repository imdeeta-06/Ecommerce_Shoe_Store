<?php

namespace App\Models;

use PDO;

class Coupons extends BaseModel {
    public function __construct() {
        parent::__construct();
        // Xử lý logic cho các bảng: coupons
    }

    // --- COUPONS ---
    public function createCoupon($data) { return $this->insert('coupons', $data); }
    public function getCoupon($id) { return $this->getById('coupons', $id); }
    public function updateCoupon($id, $data) { return $this->update('coupons', $id, $data); }
    public function deleteCoupon($id) { return $this->delete('coupons', $id); }

    public function getCouponByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM coupons WHERE UPPER(code) = UPPER(:code) LIMIT 1");
        $stmt->execute(['code' => trim((string)$code)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Kiểm tra tính hợp lệ của mã giảm giá (dựa trên thời gian, lượt sử dụng, đơn tối thiểu)
    public function validateCoupon($code, $orderTotal, $userId = null, array $items = []) {
        $coupon = $this->getCouponByCode($code);
        return $this->validateCouponData($coupon, $orderTotal, $userId, $items);
    }

    public function validateCouponById($couponId, $orderTotal, $userId = null, array $items = [], $forUpdate = false) {
        $sql = 'SELECT * FROM coupons WHERE id = :id LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => (int)$couponId]);
        return $this->validateCouponData($stmt->fetch(PDO::FETCH_ASSOC), $orderTotal, $userId, $items);
    }

    public function calculateDiscount(array $coupon, float $orderTotal): float {
        $percent = (float)($coupon['discount_percent'] ?? 0);
        $fixedOrMax = (float)($coupon['max_discount'] ?? 0);

        if ($percent > 0) {
            $discount = $orderTotal * ($percent / 100);
            if ($fixedOrMax > 0) {
                $discount = min($discount, $fixedOrMax);
            }
            return min($orderTotal, max(0, $discount));
        }

        return min($orderTotal, max(0, $fixedOrMax));
    }

    public function reserveUsage($couponId, $userId, $orderId): bool {
        $stmt = $this->db->prepare("UPDATE coupons
            SET used_count = used_count + 1
            WHERE id = :id AND (usage_limit = 0 OR used_count < usage_limit)");
        $stmt->execute(['id' => (int)$couponId]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }

        $stmt = $this->db->prepare("INSERT INTO coupon_usages (coupon_id, user_id, order_id) VALUES (:coupon_id, :user_id, :order_id)");
        $stmt->execute([
            'coupon_id' => (int)$couponId,
            'user_id' => (int)$userId,
            'order_id' => (int)$orderId
        ]);
        return true;
    }

    private function validateCouponData($coupon, $orderTotal, $userId, array $items) {
        
        if (!$coupon) {
            return ['is_valid' => false, 'message' => 'Mã giảm giá không tồn tại.'];
        }

        if (isset($coupon['status']) && (int)$coupon['status'] !== 1) {
            return ['is_valid' => false, 'message' => 'Mã giảm giá hiện không hoạt động.'];
        }

        $now = date('Y-m-d H:i:s');

        // Kiểm tra ngày bắt đầu (nếu có)
        if (!empty($coupon['start_date']) && $now < $coupon['start_date']) {
            return ['is_valid' => false, 'message' => 'Mã giảm giá chưa đến thời gian sử dụng.'];
        }

        // Kiểm tra ngày hết hạn
        if (!empty($coupon['expiry_date']) && $now > $coupon['expiry_date']) {
            return ['is_valid' => false, 'message' => 'Mã giảm giá đã hết hạn.'];
        }

        // Kiểm tra lượt sử dụng (nếu có giới hạn)
        if (!empty($coupon['usage_limit']) && $coupon['used_count'] >= $coupon['usage_limit']) {
            return ['is_valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.'];
        }

        $orderTotal = max(0, (float)$orderTotal);
        $eligibleSubtotal = $this->calculateEligibleSubtotal($coupon, $items, $orderTotal);
        $hasScope = !empty($coupon['product_id']) || !empty($coupon['category_id']);

        if ($hasScope && $eligibleSubtotal <= 0) {
            return ['is_valid' => false, 'message' => 'Mã giảm giá không áp dụng cho sản phẩm trong giỏ hàng.'];
        }

        // Với coupon có phạm vi, đơn tối thiểu được tính trên phần sản phẩm
        // đủ điều kiện, không tính nhầm cả những sản phẩm ngoài phạm vi.
        if ($eligibleSubtotal < (float)$coupon['min_order_amount']) {
            return ['is_valid' => false, 'message' => 'Phần sản phẩm áp dụng mã chưa đạt giá trị tối thiểu ('.number_format($coupon['min_order_amount']).'đ).'];
        }

        if ($userId && !empty($coupon['usage_limit_per_user'])) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = :coupon_id AND user_id = :user_id');
            $stmt->execute(['coupon_id' => (int)$coupon['id'], 'user_id' => (int)$userId]);
            if ((int)$stmt->fetchColumn() >= (int)$coupon['usage_limit_per_user']) {
                return ['is_valid' => false, 'message' => 'Bạn đã sử dụng mã giảm giá này đủ số lần cho phép.'];
            }
        }

        return [
            'is_valid' => true,
            'data' => $coupon,
            'eligible_subtotal' => $eligibleSubtotal,
            'discount' => $this->calculateDiscount($coupon, $eligibleSubtotal)
        ];
    }

    private function calculateEligibleSubtotal(array $coupon, array $items, float $fallbackTotal): float {
        $hasScope = !empty($coupon['product_id']) || !empty($coupon['category_id']);
        if (!$hasScope && empty($items)) {
            return $fallbackTotal;
        }

        $eligibleSubtotal = 0.0;
        foreach ($items as $item) {
            if (!$this->itemMatchesCouponScope($coupon, $item)) {
                continue;
            }

            $unitPrice = array_key_exists('price_at_time', $item)
                ? (float)$item['price_at_time']
                : (float)($item['price'] ?? 0);
            $quantity = max(0, (int)($item['quantity'] ?? $item['qty'] ?? 0));
            $eligibleSubtotal += max(0, $unitPrice) * $quantity;
        }

        return $eligibleSubtotal;
    }

    private function itemMatchesCouponScope(array $coupon, array $item): bool {
        $productId = (int)($item['product_id'] ?? 0);
        if (!empty($coupon['product_id'])) {
            return $productId === (int)$coupon['product_id'];
        }

        if (!empty($coupon['category_id'])) {
            $categoryId = array_key_exists('category_id', $item) && $item['category_id'] !== null
                ? (int)$item['category_id']
                : null;

            if ($categoryId === null && $productId > 0) {
                $stmt = $this->db->prepare('SELECT category_id FROM product WHERE id = :product_id');
                $stmt->execute(['product_id' => $productId]);
                $categoryId = $stmt->fetchColumn();
            }

            return (int)$categoryId === (int)$coupon['category_id'];
        }

        return true;
    }
    
    // Tăng số lượt sử dụng mã giảm giá lên 1 (gọi khi đặt hàng thành công)
    public function incrementUsage($id) {
        $stmt = $this->db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
