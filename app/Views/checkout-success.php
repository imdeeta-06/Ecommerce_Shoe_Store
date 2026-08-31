<?php
$checkoutSuccessFlashMessages = \App\Helpers\SessionHelper::getAllFlash();
$storeConfig = require __DIR__ . '/../../config/store.php';
include __DIR__ . '/partials/header.php';
?>

<style>
.success-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    font-family: var(--font-body);
}

.success-container {
    text-align: center;
    max-width: 600px;
    width: 100%;
}

.success-icon {
    width: 80px;
    height: 80px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    box-shadow: 0 4px 15px rgba(42, 157, 143, 0.2);
}

.success-title {
    font-family: var(--font-ui);
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--primary-dark);
}

.success-desc {
    color: var(--text-muted);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.btn-continue {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: var(--primary-dark);
    color: #fff;
    text-decoration: none;
    border-radius: 100px;
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.9rem;
    letter-spacing: 1px;
    transition: all 0.3s;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

.btn-continue:hover {
    background: var(--primary-color);
    transform: translateY(-2px);
}
</style>

<div class="success-page">
    <div class="success-container">
        <?php foreach ($checkoutSuccessFlashMessages as $type => $message): ?>
            <div class="client-flash <?= $type === 'error' ? 'error' : 'success' ?>" role="status">
                <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>

        <div class="success-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h1 class="success-title">Đặt hàng thành công!</h1>
        <p class="success-desc">Cảm ơn quý Phật tử đã mua sắm tại Liên Hoa. Đơn hàng đã được ghi nhận và đang chờ xử lý. Chúng tôi sẽ gửi thông báo xác nhận và cập nhật trạng thái đơn hàng sớm nhất.</p>
        
        <?php
            $paymentMethod = $order['payment']['payment_method'] ?? '';
            if (isset($order) && $order && in_array($paymentMethod, ['bank', 'bank_transfer'], true)):
        ?>
            <div class="bank-transfer-card" style="background:#fff; border:1px solid var(--border-color); border-radius:16px; padding:2rem; margin:2rem auto; text-align:left; box-shadow:0 4px 20px rgba(74, 59, 50, 0.05); max-width:450px;">
                <h3 style="font-family:var(--font-ui); color:var(--primary-dark); font-size:1.2rem; margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:.8rem; text-align:center; text-transform:uppercase; letter-spacing:1px; font-weight:600;">Thông tin chuyển khoản</h3>
                
                <div style="display:flex; justify-content:center; margin:1.5rem 0;">
                    <?php 
                        $qrAmount = (int)$order['final_amount'];
                        $qrInfo = urlencode($order['order_code']);
                        $bank = $storeConfig['bank'];
                        $qrBank = rawurlencode($bank['code']);
                        $qrAccount = rawurlencode($bank['account_number']);
                        $qrAccountName = rawurlencode($bank['account_name']);
                        $qrUrl = "https://img.vietqr.io/image/{$qrBank}-{$qrAccount}-compact2.png?amount={$qrAmount}&addInfo={$qrInfo}&accountName={$qrAccountName}";
                    ?>
                    <img src="<?= $qrUrl ?>" alt="VietQR" style="max-width:240px; border:1px solid #eee; border-radius:8px; padding:8px; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                </div>
                
                <div style="font-size:0.9rem; line-height:1.8; color:#444;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:.5rem;">
                        <span style="color:#777;">Ngân hàng:</span>
                        <strong style="color:var(--primary-dark);"><?= htmlspecialchars($bank['name']) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:.5rem;">
                        <span style="color:#777;">Số tài khoản:</span>
                        <strong style="color:var(--primary-dark); font-size:1rem; letter-spacing:0.5px;"><?= htmlspecialchars($bank['account_number']) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:.5rem;">
                        <span style="color:#777;">Chủ tài khoản:</span>
                        <strong style="color:var(--primary-dark); text-transform:uppercase;"><?= htmlspecialchars($bank['account_name']) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:.5rem;">
                        <span style="color:#777;">Số tiền:</span>
                        <strong style="color:#c94a4a; font-size:1rem;"><?= number_format($order['final_amount'], 0, ',', '.') ?>đ</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:.8rem; padding-bottom:.8rem; border-bottom:1px dashed #ddd;">
                        <span style="color:#777;">Nội dung CK:</span>
                        <strong style="color:#2a9d8f; font-size:1rem; letter-spacing:0.5px;"><?= htmlspecialchars($order['order_code']) ?></strong>
                    </div>
                    
                    <p style="font-size:0.8rem; color:#888; text-align:center; margin:0; line-height:1.5;">Quý Phật tử vui lòng quét mã QR ở trên hoặc nhập chính xác thông tin chuyển khoản để đơn hàng được xử lý nhanh nhất.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($order) && $order && $paymentMethod === 'paypal'): ?>
            <div style="background:#fff;border:1px solid var(--border-color);border-radius:16px;padding:1.5rem;margin:2rem auto;max-width:450px;text-align:left;box-shadow:0 4px 20px rgba(74,59,50,.05);">
                <h3 style="margin:0 0 1rem;text-align:center;color:var(--primary-dark);text-transform:uppercase;letter-spacing:1px;font-size:1.05rem;">PayPal đã xác nhận</h3>
                <p style="margin:.4rem 0;display:flex;justify-content:space-between;gap:1rem;"><span>Trạng thái:</span><strong><?= ($order['payment']['payment_state'] ?? '') === 'paid' ? 'Đã thanh toán' : 'Đang đối soát' ?></strong></p>
                <?php if (!empty($order['payment']['provider_amount'])): ?><p style="margin:.4rem 0;display:flex;justify-content:space-between;gap:1rem;"><span>Số tiền PayPal:</span><strong><?= number_format((float)$order['payment']['provider_amount'], 2, '.', ',') ?> <?= htmlspecialchars($order['payment']['provider_currency'] ?? 'USD', ENT_QUOTES, 'UTF-8') ?></strong></p><?php endif; ?>
                <?php if (!empty($order['payment']['provider_capture_id'])): ?><p style="margin:.4rem 0;display:flex;justify-content:space-between;gap:1rem;"><span>Mã giao dịch:</span><strong style="word-break:break-all;text-align:right;"><?= htmlspecialchars($order['payment']['provider_capture_id'], ENT_QUOTES, 'UTF-8') ?></strong></p><?php endif; ?>
            </div>
        <?php endif; ?>
        
        <p style="margin:0 0 2.5rem; color:var(--text-muted); font-size:.95rem;">Bạn có thể xem đơn hàng trong tài khoản cá nhân hoặc <a href="<?= BASE_URL ?>tracking" style="color:var(--primary-color); font-weight:500; text-decoration:underline;">tra cứu đơn hàng</a>.</p>
        <?php if (!empty($order['id'])): ?><a href="<?= BASE_URL ?>order/receipt?order_id=<?= (int)$order['id'] ?>" class="btn-continue" target="_blank" rel="noopener" style="margin-right:.5rem;">In phiếu đơn hàng</a><?php endif; ?>
        <a href="<?= BASE_URL ?>shop" class="btn-continue">Tiếp tục mua sắm</a>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
