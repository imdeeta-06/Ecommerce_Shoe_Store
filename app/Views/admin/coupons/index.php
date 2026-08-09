<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Mã giảm giá', 'coupons', $flash ?? null); 
?>

<div class="admin-title" style="margin-bottom: 2rem;">
    <div>
        <h1>Mã giảm giá</h1>
        <p>Quản lý các chương trình khuyến mãi.</p>
    </div>
    <a href="<?= BASE_URL ?>admin/coupons/create" class="admin-btn primary">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 6px;"><path d="M12 5v14M5 12h14"></path></svg>
        Tạo mã mới
    </a>
</div>

<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Mã Code</th>
                <th>Mức giảm</th>
                <th>Điều kiện</th>
                <th>Phạm vi / mỗi khách</th>
                <th>Đã dùng / Tổng</th>
                <th>Hạn sử dụng</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($coupons as $coupon): 
                $now = date('Y-m-d H:i:s');
                $isValid = true;
                $statusLabel = 'Khả dụng';
                $statusClass = 'success';

                if (!empty($coupon['expiry_date']) && $now > $coupon['expiry_date']) {
                    $isValid = false;
                    $statusLabel = 'Đã hết hạn';
                    $statusClass = 'error';
                } elseif (!empty($coupon['usage_limit']) && $coupon['used_count'] >= $coupon['usage_limit']) {
                    $isValid = false;
                    $statusLabel = 'Hết lượt';
                    $statusClass = 'error';
                } elseif (!empty($coupon['start_date']) && $now < $coupon['start_date']) {
                    $isValid = false;
                    $statusLabel = 'Chưa đến hạn';
                    $statusClass = 'warning';
                }
                
                $discountText = $coupon['discount_percent'] ? '-'.$coupon['discount_percent'].'%' : '-'.number_format($coupon['max_discount'], 0, ',', '.').'₫';
                $conditionText = $coupon['min_order_amount'] > 0 ? 'Đơn tối thiểu '.number_format($coupon['min_order_amount'], 0, ',', '.').'đ' : 'Không điều kiện';
                $scopeText = !empty($coupon['product_id']) ? 'Sản phẩm #' . (int)$coupon['product_id'] : (!empty($coupon['category_id']) ? 'Danh mục #' . (int)$coupon['category_id'] : 'Toàn bộ đơn');
                $scopeText .= ' · ' . max(1, (int)($coupon['usage_limit_per_user'] ?? 1)) . ' lần/người';
            ?>
            <tr>
                <td><strong style="font-family: monospace; font-size: 1.1rem; color: #111; letter-spacing: 1px;"><?= htmlspecialchars($coupon['code']) ?></strong></td>
                <td><strong style="color: #ef4444;"><?= $discountText ?></strong></td>
                <td style="color: #555;"><?= htmlspecialchars($conditionText) ?></td>
                <td style="color: #555;"><?= htmlspecialchars($scopeText) ?></td>
                <td style="font-weight: 600;"><?= $coupon['used_count'] ?> / <span style="color: #888;"><?= $coupon['usage_limit'] ?: '∞' ?></span></td>
                <td>
                    <?php if ($isValid): ?>
                        <span style="color: #666;"><?= date('d/m/Y', strtotime($coupon['expiry_date'])) ?></span>
                    <?php else: ?>
                        <span style="color: #ef4444; font-weight: bold;"><?= date('d/m/Y', strtotime($coupon['expiry_date'])) ?></span>
                    <?php endif; ?>
                </td>
                <td><span class="admin-badge <?= $statusClass ?>"><?= mb_strtoupper($statusLabel, 'UTF-8') ?></span></td>
                <td>
                    <div class="admin-actions">
                        <a href="<?= BASE_URL ?>admin/coupons/edit?id=<?= (int)$coupon['id'] ?>" class="admin-btn-sm admin-btn light">Sửa</a>
                        <form method="post" action="<?= BASE_URL ?>admin/coupons/delete" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?')" style="margin:0;">
                            <input type="hidden" name="id" value="<?= (int)$coupon['id'] ?>">
                            <button type="submit" class="admin-btn-sm admin-btn danger">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($coupons)): ?>
            <tr><td colspan="8" style="text-align: center; padding: 3rem 1rem; color: #888;">Chưa có mã giảm giá nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php adminEnd(); ?>
