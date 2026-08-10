<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="client-page">
    <section class="client-main-content" style="max-width:620px;margin:0 auto;text-align:center;padding:4rem 1rem;">
        <h1 class="client-title">Hủy email nhắc giỏ hàng</h1>
        <p style="color:#666;line-height:1.7;">
            <?= $success
                ? 'Bạn đã hủy nhận email nhắc giỏ hàng thành công.'
                : 'Liên kết không hợp lệ hoặc yêu cầu này đã được xử lý trước đó.' ?>
        </p>
        <a class="client-btn" href="<?= BASE_URL ?>shop" style="margin-top:1.5rem;">Tiếp tục mua sắm</a>
    </section>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
