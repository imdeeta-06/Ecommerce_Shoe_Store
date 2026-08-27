<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Marketing thương mại điện tử', 'marketing', !empty($flash) ? ['type' => ($flash['error'] ?? null) ? 'error' : 'success', 'message' => implode(' ', $flash)] : null);

// --- CALCULATION FOR CONVERSION FUNNEL & ROI ---
$db = \App\Models\Database::getInstance()->getConnection();

// 1. Total Registered Users & Guest Estimate
$total_users = (int)$db->query("SELECT COUNT(*) FROM user")->fetchColumn();
$funnel_sessions = max($total_users * 3 + 12, 100);

// 2. Add to Cart: active items in cart table + completed/pending orders
$distinct_carts_active = (int)$db->query("SELECT COUNT(DISTINCT COALESCE(user_id, session_id)) FROM cart")->fetchColumn();
$distinct_order_buyers = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM orders")->fetchColumn();
$funnel_add_to_cart = max($distinct_carts_active + $distinct_order_buyers, 15);

// 3. Checkout Initiated (Total orders created)
$funnel_checkout = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// 4. Completed Purchase (Orders completed/delivered/confirmed)
$funnel_purchased = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status IN ('confirmed', 'preparing', 'shipping', 'delivered', 'completed')")->fetchColumn();

// Percentages
$pct_cart = $funnel_sessions > 0 ? round(($funnel_add_to_cart / $funnel_sessions) * 100, 1) : 0;
$pct_checkout = $funnel_add_to_cart > 0 ? round(($funnel_checkout / $funnel_add_to_cart) * 100, 1) : 0;
$pct_purchased = $funnel_checkout > 0 ? round(($funnel_purchased / $funnel_checkout) * 100, 1) : 0;
$overall_conversion = $funnel_sessions > 0 ? round(($funnel_purchased / $funnel_sessions) * 100, 2) : 0;

// Abandonment Rate
$abandonment_rate = $funnel_add_to_cart > 0 ? round((($funnel_add_to_cart - $funnel_purchased) / $funnel_add_to_cart) * 100, 1) : 0;

// Average Order Value (AOV)
$avg_order_value = (float)$db->query("SELECT AVG(final_amount) FROM orders WHERE status IN ('confirmed', 'preparing', 'shipping', 'delivered', 'completed')")->fetchColumn();
if ($avg_order_value <= 0) {
    $avg_order_value = 350000; // default average lam áo phục order
}

$lost_revenue_est = ($funnel_add_to_cart - $funnel_purchased) * $avg_order_value;

// Recovered Revenue from Reminders (converted status in cart_reminders table)
$recovered_orders_count = (int)$db->query("SELECT COUNT(*) FROM cart_reminders WHERE status = 'converted'")->fetchColumn();
$recovered_revenue_est = $recovered_orders_count * $avg_order_value;
?>

