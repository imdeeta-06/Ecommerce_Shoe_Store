<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Marketing thương mại điện tử', 'marketing', !empty($flash) ? ['type' => ($flash['error'] ?? null) ? 'error' : 'success', 'message' => implode(' ', $flash)] : null);
?>
<div class="admin-grid">
    <section class="admin-panel">
        <h2 class="admin-panel-title">Thêm banner</h2>
        <form method="post" action="<?= BASE_URL ?>admin/marketing/banner/store">
            <div class="admin-field"><label>Đường dẫn ảnh *</label><input name="image_url" required placeholder="assets/images/hero.avif hoặc public/uploads/..." /></div>
            <div class="admin-field"><label>Link khi khách bấm banner</label><input name="link_url" placeholder="shop?gender=men hoặc product?id=..." /></div>
            <button class="admin-btn primary" type="submit">Thêm banner</button>
        </form>
</section>
<section class="admin-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div><h2 class="admin-panel-title">Thông báo đơn hàng</h2><p style="color:#666;margin-bottom:1rem;">Khi khách đặt hàng hoặc đơn chuyển trạng thái, hệ thống tạo thông báo giao dịch. Cấu hình SMTP rồi bấm gửi hoặc chạy cron.</p></div>
        <form method="post" action="<?= BASE_URL ?>admin/marketing/order-notifications/send"><button class="admin-btn primary" type="submit">Gửi thông báo đang chờ</button></form>
    </div>
    <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Đơn hàng</th><th>Email</th><th>Loại</th><th>Trạng thái</th><th>Lần thử</th><th>Lỗi gần nhất</th></tr></thead><tbody>
        <?php foreach ($orderNotifications as $notification): ?>
            <?php $notificationLabels = ['order_created' => 'Tiếp nhận đơn', 'status_confirmed' => 'Đã xác nhận', 'status_preparing' => 'Đang chuẩn bị', 'status_shipping' => 'Đang giao', 'status_delivered' => 'Giao thành công', 'status_completed' => 'Hoàn thành', 'status_canceled' => 'Đã hủy']; ?>
            <tr><td><strong><?= adminE($notification['order_code'] ?? ('#' . $notification['order_id'])) ?></strong></td><td><?= adminE($notification['recipient_email']) ?></td><td><?= adminE($notificationLabels[$notification['notification_type']] ?? $notification['notification_type']) ?></td><td><span class="admin-badge <?= $notification['status'] === 'sent' ? 'success' : ($notification['status'] === 'failed' ? 'error' : 'warning') ?>"><?= adminE(['pending' => 'Chờ gửi', 'sent' => 'Đã gửi', 'failed' => 'Gửi lỗi'][$notification['status']] ?? $notification['status']) ?></span></td><td><?= (int)$notification['attempt_count'] ?>/3</td><td style="max-width:240px;color:#b91c1c;"><?= adminE($notification['last_error'] ?? '') ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($orderNotifications)): ?><tr><td colspan="6" style="text-align:center;padding:2rem;color:#666;">Chưa có thông báo đơn hàng trong hàng đợi.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<section class="admin-panel">
        <h2 class="admin-panel-title">Thiết lập marketing tự động</h2>
        <p style="color:#555;line-height:1.7;">Sản phẩm nổi bật do admin bật trong form sản phẩm. Sản phẩm bán chạy tính theo số lượng đã giao thành công/hoàn thành; đơn đang giữ hàng chưa được tính là bán.</p>
        <p style="margin-top:1rem;color:#666;">SEO cơ bản đã dùng tiêu đề, mô tả và canonical động cho trang chủ/sản phẩm.</p>
    </section>
</div>
<section class="admin-panel"><h2 class="admin-panel-title">Banner hiện có</h2><div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Ảnh</th><th>Link</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php foreach ($banners as $banner): ?><tr><td><img class="admin-thumb-lg" src="<?= adminImageUrl($banner['image_url']) ?>" alt="Banner"></td><td><?= adminE($banner['link_url'] ?? '') ?></td><td><span class="admin-badge <?= (int)$banner['status'] === 1 ? 'success' : 'neutral' ?>"><?= (int)$banner['status'] === 1 ? 'Đang hiển thị' : 'Đã ẩn' ?></span></td><td><div class="admin-actions"><form method="post" action="<?= BASE_URL ?>admin/marketing/banner/status"><input type="hidden" name="id" value="<?= (int)$banner['id'] ?>"><button class="admin-btn-sm admin-btn light" type="submit">Ẩn/hiện</button></form><form method="post" action="<?= BASE_URL ?>admin/marketing/banner/delete" onsubmit="return confirm('Xóa banner này?')"><input type="hidden" name="id" value="<?= (int)$banner['id'] ?>"><button class="admin-btn-sm admin-btn danger" type="submit">Xóa</button></form></div></td></tr><?php endforeach; ?><?php if (empty($banners)): ?><tr><td colspan="4" style="text-align:center;padding:2rem;color:#666;">Chưa có banner động.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="admin-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div>
            <h2 class="admin-panel-title">Giỏ hàng bỏ quên</h2>
            <p style="color:#666;margin-bottom:1rem;">Hệ thống đưa giỏ có sản phẩm và không hoạt động quá 24 giờ vào hàng đợi. Cấu hình SMTP rồi chạy nút này hoặc cron để gửi email thật.</p>
        </div>
        <form method="post" action="<?= BASE_URL ?>admin/marketing/cart-reminders/send">
            <button class="admin-btn primary" type="submit">Gửi email đang chờ</button>
        </form>
    </div>
    <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Khách hàng</th><th>Email</th><th>Lần cuối thấy giỏ</th><th>Lần thử</th><th>Trạng thái</th><th>Lỗi gần nhất</th></tr></thead><tbody>
        <?php foreach ($reminders as $reminder): ?>
            <?php $reminderLabels = ['pending' => 'Chờ gửi', 'failed' => 'Gửi lỗi', 'sent' => 'Đã gửi', 'converted' => 'Đã chuyển đổi', 'unsubscribed' => 'Đã hủy nhận']; ?>
            <tr><td><?= adminE($reminder['full_name']) ?></td><td><?= adminE($reminder['email']) ?></td><td><?= adminE($reminder['last_seen_at']) ?></td><td><?= (int)($reminder['attempt_count'] ?? 0) ?>/3</td><td><span class="admin-badge <?= ($reminder['status'] ?? '') === 'failed' ? 'danger' : 'warning' ?>"><?= adminE($reminderLabels[$reminder['status'] ?? ''] ?? ($reminder['status'] ?? '')) ?></span></td><td style="max-width:240px;color:#b91c1c;"><?= adminE($reminder['last_error'] ?? '') ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($reminders)): ?><tr><td colspan="6" style="text-align:center;padding:2rem;color:#666;">Hiện không có email đủ điều kiện gửi.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php adminEnd(); ?>
