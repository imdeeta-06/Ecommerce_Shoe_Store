<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Cấu hình hệ thống', 'settings');
?>
<section class="admin-panel">
    <h2 class="admin-panel-title">Thông tin cửa hàng đang áp dụng</h2>
    <p><strong><?= adminE($store['brand_name']) ?></strong></p>
    <p><?= adminE($store['address']) ?></p>
    <p><?= adminE($store['phone']) ?> · <?= adminE($store['email']) ?></p>
    <p>Giờ hỗ trợ: <?= adminE($store['support_hours']) ?></p>
    <p style="margin-top:1rem;">Thông tin được nạp từ cấu hình máy chủ. Liên hệ người quản trị hosting để cập nhật.</p>
</section>
<section class="admin-panel">
    <h2 class="admin-panel-title">Thanh toán & thông báo</h2>
    <p>COD: ghi nhận đã thu tiền khi xác nhận giao thành công.</p>
    <p>Chuyển khoản: quản trị viên kiểm tra tiền vào tài khoản và nhập mã giao dịch.</p>
    <p>PayPal: <strong><?= $paypalReady ? 'Đã có cấu hình' : 'Chưa đủ cấu hình' ?></strong></p>
    <p>Email: <strong><?= $mailReady ? 'Đã có cấu hình' : 'Chưa bật hoặc chưa đủ cấu hình' ?></strong></p>
    <p>Trạng thái cấu hình không thay thế việc kiểm tra kết nối thực tế.</p>
</section>
<section class="admin-panel"><h2 class="admin-panel-title">Ghi nhận doanh thu</h2><p>Đơn đã giao thành công và đã thanh toán mới được tính doanh thu. Tiền hoàn thực tế được trừ khỏi đơn gốc. Chuyển từ Đã giao sang Hoàn thành không ghi nhận thêm doanh thu.</p><a class="admin-btn light" href="<?= BASE_URL ?>admin/tax-report">Xem báo cáo doanh thu</a></section>
<?php adminEnd(); ?>
