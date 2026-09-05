<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="client-page">
    <h1 class="client-title" style="text-align:center;margin-bottom:1rem;">Gửi phản hồi cho chúng tôi</h1>
    <p style="max-width:720px;margin:0 auto 2rem;text-align:center;color:#666;line-height:1.7;">Chúng tôi luôn lắng nghe ý kiến của bạn để cải thiện chất lượng sản phẩm và dịch vụ tốt hơn mỗi ngày.</p>
    
    <?php if (!empty($flash)): ?><?php foreach ($flash as $type => $message): ?><div style="max-width:720px;margin:0 auto 1rem;padding:1rem;background:<?= $type === 'error' ? '#fef2f2' : '#f0fdf4' ?>;color:<?= $type === 'error' ? '#991b1b' : '#166534' ?>;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?><?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>support/store" style="max-width:720px;margin:0 auto;border:1px solid #ddd;padding:2rem;background:#fff;">
        <input type="hidden" name="subject" value="Phản hồi trải nghiệm website">
        <input type="hidden" name="return_to" value="feedback">
        <div class="client-form-group">
            <label class="client-label" for="feedbackName">Họ và tên *</label>
            <input class="client-input" id="feedbackName" name="name" required maxlength="150" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="client-form-group">
            <label class="client-label" for="feedbackEmail">Email liên hệ *</label>
            <input class="client-input" id="feedbackEmail" type="email" name="email" required maxlength="255">
        </div>
        <div class="client-form-group">
            <label class="client-label" for="feedbackRating">Mức độ hài lòng</label>
            <select class="client-input" id="feedbackRating" name="rating">
                <option value="5">Rất hài lòng</option>
                <option value="4">Hài lòng</option>
                <option value="3">Bình thường</option>
                <option value="2">Không hài lòng</option>
                <option value="1">Rất thất vọng</option>
            </select>
        </div>
        <div class="client-form-group">
            <label class="client-label" for="feedbackMessage">Ý kiến đóng góp của bạn *</label>
            <textarea class="client-input" id="feedbackMessage" name="message" rows="6" minlength="10" maxlength="5000" required placeholder="Vui lòng chia sẻ trải nghiệm hoặc đề xuất của bạn..."></textarea>
        </div>
        <button class="client-btn" type="submit" style="width:100%;">Gửi phản hồi</button>
    </form>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
