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
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2>Create Account</h2>
                <p class="subtitle">Join PaceUp today.</p>
            </div>

            <?php if ($error): ?>
                <div style="background: #fee; color: #c00; padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center; font-size: 14px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form action="<?= BASE_URL ?>register" method="POST">
                <div class="form-group">
                    <input type="text" name="full_name" placeholder="Họ và tên" required value="<?= htmlspecialchars($old['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <input type="text" name="email" placeholder="Email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Số điện thoại" maxlength="20" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Mật khẩu" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
                </div>

                <button type="submit" class="btn-login">Sign Up</button>

                <p style="text-align: center; margin-top: 1.5rem; font-size: 14px; color: #888;">
                    Already have an account? <a href="<?= BASE_URL ?>login" style="color: #000; font-weight: 600;">Login</a>
                </p>
            </form>
        </div>
    </div>
    <div class="auth-image"></div>
</main>

</body>
</html>
