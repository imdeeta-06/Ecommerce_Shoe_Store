<?php
require_once __DIR__ . '/../_helpers.php';
$statusLabels = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị', 'shipping' => 'Đang giao', 'delivered' => 'Giao thành công', 'completed' => 'Hoàn thành', 'canceled' => 'Đã hủy'];
$shippingLabels = ['not_shipped' => 'Chưa bàn giao', 'packing' => 'Đang đóng gói', 'in_transit' => 'Đang vận chuyển', 'delivered' => 'Đã giao', 'returned' => 'Hoàn về', 'canceled' => 'Đã hủy'];
adminStart('Chi tiết đơn hàng ' . $order['order_code'], 'orders', !empty($flash) ? ['type' => ($flash['error'] ?? null) ? 'error' : 'success', 'message' => implode(' ', $flash)] : null);
?>
<div class="admin-actions" style="margin-bottom:1.5rem;"><a class="admin-btn light" href="<?= BASE_URL ?>admin/orders">← Danh sách đơn</a></div>
<div class="admin-grid">
    <section class="admin-panel">
        <h2 class="admin-panel-title">Luồng xử lý đơn</h2>
        <p style="margin-bottom:1rem;">Hiện tại: <strong><?= adminE($statusLabels[$order['status']] ?? $order['status']) ?></strong></p>
        <form method="post" action="<?= BASE_URL ?>admin/orders/status">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <div class="admin-field"><label>Chuyển trạng thái</label><select name="status"><?php foreach ($statusLabels as $status => $label): ?><option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
            <div class="admin-field"><label>Ghi chú</label><textarea name="note" rows="3" placeholder="Ví dụ: Đã xác nhận đủ tồn kho..."></textarea></div>
            <button class="admin-btn primary" type="submit">Cập nhật trạng thái</button>
        </form>
    </section>
    <section class="admin-panel">
        <h2 class="admin-panel-title">Thông tin giao hàng</h2>
        <form method="post" action="<?= BASE_URL ?>admin/orders/shipping">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <div class="admin-field"><label>Đơn vị vận chuyển</label><input name="shipping_carrier" value="<?= adminE($order['shipping_carrier'] ?? '') ?>" placeholder="GHN, GHTK, Viettel Post..."></div>
            <div class="admin-field"><label>Mã vận đơn</label><input name="tracking_code" value="<?= adminE($order['tracking_code'] ?? '') ?>" placeholder="Nhập mã vận đơn"></div>
            <div class="admin-grid"><div class="admin-field"><label>Phí ship (VNĐ)</label><input type="number" min="0" name="shipping_fee" value="<?= (float)($order['shipping_fee'] ?? 0) ?>"></div><div class="admin-field"><label>Trạng thái giao</label><input value="<?= adminE($shippingLabels[$order['shipping_status'] ?? 'not_shipped'] ?? ($order['shipping_status'] ?? 'not_shipped')) ?>" readonly><small style="display:block;color:#666;margin-top:.35rem;">Trạng thái này tự đồng bộ theo “Luồng xử lý đơn” ở bên trái.</small></div></div>
            <button class="admin-btn primary" type="submit">Lưu giao hàng</button>
        </form>
    </section>
</div>
<section class="admin-panel"><h2 class="admin-panel-title">Người nhận</h2><p><strong><?= adminE($order['shipping_name']) ?></strong> · <?= adminE($order['shipping_phone']) ?></p><p><?= adminE($order['shipping_address']) ?></p><p style="color:#666;">Ghi chú: <?= adminE($order['customer_note'] ?? 'Không có') ?></p></section>
<section class="admin-panel"><h2 class="admin-panel-title">Xác nhận thỏa thuận điện tử</h2><p>Trạng thái: <strong><?= !empty($order['terms_accepted']) ? 'Đã đồng ý' : 'Chưa có dữ liệu (đơn cũ)' ?></strong></p><p>Phiên bản điều khoản: <?= adminE($order['contract_version'] ?? 'v1.0') ?> · Thời điểm: <?= adminE($order['terms_accepted_at'] ?? 'Chưa ghi nhận') ?></p><?php if (!empty($order['terms_accepted_ip'])): ?><p style="color:#666;">IP: <?= adminE($order['terms_accepted_ip']) ?> · Trình duyệt: <?= adminE($order['terms_accepted_user_agent'] ?? '') ?></p><?php endif; ?></section>
<section class="admin-panel"><h2 class="admin-panel-title">Thanh toán</h2><p>Phương thức: <strong><?= adminE(strtoupper($order['payment']['payment_method'] ?? 'COD')) ?></strong></p><p>Trạng thái: <strong><?= adminE(['pending' => 'Chờ thanh toán', 'paid' => 'Đã thanh toán', 'canceled' => 'Đã hủy', 'refunded' => 'Đã hoàn tiền'][$order['payment']['payment_state'] ?? 'pending'] ?? ($order['payment']['payment_state'] ?? 'Chờ thanh toán')) ?></strong></p><?php if (!empty($order['payment']['transaction_code'])): ?><p>Mã giao dịch: <?= adminE($order['payment']['transaction_code']) ?></p><?php endif; ?><?php if (!empty($order['payment']['refund_transaction_code'])): ?><p>Mã hoàn tiền: <?= adminE($order['payment']['refund_transaction_code']) ?></p><?php endif; ?></section>
<section class="admin-panel"><h2 class="admin-panel-title">Sản phẩm trong đơn</h2><div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Sản phẩm</th><th>Size</th><th>Màu</th><th>SL</th><th>Đơn giá tại thời điểm mua</th></tr></thead><tbody><?php foreach ($order['items'] as $item): ?><tr><td><?= adminE($item['product_name'] ?? 'Sản phẩm cũ') ?></td><td><?= adminE($item['size'] ?? '') ?></td><td><?= adminE($item['color'] ?? '') ?></td><td><?= (int)$item['quantity'] ?></td><td><?= adminMoney($item['price_at_time']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="admin-panel"><h2 class="admin-panel-title">Lịch sử trạng thái</h2><?php foreach ($order['status_logs'] as $log): ?><p style="padding:.5rem 0;border-bottom:1px solid #eee;"><strong><?= adminE($statusLabels[$log['status']] ?? $log['status']) ?></strong> · <?= adminE($log['created_at']) ?> · <?= adminE($log['note'] ?? '') ?></p><?php endforeach; ?><?php if (empty($order['status_logs'])): ?><p style="color:#666;">Chưa có lịch sử cập nhật.</p><?php endif; ?></section>
<?php adminEnd(); ?>
