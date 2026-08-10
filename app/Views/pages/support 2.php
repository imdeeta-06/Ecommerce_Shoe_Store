<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="client-page">
    <h1 class="client-title" style="text-align:center;margin-bottom:1rem;">Hỗ trợ khách hàng</h1>
    <p style="max-width:720px;margin:0 auto 2rem;text-align:center;color:#666;line-height:1.7;">Gửi yêu cầu về đơn hàng, giao hàng, đổi trả, bảo hành hoặc sản phẩm. Hệ thống cấp mã yêu cầu và gửi email xác nhận tự động khi SMTP được bật.</p>
    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $type => $message): ?>
            <div style="max-width:720px;margin:0 auto 1rem;padding:1rem;border:1px solid <?= $type === 'error' ? '#fecaca' : '#bbf7d0' ?>;background:<?= $type === 'error' ? '#fef2f2' : '#f0fdf4' ?>;color:<?= $type === 'error' ? '#991b1b' : '#166534' ?>;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>support/store" style="max-width:720px;margin:0 auto;border:1px solid #ddd;padding:2rem;background:#fff;">
        <div class="client-form-group"><label class="client-label" for="supportName">Họ và tên *</label><input class="client-input" id="supportName" name="name" required maxlength="150" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="client-form-group"><label class="client-label" for="supportEmail">Email nhận phản hồi *</label><input class="client-input" id="supportEmail" type="email" name="email" required maxlength="255"></div>
        <div class="client-form-group"><label class="client-label" for="supportPhone">Số điện thoại</label><input class="client-input" id="supportPhone" name="phone" maxlength="30"></div>
        <div class="client-form-group"><label class="client-label" for="supportSubject">Chủ đề *</label><select class="client-input" id="supportSubject" name="subject" required><option value="">Chọn nội dung cần hỗ trợ</option><option>Tra cứu và giao hàng</option><option>Đổi trả hoặc hoàn tiền</option><option>Bảo hành</option><option>Sản phẩm và size/màu</option><option>Khác</option></select></div>
        <div class="client-form-group"><label class="client-label" for="supportMessage">Nội dung *</label><textarea class="client-input" id="supportMessage" name="message" rows="6" minlength="10" maxlength="5000" required placeholder="Mô tả vấn đề hoặc mã đơn hàng nếu có..."></textarea></div>
        <button class="client-btn" type="submit" style="width:100%;">Gửi yêu cầu hỗ trợ</button>
    </form>
    <p style="max-width:720px;margin:1rem auto;text-align:center;color:#777;">Bạn có thể xem trước <a href="<?= BASE_URL ?>faqs">FAQs</a> hoặc dùng <a href="<?= BASE_URL ?>tracking">Tra cứu đơn hàng</a>.</p>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
