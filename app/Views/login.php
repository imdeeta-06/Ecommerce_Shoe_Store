<?php
$error = \App\Helpers\SessionHelper::getFlash('error') ?? '';
$success = isset($_GET['registered'])
    ? 'Đăng ký thành công, vui lòng đăng nhập.'
    : (\App\Helpers\SessionHelper::getFlash('success') ?? '');
$oldEmail = $_SESSION['login_old']['email'] ?? '';
unset($_SESSION['login_old']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - PaceUp</title>
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
                <h2 style="font-family: var(--font-heading); font-size: 2rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 0.5rem;">Đăng nhập</h2>
                <p style="color: #666; font-size: 0.9rem;">Vui lòng nhập thông tin của bạn.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="client-flash error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="client-flash success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form action="<?= BASE_URL ?>login" method="POST">
                <div class="client-form-group">
                    <label class="client-label">Email</label>
                    <input type="email" id="email" name="email" class="client-input" required value="<?= htmlspecialchars($oldEmail) ?>">
                </div>
                <div class="client-form-group" style="position: relative;">
                    <label class="client-label">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="client-input" required style="padding-right: 60px;">
                    <button type="button" id="togglePassword" style="position: absolute; right: 0; top: 35px; background: none; border: none; cursor: pointer; color: #111; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Hiện</button>
                </div>
                
                <div class="client-auth-options">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="remember" style="accent-color: #111;"> Ghi nhớ tôi
                    </label>
                    <a href="<?= BASE_URL ?>forgot-password" class="client-text-link">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="client-btn" style="width: 100%;">Đăng nhập</button>

                <p style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: #666;">
                    Chưa có tài khoản? <a href="<?= BASE_URL ?>register" class="client-text-link">Đăng ký ngay</a>
                </p>
            </form>
        </div>
    </div>
    <div class="auth-image"></div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if(togglePassword) {
            togglePassword.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    togglePassword.textContent = 'Ẩn';
                } else {
                    passwordInput.type = 'password';
                    togglePassword.textContent = 'Hiện';
                }
            });
        }
    });
</script>
</body>
</html>