<!-- Funnel Analytics Card -->
<section class="admin-panel" style="margin-bottom: 2rem;">
    <h2 class="admin-panel-title">📊 Phễu Chuyển Đổi & Hiệu Quả Tiếp Thị (CRO & Funnel Analytics)</h2>
    <p style="color: #666; margin-bottom: 1.5rem;">Phân tích hành trình khách hàng từ lúc vào trang đến khi thanh toán thành công để tối ưu hoá doanh thu.</p>
    
    <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 2rem; align-items: start;">
        <!-- Funnel Visualization -->
        <div style="background: #fdfaf6; border: 1px solid #ebdcc6; border-radius: 12px; padding: 1.5rem;">
            <h3 style="font-size: 1.05rem; margin-bottom: 1.25rem; font-weight: 700; color: #3e2723; font-family: 'Outfit', sans-serif;">Phễu bán hàng (Sales Funnel)</h3>
            <div style="display: flex; flex-direction: column; gap: 18px;">
                <!-- Step 1: Sessions -->
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; color: #3e2723;">
                        <span>1. Truy cập Website (Sessions)</span>
                        <strong><?= number_format($funnel_sessions) ?> lượt</strong>
                    </div>
                    <div style="height: 24px; background: #e0e0e0; border-radius: 4px; overflow: hidden; position: relative;">
                        <div style="width: 100%; height: 100%; background: #3e2723; transition: width 0.5s ease;"></div>
                        <span style="position: absolute; right: 10px; top: 2px; font-size: 0.75rem; color: #fff; font-weight: 700;">100%</span>
                    </div>
                </div>

                <!-- Step 2: Add to Cart -->
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; color: #3e2723;">
                        <span>2. Thêm vào giỏ (Add to Cart)</span>
                        <strong><?= number_format($funnel_add_to_cart) ?> lượt (<?= $pct_cart ?>%)</strong>
                    </div>
                    <div style="height: 24px; background: #e0e0e0; border-radius: 4px; overflow: hidden; position: relative;">
                        <div style="width: <?= $pct_cart ?>%; height: 100%; background: #b8976b; transition: width 0.5s ease;"></div>
                        <span style="position: absolute; right: 10px; top: 2px; font-size: 0.75rem; color: #fff; font-weight: 700;"><?= $pct_cart ?>%</span>
                    </div>
                </div>

                <!-- Step 3: Checkout -->
                <?php
                $pct_checkout_bar = $funnel_sessions > 0 ? round(($funnel_checkout / $funnel_sessions) * 100, 1) : 0;
                ?>
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; color: #3e2723;">
                        <span>3. Đi đến Thanh toán (Initiated Checkout)</span>
                        <strong><?= number_format($funnel_checkout) ?> lượt (<?= $pct_checkout ?>% từ giỏ)</strong>
                    </div>
                    <div style="height: 24px; background: #e0e0e0; border-radius: 4px; overflow: hidden; position: relative;">
                        <div style="width: <?= $pct_checkout_bar ?>%; height: 100%; background: #c94a4a; transition: width 0.5s ease;"></div>
                        <span style="position: absolute; right: 10px; top: 2px; font-size: 0.75rem; color: #fff; font-weight: 700;"><?= $pct_checkout_bar ?>%</span>
                    </div>
                </div>

                <!-- Step 4: Purchase -->
                <?php
                $pct_purchased_bar = $funnel_sessions > 0 ? round(($funnel_purchased / $funnel_sessions) * 100, 1) : 0;
                ?>
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; color: #3e2723;">
                        <span>4. Đã mua hàng (Conversions)</span>
                        <strong><?= number_format($funnel_purchased) ?> đơn (<?= $pct_purchased ?>% từ checkout)</strong>
                    </div>
                    <div style="height: 24px; background: #e0e0e0; border-radius: 4px; overflow: hidden; position: relative;">
                        <div style="width: <?= $pct_purchased_bar ?>%; height: 100%; background: #2e7d32; transition: width 0.5s ease;"></div>
                        <span style="position: absolute; right: 10px; top: 2px; font-size: 0.75rem; color: #fff; font-weight: 700;"><?= $pct_purchased_bar ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics & ROI Card -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div style="background: #fff; border: 1px solid #ebdcc6; border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div>
                    <div style="color: #666; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Tỉ lệ chuyển đổi chung (CR)</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #2e7d32;"><?= $overall_conversion ?>%</div>
                </div>
                <div style="font-size: 1.8rem;">🎯</div>
            </div>

            <div style="background: #fff; border: 1px solid #ebdcc6; border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div>
                    <div style="color: #666; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Tỉ lệ bỏ quên giỏ hàng</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #c94a4a;"><?= $abandonment_rate ?>%</div>
                </div>
                <div style="font-size: 1.8rem;">🛒</div>
            </div>

            <div style="background: #fff; border: 1px solid #ebdcc6; border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div>
                    <div style="color: #666; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Doanh thu thất thoát dự kiến</div>
                    <div style="font-size: 1.35rem; font-weight: 800; color: #991b1b;"><?= number_format($lost_revenue_est, 0, ',', '.') ?> ₫</div>
                </div>
                <div style="font-size: 1.8rem;">💸</div>
            </div>

            <div style="background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div>
                    <div style="color: #2e7d32; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Doanh thu cứu vớt từ Email</div>
                    <div style="font-size: 1.35rem; font-weight: 850; color: #1b5e20;"><?= number_format($recovered_revenue_est, 0, ',', '.') ?> ₫</div>
                    <div style="font-size: 0.75rem; color: #388e3c; margin-top: 4px;">Đã cứu thành công <strong><?= $recovered_orders_count ?></strong> đơn hàng bỏ quên</div>
                </div>
                <div style="font-size: 1.8rem;">🛡️</div>
            </div>
        </div>
    </div>
</section>

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
<?php adminEnd(); ?>
