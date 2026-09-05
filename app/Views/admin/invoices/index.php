<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Hóa đơn bán hàng', 'invoices', !empty($flash) ? [
    'type' => isset($flash['error']) ? 'error' : 'success',
    'message' => implode(' ', $flash),
] : null);
?>

<p style="color:#666;line-height:1.7;margin:-1rem 0 1.5rem;">Hộ kinh doanh áp dụng phương pháp trực tiếp trên doanh thu: giá bán và tổng tiền là số tiền thanh toán gộp, không tách thuế GTGT trên hóa đơn bán hàng. Dữ liệu đồ án không thay thế hóa đơn điện tử hợp pháp.</p>

<div class="admin-grid" style="align-items:start;">
    <section class="admin-panel" style="margin-bottom:0;">
        <h2 class="admin-panel-title">Phát hành cho đơn đủ điều kiện</h2>
        <form method="post" action="<?= BASE_URL ?>admin/invoices/issue">
            <div class="admin-field"><label>Đơn đã giao và đã thanh toán</label><select name="order_id" required><option value="">-- Chọn đơn hàng --</option><?php foreach ($eligibleOrders as $order): ?><option value="<?= (int)$order['id'] ?>"><?= adminE($order['order_code'] . ' — ' . $order['shipping_name'] . ' — ' . number_format($order['final_amount'], 0, ',', '.') . ' ₫') ?></option><?php endforeach; ?></select></div>
            <div class="admin-field"><label>Tên người mua</label><input name="buyer_name" placeholder="Mặc định lấy theo người nhận"></div>
            <div class="admin-field"><label>Mã số thuế người mua (nếu có)</label><input name="buyer_tax_code" maxlength="30" placeholder="Ví dụ: 0101234567"></div>
            <div class="admin-field"><label>Địa chỉ người mua</label><input name="buyer_address" placeholder="Mặc định lấy theo địa chỉ giao hàng"></div>
            <button class="admin-btn primary" <?= empty($eligibleOrders) ? 'disabled' : '' ?> style="width:100%;justify-content:center;">Phát hành hóa đơn bán hàng</button>
        </form>
    </section>

    <section class="admin-panel" style="margin-bottom:0;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h2 class="admin-panel-title" style="margin:0;">Sổ doanh thu hóa đơn</h2>
            <form method="get"><input type="month" name="month" value="<?= adminE($report['month']) ?>" onchange="this.form.submit()"></form>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;">
            <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:1rem;border-radius:8px;"><small>SỐ HÓA ĐƠN</small><div style="font-size:1.5rem;font-weight:700;"><?= (int)$report['invoice_count'] ?></div></div>
            <div style="background:#fef2f2;border:1px solid #fecaca;padding:1rem;border-radius:8px;"><small>ĐÃ HỦY</small><div style="font-size:1.5rem;font-weight:700;color:#b91c1c;"><?= (int)$report['canceled_count'] ?></div></div>
            <div style="background:#eef6f4;border:1px solid #b9d8d3;padding:1rem;border-radius:8px;"><small>TỔNG THANH TOÁN</small><div style="font-size:1.25rem;font-weight:700;color:#245b55;"><?= adminMoney($report['total_amount']) ?></div></div>
        </div>
    </section>
</div>

<section class="admin-panel" style="margin-top:1.5rem;">
    <h2 class="admin-panel-title">Sổ hóa đơn bán hàng</h2>
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead><tr><th>Mẫu số/Ký hiệu/Số</th><th>Đơn hàng</th><th>Loại</th><th>Ngày phát hành</th><th>Tổng thanh toán</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>invoice/view?id=<?= (int)$invoice['id'] ?>" target="_blank" rel="noopener" style="font-weight:600;color:#2563eb;text-decoration:underline;"><?= adminE(substr($invoice['invoice_series'], 0, 1) . '/' . substr($invoice['invoice_series'], 1) . '/' . str_pad($invoice['invoice_number'], 7, '0', STR_PAD_LEFT)) ?></a></td>
                    <td><strong><?= adminE($invoice['order_code']) ?></strong></td>
                    <td><?= $invoice['invoice_type'] === 'adjustment' ? 'Điều chỉnh' : 'Gốc' ?></td>
                    <td><?= adminE($invoice['issued_at']) ?></td>
                    <td><strong><?= adminMoney($invoice['total_amount']) ?></strong></td>
                    <td><span class="admin-badge <?= $invoice['status'] === 'canceled' ? 'error' : 'success' ?>"><?= adminE($invoice['status'] === 'canceled' ? 'Đã hủy' : 'Hợp lệ') ?></span></td>
                    <td>
                    <?php if ($invoice['invoice_type'] === 'original' && $invoice['status'] !== 'canceled'): ?>
                        <details><summary style="cursor:pointer;color:#2563eb;">Điều chỉnh/Hủy</summary>
                            <div style="width:270px;padding:1rem;border:1px solid #ddd;background:#fff;position:absolute;z-index:10;">
                                <form method="post" action="<?= BASE_URL ?>admin/invoices/adjust" style="padding-bottom:1rem;margin-bottom:1rem;border-bottom:1px solid #eee;">
                                    <input type="hidden" name="invoice_id" value="<?= (int)$invoice['id'] ?>">
                                    <div class="admin-field"><label>Tổng tiền điều chỉnh (+/-)</label><input name="total_delta" type="number" step="1000" required placeholder="Ví dụ: -50000"></div>
                                    <div class="admin-field"><label>Lý do</label><input name="reason" required maxlength="500"></div>
                                    <button class="admin-btn-sm admin-btn primary">Lưu điều chỉnh</button>
                                </form>
                                <form method="post" action="<?= BASE_URL ?>admin/invoices/cancel" onsubmit="return confirm('Hủy hóa đơn này?')">
                                    <input type="hidden" name="invoice_id" value="<?= (int)$invoice['id'] ?>">
                                    <div class="admin-field"><label>Lý do hủy</label><input name="reason" required maxlength="500"></div>
                                    <button class="admin-btn-sm admin-btn danger">Hủy hóa đơn</button>
                                </form>
                            </div>
                        </details>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$invoices): ?><tr><td colspan="7" style="text-align:center;padding:2rem;color:#6b7280;">Chưa có hóa đơn trong kỳ.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php adminEnd(); ?>
