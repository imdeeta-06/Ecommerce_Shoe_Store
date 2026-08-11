<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Quản lý đơn hàng', 'orders', !empty($flash) ? ['type' => ($flash['error'] ?? null) ? 'error' : 'success', 'message' => implode(' ', $flash)] : null);
$statusLabels = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị', 'shipping' => 'Đang giao', 'delivered' => 'Giao thành công', 'completed' => 'Hoàn thành', 'canceled' => 'Đã hủy'];
?>
<div class="admin-panel">
    <form method="get" action="<?= BASE_URL ?>admin/orders" class="admin-grid" style="align-items:end;">
        <div class="admin-field"><label>Tìm mã đơn / khách hàng</label><input name="keyword" value="<?= adminE($filters['keyword']) ?>" placeholder="ORD-... hoặc tên, số điện thoại"></div>
        <div class="admin-field"><label>Trạng thái</label><select name="status"><option value="">Tất cả</option><?php foreach ($statusLabels as $status => $label): ?><option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
        <button class="admin-btn primary" type="submit">Lọc đơn hàng</button>
    </form>
</div>
<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái đơn</th><th>Giao hàng</th><th>Ngày đặt</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><strong><?= adminE($order['order_code']) ?></strong></td>
                <td><?= adminE($order['user_name'] ?? $order['shipping_name']) ?><br><small><?= adminE($order['shipping_phone']) ?></small></td>
                <td><?= adminMoney($order['final_amount']) ?></td>
                <td><span class="admin-badge <?= $order['status'] === 'canceled' ? 'error' : ($order['status'] === 'completed' ? 'success' : 'neutral') ?>"><?= adminE($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                <td><?= adminE($order['shipping_carrier'] ?? 'Chưa chọn đơn vị') ?><br><small><?= adminE($order['tracking_code'] ?? 'Chưa có mã vận đơn') ?></small></td>
                <td><?= adminE($order['created_at']) ?></td>
                <td><a class="admin-btn-sm admin-btn light" href="<?= BASE_URL ?>admin/orders/view?id=<?= (int)$order['id'] ?>">Chi tiết</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?><tr><td colspan="7" style="text-align:center;padding:2rem;color:#666;">Chưa có đơn hàng.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php adminEnd(); ?>
