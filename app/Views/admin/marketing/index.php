<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Marketing thương mại điện tử', 'marketing', !empty($flash) ? ['type' => ($flash['error'] ?? null) ? 'error' : 'success', 'message' => implode(' ', $flash)] : null);
?>
<div class="admin-grid">
    <section class="admin-panel">
        <h2 class="admin-panel-title">Thêm banner</h2>
        <form method="post" action="<?= BASE_URL ?>admin/marketing/banner/store">
            <div class="admin-field"><label>Đường dẫn ảnh *</label><input name="image_url" required placeholder="assets/images/hero.avif hoặc public/uploads/..." /></div>
            <div class="admin-field"><label>Link khi khách bấm banner</label><input name="link_url" placeholder="shop?category=Đồ+lam+đi+chùa hoặc product?id=..." /></div>
            <button class="admin-btn primary" type="submit">Thêm banner</button>
        </form>
</section>
<section class="admin-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <div><h2 class="admin-panel-title">Thông báo đơn hàng</h2><p style="color:#666;margin-bottom:1rem;">Khi khách đặt hàng hoặc đơn chuyển trạng thái, hệ thống tạo thông báo giao dịch. Cấu hình SMTP rồi bấm gửi hoặc chạy cron.</p></div>
        <form method="post" action="<?= BASE_URL ?>admin/marketing/order-notifications/send"><button class="admin-btn primary" type="submit">Gửi thông báo đang chờ</button></form>
    </div>
    <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Đơn hàng</th><th>Email</th><th>Loại</th><th>Trạng thái</th><th>Lần thử</th><th>Lỗi gần nhất</th></tr></thead><tbody>
        <?php foreach ($orderNotifications as $notification): ?>
            <?php $notificationLabels = ['order_created' => 'Tiếp nhận đơn', 'status_confirmed' => 'Đã xác nhận', 'status_preparing' => 'Đang chuẩn bị', 'status_shipping' => 'Đang giao', 'status_delivered' => 'Giao thành công', 'status_completed' => 'Hoàn thành', 'status_canceled' => 'Đã hủy']; ?>
            <tr><td style="white-space:nowrap;"><strong><?= adminE($notification['order_code'] ?? ('#' . $notification['order_id'])) ?></strong></td><td><?= adminE($notification['recipient_email']) ?></td><td><?= adminE($notificationLabels[$notification['notification_type']] ?? $notification['notification_type']) ?></td><td><span class="admin-badge <?= $notification['status'] === 'sent' ? 'success' : ($notification['status'] === 'failed' ? 'error' : 'warning') ?>"><?= adminE(['pending' => 'Chờ gửi', 'sent' => 'Đã gửi', 'failed' => 'Gửi lỗi'][$notification['status']] ?? $notification['status']) ?></span></td><td><?= (int)$notification['attempt_count'] ?>/3</td><td style="max-width:240px;color:#b91c1c;"><?= adminE($notification['last_error'] ?? '') ?></td></tr>
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
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
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
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap"><h2 class="admin-title" style="margin-top: 2rem; margin-bottom: 1.5rem; font-size: 1.25rem;">Phễu đo lường <?= (int)$analyticsDays ?> ngày (có đồng ý)</h2><form method="get"><label>Khoảng báo cáo <select name="days" onchange="this.form.submit()"><option value="7" <?= $analyticsDays===7?'selected':'' ?>>7 ngày</option><option value="30" <?= $analyticsDays===30?'selected':'' ?>>30 ngày</option><option value="90" <?= $analyticsDays===90?'selected':'' ?>>90 ngày</option><option value="365" <?= $analyticsDays===365?'selected':'' ?>>365 ngày</option></select></label></form></div>
<?php $ae = $analytics['events'] ?? []; $sessions = (int)($analytics['total_sessions'] ?? 0); $purchases = (int)($ae['purchase']['sessions'] ?? 0); ?>
<div class="admin-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); margin-bottom: 2rem;">
    <?php foreach (['page_view' => 'Lượt xem', 'product_view' => 'Xem sản phẩm', 'add_to_cart' => 'Thêm giỏ', 'checkout_started' => 'Bắt đầu checkout', 'purchase' => 'Mua thành công'] as $eventKey => $eventLabel): ?>
        <div class="stat-card" style="padding: 1.5rem;">
            <div class="stat-title" style="margin-bottom:0.5rem;"><?= adminE($eventLabel) ?></div>
            <div class="stat-value" style="margin-bottom:0;"><?= (int)($ae[$eventKey]['total'] ?? 0) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="admin-table-wrapper" style="margin-bottom: 2.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding: 1.5rem 1.5rem 0 1.5rem;">
        <h3 class="admin-panel-title" style="border: none; margin: 0; padding: 0;">Hiệu suất chiến dịch</h3>
        <span style="font-size:0.875rem; color:#6b7280; font-weight: 500;">Tỷ lệ chuyển đổi phiên: <strong style="color:#111;"><?= $sessions > 0 ? number_format($purchases * 100 / $sessions, 2, ',', '.') : '0,00' ?>%</strong></span>
    </div>
    <table class="admin-table" style="margin-top: 1rem;">
        <thead><tr><th>Chiến dịch</th><th>Phiên</th><th>Checkout</th><th>Mua</th><th>CVR</th></tr></thead>
        <tbody>
            <?php foreach (($analytics['campaigns'] ?? []) as $campaign): ?><tr><td><?= adminE($campaign['campaign']) ?></td><td><?= (int)$campaign['sessions'] ?></td><td><?= (int)$campaign['checkouts'] ?></td><td><?= (int)$campaign['purchases'] ?></td><td><?= (int)$campaign['sessions'] > 0 ? number_format((int)$campaign['purchases'] * 100 / (int)$campaign['sessions'], 2, ',', '.') : '0,00' ?>%</td></tr><?php endforeach; ?>
            <?php if (empty($analytics['campaigns'])): ?><tr><td colspan="5" style="text-align:center;color:#666;padding:2rem;">Chưa có dữ liệu đo lường đã đồng ý.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="admin-grid" style="margin-bottom:2rem;"><section class="admin-panel"><h3 class="admin-panel-title">Tạo chiến dịch newsletter</h3><form method="post" action="<?= BASE_URL ?>admin/marketing/campaign/store"><div class="admin-field"><label>Tên nội bộ *</label><input name="name" required></div><div class="admin-field"><label>Tiêu đề email *</label><input name="subject" required></div><div class="admin-field"><label>Nội dung *</label><textarea name="body" rows="5" required></textarea></div><div class="admin-field"><label>Liên kết đích</label><input name="target_url" placeholder="shop hoặc https://..."></div><div class="admin-field"><label>Phân khúc</label><select name="segment"><option value="all">Tất cả đã xác nhận</option><option value="customers">Đã có tài khoản</option><option value="prospects">Chưa có tài khoản</option></select></div><button class="admin-btn primary">Lưu bản nháp</button></form></section><section class="admin-panel"><h3 class="admin-panel-title">Chiến dịch & đo lường</h3><div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Tên</th><th>Phân khúc</th><th>Trạng thái</th><th>Gửi/Mở/Click</th><th>Thao tác</th></tr></thead><tbody><?php foreach(($newsletterCampaigns??[]) as $c):?><tr><td><?= adminE($c['name']) ?><br><small><?= adminE($c['subject']) ?></small></td><td><?= adminE($c['segment']) ?></td><td><?= adminE($c['status']) ?></td><td><?= (int)$c['sent_count'] ?>/<?= (int)$c['open_count'] ?>/<?= (int)$c['click_count'] ?></td><td><div class="admin-actions"><?php if(in_array($c['status'],['draft','failed'],true)):?><form method="post" action="<?= BASE_URL ?>admin/marketing/campaign/queue"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="admin-btn-sm admin-btn light">Xếp hàng</button></form><?php endif;?><?php if(in_array($c['status'],['queued','sending','failed'],true)):?><form method="post" action="<?= BASE_URL ?>admin/marketing/campaign/send"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="admin-btn-sm admin-btn primary">Gửi</button></form><?php endif;?></div></td></tr><?php endforeach;?><?php if(empty($newsletterCampaigns)):?><tr><td colspan="5">Chưa có chiến dịch.</td></tr><?php endif;?></tbody></table></div></section></div>
<div class="admin-table-wrapper" style="margin-bottom: 2rem;">
    <h3 class="admin-panel-title" style="padding: 1.5rem 1.5rem 0 1.5rem; border: none; margin: 0;">Đăng ký newsletter có bằng chứng đồng ý</h3>
    <table class="admin-table" style="margin-top: 1rem;">
        <thead><tr><th>Email</th><th>Trạng thái</th><th>Phiên bản đồng ý</th><th>Nguồn</th><th>Yêu cầu</th><th>Xác nhận</th></tr></thead>
        <tbody>
            <?php foreach (($newsletterSubscriptions ?? []) as $subscription): ?><tr><td><?= adminE($subscription['email']) ?></td><td><?= adminE($subscription['status']) ?></td><td><?= adminE($subscription['consent_version']) ?></td><td><?= adminE($subscription['source']) ?></td><td><?= adminE($subscription['consented_at']) ?></td><td><?= adminE($subscription['confirmed_at']??'Chưa xác nhận') ?></td></tr><?php endforeach; ?>
            <?php if (empty($newsletterSubscriptions)): ?><tr><td colspan="6" style="text-align:center;color:#666;padding:2rem;">Chưa có email đăng ký thật.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php adminEnd(); ?>
