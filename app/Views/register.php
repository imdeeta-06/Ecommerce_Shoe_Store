<?php
$error = $_SESSION['flash']['error'] ?? '';
unset($_SESSION['flash']['error']);
$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_old']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - PaceUp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<main class="auth-page">
    <div class="auth-form-wrapper">
        <div class="auth-form">
            <div style="text-align: center; margin-bottom: 3rem;">
                <h2 style="font-family: var(--font-heading); font-size: 2rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 0.5rem;">Đăng ký</h2>
                <p style="color: #666; font-size: 0.9rem;">Tạo tài khoản mới cùng PaceUp.</p>
            </div>

            <?php if ($error): ?>
                <div class="client-flash error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form action="<?= BASE_URL ?>register" method="POST">
                <div class="client-form-group">
                    <label class="client-label">Họ và tên</label>
                    <input type="text" name="full_name" class="client-input" required value="<?= htmlspecialchars($old['full_name'] ?? '') ?>">
                </div>
                <div class="client-form-group">
                    <label class="client-label">Email</label>
                    <input type="text" name="email" class="client-input" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>
                <div class="client-form-group">
                    <label class="client-label">Số điện thoại</label>
                    <input type="tel" name="phone" class="client-input" maxlength="20" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
                <div class="client-form-group">
                    <label class="client-label">Mật khẩu</label>
                    <input type="password" name="password" class="client-input" required>
                </div>
                <div class="client-form-group" style="margin-bottom: 2.5rem;">
                    <label class="client-label">Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_password" class="client-input" required>
                </div>

                <button type="submit" class="client-btn" style="width: 100%;">Đăng ký</button>

                <p style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: #666;">
                    Đã có tài khoản? <a href="<?= BASE_URL ?>login" class="client-text-link">Đăng nhập</a>
                </p>
            </form>
        </div>
    </div>
    <div class="auth-image"></div>
</main>

</body>
</html>
