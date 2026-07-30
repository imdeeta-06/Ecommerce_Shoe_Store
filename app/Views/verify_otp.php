<?php
$flashMessages = \App\Helpers\SessionHelper::getAllFlash();
$email = $_SESSION['otp_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực OTP - PaceUp</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<main class="auth-page">
    <div class="auth-form-wrapper">
        <div class="auth-form">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2>Xác thực OTP</h2>
                <p class="subtitle">Mã OTP đang xác thực cho <?= htmlspecialchars($email) ?>.</p>
            </div>

            <?php foreach ($flashMessages as $type => $message): ?>
                <div style="padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center; font-size: 14px; background: <?= $type === 'error' ? '#fee' : '#eef6ff' ?>; color: <?= $type === 'error' ? '#c00' : '#075985' ?>;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endforeach; ?>

            <form action="<?= BASE_URL ?>verify-otp" method="POST">
                <div class="form-group">
                    <input type="text" name="otp" placeholder="Nhập 6 số OTP" maxlength="6" required>
                </div>

                <button type="submit" class="btn-login">Xác nhận</button>
            </form>
        </div>
    </div>
    <div class="auth-image"></div>
</main>

</body>
</html>
