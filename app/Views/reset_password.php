<?php
$flashMessages = \App\Helpers\SessionHelper::getAllFlash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - PaceUp</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<main class="auth-page">
    <div class="auth-form-wrapper">
        <div class="auth-form">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2>Đặt lại mật khẩu</h2>
                <p class="subtitle">Tạo mật khẩu mới cho tài khoản của bạn.</p>
            </div>

            <?php foreach ($flashMessages as $type => $message): ?>
                <div style="padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center; font-size: 14px; background: <?= $type === 'error' ? '#fee' : '#eef6ff' ?>; color: <?= $type === 'error' ? '#c00' : '#075985' ?>;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endforeach; ?>

            <form action="<?= BASE_URL ?>reset-password" method="POST">
                <div class="form-group">
                    <input type="password" name="new_password" placeholder="Mật khẩu mới" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu mới" required>
                </div>

                <button type="submit" class="btn-login">Đặt lại mật khẩu</button>
            </form>
        </div>
    </div>
    <div class="auth-image"></div>
</main>

</body>
</html>
