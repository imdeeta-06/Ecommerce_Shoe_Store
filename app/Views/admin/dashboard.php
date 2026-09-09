<?php
require_once __DIR__ . '/_helpers.php';

$dashboardStatusLabels = [
    'pending' => 'Mới',
    'confirmed' => 'Đã xác nhận',
    'preparing' => 'Đang chuẩn bị',
    'shipping' => 'Đang giao',
    'delivered' => 'Đã giao',
    'completed' => 'Hoàn thành',
    'canceled' => 'Đã huỷ',
];
$dashboardStatusColors = ['#3b82f6', '#60a5fa', '#f59e0b', '#f97316', '#10b981', '#059669', '#ef4444'];
$statusChartLabels = [];
$statusChartData = [];
$statusChartColors = [];
foreach ($dashboardStatusLabels as $status => $label) {
    if ((int)($dashboard['status_counts'][$status] ?? 0) > 0) {
        $statusChartLabels[] = $label;
        $statusChartData[] = (int)$dashboard['status_counts'][$status];
        $statusChartColors[] = $dashboardStatusColors[count($statusChartColors) % count($dashboardStatusColors)];
    }
}

adminStart('Bảng điều khiển', 'dashboard', $flash ?? null);
?>

<style nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
.dashboard-stat-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 2rem; }
.dashboard-stat-grid .stat-card { min-width: 0; }
.dashboard-stat-grid .stat-value { overflow-wrap: anywhere; }
.dashboard-chart-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
.dashboard-chart-grid .admin-panel { margin-bottom: 0; min-width: 0; }
.dashboard-chart-wrap { position: relative; min-height: 270px; }
.dashboard-status-wrap { position: relative; min-height: 270px; display: flex; align-items: center; justify-content: center; }
.dashboard-chart-fallback { display: none; color: var(--admin-text-light); text-align: center; padding: 2rem; }
.dashboard-table-title { padding: 1.5rem 1.5rem 0; border: 0; margin: 0; }
@media (max-width: 1180px) {
    .dashboard-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .dashboard-chart-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .dashboard-stat-grid { grid-template-columns: 1fr; }
    .dashboard-chart-wrap, .dashboard-status-wrap { min-height: 230px; }
}
</style>

<section class="admin-grid dashboard-stat-grid" aria-label="Chỉ số bảng điều khiển">
    <article class="stat-card">
        <div class="stat-header">
            <div><div class="stat-title">Tổng doanh thu</div><div class="stat-value"><?= adminMoney($dashboard['revenue']) ?></div></div>
            <div class="stat-icon" style="background:#e8f5e9;color:#4caf50">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            </div>
        </div>
        <div class="stat-change up">Đã giao, đã thanh toán, trừ tiền đã hoàn</div>
    </article>

    <article class="stat-card">
        <div class="stat-header">
            <div><div class="stat-title">Đơn hàng mới</div><div class="stat-value"><?= number_format($dashboard['new_order_count']) ?></div></div>
            <div class="stat-icon" style="background:#e3f2fd;color:#2196f3">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0"></path></svg>
            </div>
        </div>
        <div class="stat-change up">Đơn trong tháng này</div>
    </article>

    <article class="stat-card">
        <div class="stat-header">
            <div><div class="stat-title">Khách hàng</div><div class="stat-value"><?= number_format($dashboard['customer_count']) ?></div></div>
            <div class="stat-icon" style="background:#f3e5f5;color:#9c27b0">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
        </div>
        <div class="stat-change up">+<?= number_format($dashboard['new_customer_count']) ?> thành viên mới tháng này</div>
    </article>

    <article class="stat-card">
        <div class="stat-header">
            <div><div class="stat-title">Sản phẩm</div><div class="stat-value"><?= number_format($dashboard['product_count']) ?></div></div>
            <div class="stat-icon" style="background:#fff3e0;color:#ff9800">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </div>
        </div>
        <div class="stat-change <?= $dashboard['low_stock_count'] > 0 ? 'down' : 'up' ?>"><?= $dashboard['low_stock_count'] > 0 ? '↓ ' : '' ?><?= number_format($dashboard['low_stock_count']) ?> biến thể sắp hết</div>
    </article>
</section>

<section class="dashboard-chart-grid" aria-label="Biểu đồ kinh doanh">
    <div class="admin-panel">
        <h2 class="admin-panel-title">Biểu đồ doanh thu (7 ngày)</h2>
        <div class="dashboard-chart-wrap"><canvas id="revenueChart"></canvas><p class="dashboard-chart-fallback">Không tải được biểu đồ.</p></div>
    </div>
    <div class="admin-panel">
        <h2 class="admin-panel-title">Trạng thái đơn hàng</h2>
        <div class="dashboard-status-wrap"><canvas id="statusChart"></canvas><p class="dashboard-chart-fallback">Không tải được biểu đồ.</p></div>
    </div>
</section>

<section class="admin-table-wrapper">
    <h2 class="admin-panel-title dashboard-table-title">Đơn hàng mới nhất</h2>
    <table class="admin-table">
        <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($dashboard['latest_orders'])): ?>
            <tr><td colspan="6" style="text-align:center;color:#6b7280">Chưa có đơn hàng.</td></tr>
        <?php else: foreach ($dashboard['latest_orders'] as $order): ?>
            <?php
            $status = $order['status'] ?? 'pending';
            $statusClass = in_array($status, ['delivered', 'completed'], true) ? 'success' : (in_array($status, ['shipping', 'preparing'], true) ? 'warning' : ($status === 'canceled' ? 'error' : 'neutral'));
            ?>
            <tr>
                <td><strong>#<?= adminE($order['order_code'] ?: str_pad($order['id'], 3, '0', STR_PAD_LEFT)) ?></strong></td>
                <td><strong><?= adminE($order['customer_name']) ?></strong></td>
                <td><?= adminE(date('d/m/Y', strtotime($order['created_at']))) ?></td>
                <td><strong><?= adminMoney($order['final_amount']) ?></strong></td>
                <td><span class="admin-badge <?= adminE($statusClass) ?>"><?= adminE($dashboardStatusLabels[$status] ?? 'Khác') ?></span></td>
                <td><a class="admin-btn admin-btn-sm light" href="<?= BASE_URL ?>admin/orders/view?id=<?= (int)$order['id'] ?>">Chi tiết</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        document.querySelectorAll('.dashboard-chart-fallback').forEach(function (item) { item.style.display = 'block'; });
        return;
    }

    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(static fn($date) => date('d/m', strtotime($date)), array_keys($dashboard['revenue_by_day'])), JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?= json_encode(array_values($dashboard['revenue_by_day'])) ?>,
                backgroundColor: '#3e2723',
                borderRadius: 6,
                maxBarThickness: 42
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (context) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw); } } } },
            scales: { y: { beginAtZero: true, grid: { color: '#f1e8dc' } }, x: { grid: { display: false } } }
        }
    });

    const statusData = <?= json_encode($statusChartData) ?>;
    const statusCanvas = document.getElementById('statusChart');
    if (statusData.length === 0) {
        statusCanvas.style.display = 'none';
        statusCanvas.nextElementSibling.textContent = 'Chưa có dữ liệu đơn hàng.';
        statusCanvas.nextElementSibling.style.display = 'block';
        return;
    }
    new Chart(statusCanvas, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($statusChartLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{ data: statusData, backgroundColor: <?= json_encode($statusChartColors) ?>, borderWidth: 2, borderColor: '#fff', hoverOffset: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' } } } }
    });
});
</script>

<?php adminEnd(); ?>
