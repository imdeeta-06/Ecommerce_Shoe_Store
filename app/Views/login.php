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
    <title>Đăng nhập - Liên Hoa Đồ Lam Phật Giáo</title>
    <meta name="description" content="Đăng nhập vào tài khoản Liên Hoa để mua sắm pháp phục và đồ lam Phật giáo cao cấp.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary-dark: #3e2723;
            --primary-color: #b8976b;
            --text-muted: #a09080;
        }

        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            color: #3e2723;
        }

        /* ── Fullscreen BG Image ── */
        .login-page {
            min-height: 100vh;
            background-image: url('<?= BASE_URL ?>assets/images/zen-login-banner.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 2rem;
        }

        /* Dark overlay on the whole image */
        .login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(20, 12, 8, 0.55) 0%,
                rgba(20, 12, 8, 0.35) 50%,
                rgba(20, 12, 8, 0.20) 100%
            );
            z-index: 1;
        }

        /* ── Glassmorphism Login Card ── */
        .login-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            margin-left: 6%;
            background: rgba(252, 250, 246, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 24px;
            padding: 2.75rem 2.5rem;
            box-shadow:
                0 24px 48px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
        }

        /* Brand */
        .login-brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 2rem;
            text-decoration: none;
        }

        .login-brand-icon { font-size: 1.6rem; }

        .login-brand-text {
            display: flex;
            flex-direction: column;
        }

        .login-brand-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--primary-dark);
            line-height: 1.1;
        }

        .login-brand-sub {
            font-size: 0.6rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--primary-color);
            text-transform: uppercase;
        }

        /* Heading */
        .login-heading {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.3rem;
            line-height: 1.2;
        }

        .login-subheading {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }

        /* Flash Messages */
        .flash-msg {
            padding: 0.7rem 0.9rem;
            border-radius: 10px;
            font-size: 0.82rem;
            margin-bottom: 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .flash-msg.error {
            background: rgba(254, 242, 242, 0.9);
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .flash-msg.success {
            background: rgba(240, 253, 244, 0.9);
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        /* Form Fields */
        .form-field { margin-bottom: 1.15rem; }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #5c4a3e;
            margin-bottom: 0.4rem;
            letter-spacing: 0.5px;
        }

        .form-input-wrapper { position: relative; }

        .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1.5px solid rgba(184, 151, 107, 0.3);
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.92rem;
            color: #3e2723;
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.25s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(184, 151, 107, 0.12);
        }

        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--primary-color);
            font-size: 0.78rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
        }
        .toggle-pw:hover { color: var(--primary-dark); }

        /* Options Row */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.82rem;
            margin-bottom: 1.5rem;
        }

        .form-options label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            color: #5c4a3e;
        }

        .form-options input[type="checkbox"] {
            accent-color: var(--primary-color);
            width: 15px;
            height: 15px;
        }

        .form-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .form-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Submit */
        .btn-submit {
            display: block;
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #3e2723 0%, #5a3c35 100%);
            color: #fdfbf7;
            border: none;
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(62, 39, 35, 0.2);
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #5a3c35 0%, #b8976b 100%);
            box-shadow: 0 6px 20px rgba(184, 151, 107, 0.35);
            transform: translateY(-1px);
        }

        /* Register */
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Trust */
        .login-trust {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(184, 151, 107, 0.2);
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .trust-icon {
            width: 28px;
            height: 28px;
            background: rgba(184, 151, 107, 0.12);
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .trust-icon svg {
            width: 14px;
            height: 14px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
        }

        .trust-text {
            font-size: 0.68rem;
            color: var(--text-muted);
            line-height: 1.25;
        }

        /* ── Quote floating on image ── */
        .image-quote {
            position: absolute;
            z-index: 2;
            bottom: 2.5rem;
            right: 3rem;
            max-width: 420px;
            text-align: right;
        }

        .image-quote p {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.35rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.92);
            line-height: 1.45;
            text-shadow: 0 2px 15px rgba(0, 0, 0, 0.4);
            font-style: italic;
            margin-bottom: 0.5rem;
        }

        .image-quote span {
            font-family: 'Outfit', sans-serif;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 1.5px;
            font-weight: 500;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .login-page {
                justify-content: center;
                padding: 1.5rem;
            }
            .login-card {
                margin-left: 0;
                max-width: 100%;
                padding: 2rem 1.5rem;
                background: rgba(252, 250, 246, 0.94);
            }
            .image-quote {
                display: none;
            }
        }
    </style>
</head>
<body>

<main class="login-page">
    <!-- Glass Card (overlaying the image) -->
    <div class="login-card">
        <a href="<?= BASE_URL ?>" class="login-brand">
            <span class="login-brand-icon">🌸</span>
            <div class="login-brand-text">
                <span class="login-brand-name">Liên Hoa</span>
                <span class="login-brand-sub">Đồ Lam Phật Giáo</span>
            </div>
        </a>

        <h1 class="login-heading">Chào mừng trở lại</h1>
        <p class="login-subheading">Đăng nhập để tiếp tục mua sắm pháp phục và vật phẩm tâm linh.</p>

        <?php if ($error): ?>
            <div class="flash-msg error">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="flash-msg success">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>login" method="POST">
            <div class="form-field">
                <label class="form-label" for="email">Địa chỉ Email</label>
                <div class="form-input-wrapper">
                    <input type="email" id="email" name="email" class="form-input" placeholder="nhapemail@example.com" required value="<?= htmlspecialchars($oldEmail) ?>" autocomplete="email">
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="password">Mật khẩu</label>
                <div class="form-input-wrapper">
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required style="padding-right: 60px;" autocomplete="current-password">
                    <button type="button" id="togglePassword" class="toggle-pw">Hiện</button>
                </div>
            </div>

            <div class="form-options">
                <label>
                    <input type="checkbox" name="remember"> Ghi nhớ tôi
                </label>
                <a href="<?= BASE_URL ?>forgot-password" class="form-link">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="btn-submit">Đăng nhập</button>

            <p class="register-link">
                Chưa có tài khoản? <a href="<?= BASE_URL ?>register" class="form-link" style="font-weight:600;">Đăng ký ngay</a>
            </p>
        </form>

        <div class="login-trust">
            <div class="trust-item">
                <div class="trust-icon">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <span class="trust-text">Bảo mật<br>SSL 256-bit</span>
            </div>
            <div class="trust-item">
                <div class="trust-icon">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <span class="trust-text">Thông tin<br>được bảo vệ</span>
            </div>
            <div class="trust-item">
                <div class="trust-icon">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <span class="trust-text">Hỗ trợ<br>7:00 – 21:00</span>
            </div>
        </div>
    </div>

    <!-- Quote on image -->
    <div class="image-quote">
        <p>"Phật tại tâm, trang nghiêm tại hạnh. Mỗi bộ pháp phục là một lời nguyện an lành."</p>
        <span>— LIÊN HOA · ĐỒ LAM PHẬT GIÁO</span>
    </div>
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
