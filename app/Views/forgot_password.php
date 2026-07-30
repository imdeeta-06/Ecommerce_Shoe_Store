<?php
$flashMessages = \App\Helpers\SessionHelper::getAllFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - PaceUp</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<main class="auth-page">
    <div class="auth-form-wrapper">
        <div class="auth-form">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2>Quên mật khẩu</h2>
                <p class="subtitle">Nhập email để nhận mã OTP.</p>
            </div>

            <?php foreach ($flashMessages as $type => $message): ?>
                <div style="padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center; font-size: 14px; background: <?= $type === 'error' ? '#fee' : '#eef6ff' ?>; color: <?= $type === 'error' ? '#c00' : '#075985' ?>;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endforeach; ?>

            <form action="<?= BASE_URL ?>forgot-password" method="POST">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <button type="submit" class="btn-login">Gửi OTP</button>

                <p style="text-align: center; margin-top: 1.5rem; font-size: 14px; color: #888;">
                    <a href="<?= BASE_URL ?>login" style="color: #000; font-weight: 600;">Quay lại đăng nhập</a>
                </p>
            </form>
        </div>
    </div>
    <div class="auth-image"></div>
</main>

</body>
</html>
