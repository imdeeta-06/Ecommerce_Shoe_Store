<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Chỉnh sửa mã giảm giá', 'coupons'); 
?>

<div class="admin-panel" style="max-width: 800px;">
    <form method="post" action="<?= BASE_URL ?>admin/coupons/update">
        <input type="hidden" name="id" value="<?= (int)$coupon['id'] ?>">
        
        <div class="admin-grid" style="margin-bottom: 1.25rem;">
            <div class="admin-field">
                <label>Mã giảm giá (Code) *</label>
                <input type="text" name="code" required value="<?= adminE($coupon['code']) ?>">
            </div>
        </div>

        <div class="admin-grid" style="margin-bottom: 1.25rem;">
            <div class="admin-field">
                <label>Giới hạn mỗi khách hàng</label>
                <input type="number" name="usage_limit_per_user" min="1" step="1" value="<?= max(1, (int)($coupon['usage_limit_per_user'] ?? 1)) ?>">
            </div>
            <div class="admin-field">
                <label>Áp dụng theo danh mục</label>
                <select name="category_id"><option value="">Tất cả danh mục</option><?php foreach (($categories ?? []) as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)($coupon['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>><?= adminE($category['name']) ?></option><?php endforeach; ?></select>
            </div>
        </div>
        <div class="admin-field">
            <label>Hoặc chỉ áp dụng cho sản phẩm</label>
            <select name="product_id"><option value="">Tất cả sản phẩm</option><?php foreach (($products ?? []) as $product): ?><option value="<?= (int)$product['id'] ?>" <?= (int)($coupon['product_id'] ?? 0) === (int)$product['id'] ? 'selected' : '' ?>><?= adminE($product['name']) ?></option><?php endforeach; ?></select>
        </div>

        <div class="admin-grid" style="margin-bottom: 1.25rem;">
            <div class="admin-field">
                <label>Giảm theo phần trăm (%)</label>
                <input type="number" name="discount_percent" min="0" max="100" step="0.01" value="<?= $coupon['discount_percent'] ? (float)$coupon['discount_percent'] : '' ?>">
                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">Để trống nếu giảm theo số tiền cố định.</small>
            </div>
            
            <div class="admin-field">
                <label>Giảm tối đa / Cố định (VNĐ)</label>
                <input type="number" name="max_discount" min="0" step="1" value="<?= $coupon['max_discount'] ? (int)$coupon['max_discount'] : '' ?>">
                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">Nếu có Giảm %, đây là mức giảm tối đa. Nếu không có Giảm %, đây là số tiền giảm cố định.</small>
            </div>
        </div>

        <div class="admin-grid" style="margin-bottom: 1.25rem;">
            <div class="admin-field">
                <label>Đơn hàng tối thiểu (VNĐ)</label>
                <input type="number" name="min_order_amount" min="0" step="1" value="<?= (int)$coupon['min_order_amount'] ?>">
            </div>
            
            <div class="admin-field">
                <label>Giới hạn số lượt dùng</label>
                <input type="number" name="usage_limit" min="0" step="1" value="<?= (int)$coupon['usage_limit'] ?>">
                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">0 = Không giới hạn. Hiện tại đã dùng: <?= (int)$coupon['used_count'] ?> lượt.</small>
            </div>
        </div>

        <div class="admin-grid" style="margin-bottom: 1.25rem;">
            <div class="admin-field">
                <label>Ngày bắt đầu</label>
                <input type="datetime-local" name="start_date" value="<?= $coupon['start_date'] ? date('Y-m-d\TH:i', strtotime($coupon['start_date'])) : '' ?>">
                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">Để trống nếu có hiệu lực ngay lập tức.</small>
            </div>
            
            <div class="admin-field">
                <label>Ngày kết thúc *</label>
                <input type="datetime-local" name="expiry_date" required value="<?= $coupon['expiry_date'] ? date('Y-m-d\TH:i', strtotime($coupon['expiry_date'])) : '' ?>">
            </div>
        </div>

        <div class="admin-actions">
            <button type="submit" class="admin-btn">Cập nhật mã giảm giá</button>
            <a href="<?= BASE_URL ?>admin/coupons" class="admin-btn light">Hủy</a>
        </div>
    </form>
</div>

<?php adminEnd(); ?>
