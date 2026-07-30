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
        $stmt = $this->db->prepare("SELECT * FROM coupons WHERE code = :code");
        $stmt->execute(['code' => $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Kiểm tra tính hợp lệ của mã giảm giá (dựa trên thời gian, lượt sử dụng, đơn tối thiểu)
    public function validateCoupon($code, $orderTotal) {
        $coupon = $this->getCouponByCode($code);
        
        if (!$coupon) {
            return ['is_valid' => false, 'message' => 'Mã giảm giá không tồn tại.'];
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

        // Kiểm tra đơn tối thiểu
        if ($orderTotal < $coupon['min_order_amount']) {
            return ['is_valid' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ('.number_format($coupon['min_order_amount']).'đ) để sử dụng mã này.'];
        }

        return ['is_valid' => true, 'data' => $coupon];
    }
    
    // Tăng số lượt sử dụng mã giảm giá lên 1 (gọi khi đặt hàng thành công)
    public function incrementUsage($id) {
        $stmt = $this->db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
