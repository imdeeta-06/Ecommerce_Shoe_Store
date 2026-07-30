<?php include __DIR__ . '/partials/header.php'; ?>

<style>
.success-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    font-family: var(--font-body);
}

.success-container {
    text-align: center;
    max-width: 500px;
}

.success-icon {
    width: 80px;
    height: 80px;
    background: #4caf50;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
}

.success-title {
    font-family: var(--font-ui);
    font-size: 2rem;
    margin-bottom: 1rem;
}

.success-desc {
    color: #666;
    margin-bottom: 2rem;
    line-height: 1.5;
}

.btn-continue {
    display: inline-block;
    padding: 1rem 2rem;
    background: #111;
    color: #fff;
    text-decoration: none;
    border-radius: 100px;
    font-weight: 500;
    transition: background 0.2s;
}

.btn-continue:hover {
    background: #333;
}
</style>

<div class="success-page">
    <div class="success-container">
        <div class="success-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h1 class="success-title">Đặt hàng thành công!</h1>
        <p class="success-desc">Cảm ơn bạn đã mua sắm tại Paceup. Đơn hàng của bạn đã được ghi nhận và đang chờ xử lý. Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất.</p>
        <a href="<?= BASE_URL ?>shop" class="btn-continue">Tiếp tục mua sắm</a>
    </div>
</div>

<script>
// Prevent showing cart badge since cart was cleared on checkout
document.addEventListener('DOMContentLoaded', () => {
    // If the cart badge is rendered by header logic, it will read an empty localStorage now.
    // Just to be sure, any existing cart toggle logic will show 0.
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>