<?php adminStart('Thêm mã giảm giá', 'coupons'); ?>

<div class="admin-panel" style="max-width: 800px;">
    <form method="post" action="<?= BASE_URL ?>admin/coupons/store">
        <div class="admin-grid" style="margin-bottom: 1.25rem;">
            <div class="admin-field">
                <label>Mã giảm giá (Code) *</label>
                <input type="text" name="code" required placeholder="VD: TET2024">
            </div>
        </div>

        <div class="admin-grid" style="margin-bottom: 1.25rem;">
            <div class="admin-field">
                <label>Giảm theo phần trăm (%)</label>
                <input type="number" name="discount_percent" min="0" max="100" step="0.01" placeholder="VD: 10">
                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">Để trống nếu giảm theo số tiền cố định.</small>
            </div>
            
            <div class="admin-field">
                <label>Giảm tối đa / Cố định (VNĐ)</label>
                <input type="number" name="max_discount" min="0" step="1" placeholder="VD: 50000">
                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">Nếu có Giảm %, đây là mức giảm tối đa. Nếu không có Giảm %, đây là số tiền giảm cố định.</small>
            </div>
        </div>

        <div class="admin-grid" style="margin-bottom: 1.25rem;">
            <div class="admin-field">
                <label>Đơn hàng tối thiểu (VNĐ)</label>
                <input type="number" name="min_order_amount" min="0" step="1" value="0">
            </div>
            
            <div class="admin-field">
                <label>Giới hạn số lượt dùng</label>
                <input type="number" name="usage_limit" min="0" step="1" value="0">
                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">0 = Không giới hạn.</small>
            </div>
        </div>

        <div class="admin-grid" style="margin-bottom: 1.25rem;">
            <div class="admin-field">
                <label>Ngày bắt đầu</label>
                <input type="datetime-local" name="start_date">
                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">Để trống nếu có hiệu lực ngay lập tức.</small>
            </div>
            
            <div class="admin-field">
                <label>Ngày kết thúc *</label>
                <input type="datetime-local" name="expiry_date" required>
            </div>
        </div>

        <div class="admin-actions">
            <button type="submit" class="admin-btn">Lưu mã giảm giá</button>
            <a href="<?= BASE_URL ?>admin/coupons" class="admin-btn light">Hủy</a>
        </div>
    </form>
</div>

<?php adminEnd(); ?>
