<?php
include __DIR__ . '/../partials/header.php';
?>

<main class="client-page">
    <h1 class="client-title" style="text-align: center; margin-bottom: 2rem;">Tra cứu đơn hàng</h1>
    <div class="client-main-content" style="max-width: 600px; margin: 0 auto;">
        <p style="text-align: center; margin-bottom: 2rem; color: #666;">Nhập mã đơn hàng và đúng số điện thoại nhận hàng để kiểm tra tình trạng đơn hàng của bạn.</p>
        
        <form action="<?= BASE_URL ?>tracking" method="GET" style="border: 1px solid #eee; padding: 2rem; background: #fff;">
            <div class="client-form-group">
                <label class="client-label">Mã đơn hàng</label>
            <input type="text" name="order_code" class="client-input" placeholder="ORD-20260801-ABC123" value="<?= htmlspecialchars($orderCode ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            
            <div class="client-form-group" style="margin-bottom: 2rem;">
                <label class="client-label">Số điện thoại</label>
            <input type="tel" name="phone" class="client-input" placeholder="0901234567" value="<?= htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            
            <button type="submit" class="client-btn" style="width: 100%;">Kiểm tra ngay</button>
        </form>

        <?php if (!empty($trackingError)): ?>
            <div style="margin-top: 2rem; padding: 1rem 1.25rem; border: 1px solid #fecaca; background: #fff1f2; color: #b91c1c;">
                <?= htmlspecialchars($trackingError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php elseif (!empty($trackingResult)): ?>
            <?php
            $trackingStatusLabels = [
                'pending' => 'Chờ xác nhận',
                'confirmed' => 'Đã xác nhận',
                'preparing' => 'Đang chuẩn bị',
                'shipping' => 'Đang giao',
                'delivered' => 'Giao thành công',
                'completed' => 'Hoàn thành',
                'canceled' => 'Đã hủy'
            ];
            $shippingStatusLabels = [
                'not_shipped' => 'Chưa bàn giao',
                'packing' => 'Đang đóng gói',
                'in_transit' => 'Đang vận chuyển',
                'delivered' => 'Đã giao',
                'returned' => 'Đang hoàn về',
                'canceled' => 'Đã hủy'
            ];
            ?>
            <section style="margin-top: 2rem; border: 1px solid #e5e7eb; background: #fff; padding: 1.5rem;">
                <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;border-bottom:1px solid #eee;padding-bottom:1rem;margin-bottom:1rem;">
                    <strong>Đơn hàng <?= htmlspecialchars($trackingResult['order_code'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span style="font-weight:600;">Trạng thái: <?= htmlspecialchars($trackingStatusLabels[$trackingResult['status']] ?? $trackingResult['status'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;line-height:1.7;">
                    <div><strong>Người nhận:</strong><br><?= htmlspecialchars($trackingResult['shipping_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Phí vận chuyển:</strong><br><?= number_format((float)($trackingResult['shipping_fee'] ?? 0), 0, ',', '.') ?> ₫</div>
                    <div><strong>Đơn vị vận chuyển:</strong><br><?= htmlspecialchars($trackingResult['shipping_carrier'] ?: 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Mã vận đơn:</strong><br><?= htmlspecialchars($trackingResult['tracking_code'] ?: 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Trạng thái giao hàng:</strong><br><?= htmlspecialchars($shippingStatusLabels[$trackingResult['shipping_status']] ?? $trackingResult['shipping_status'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Ngày đặt:</strong><br><?= htmlspecialchars($trackingResult['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
