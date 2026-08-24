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
            --primary-gold: #c59b6d;
            --primary-gold-light: #e4c59e;
            --text-light: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.75);
        }

        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            color: #ffffff;
        }

        /* ── Fullscreen Background ── */
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
            padding: 2.5rem 4rem;
        }

        /* Subtle dark & warm vignette overlay */
        .login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                90deg,
                rgba(18, 12, 8, 0.65) 0%,
                rgba(18, 12, 8, 0.35) 45%,
                rgba(18, 12, 8, 0.15) 100%
            );
            z-index: 1;
        }

        /* ── Seamless Translucent Glassmorphism Card ── */
        .login-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            margin-left: 4%;
            background: rgba(28, 18, 14, 0.45);
            backdrop-filter: blur(18px) saturate(130%);
            -webkit-backdrop-filter: blur(18px) saturate(130%);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 26px;
            padding: 2.75rem 2.5rem;
            box-shadow:
                0 24px 50px rgba(0, 0, 0, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.18);
        }

        /* Brand Header */
        .login-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            text-decoration: none;
        }

        .login-brand-icon { 
            font-size: 1.7rem; 
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));
        }

        .login-brand-text {
            display: flex;
            flex-direction: column;
        }

        .login-brand-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.1;
            letter-spacing: 0.5px;
        }

        .login-brand-sub {
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--primary-gold-light);
            text-transform: uppercase;
        }

        /* Heading */
        .login-heading {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.1rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.35rem;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .login-subheading {
            font-size: 0.92rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }

        /* Flash Messages */
        .flash-msg {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.84rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(10px);
        }
        .flash-msg.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fecaca;
        }
        .flash-msg.success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #bbf7d0;
        }

        /* Form Fields */
        .form-field { margin-bottom: 1.25rem; }

        .form-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #f0e1d1;
            margin-bottom: 0.45rem;
            letter-spacing: 0.5px;
        }

        .form-input-wrapper { position: relative; }

        .form-input {
            width: 100%;
            padding: 0.85rem 1.1rem;
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            transition: all 0.25s ease;
            outline: none;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.45);
        }

        .form-input:focus {
            border-color: var(--primary-gold-light);
            background: rgba(255, 255, 255, 0.16);
            box-shadow: 0 0 0 3px rgba(197, 155, 109, 0.25);
        }

        /* Chrome Autofill fix for transparent/dark glass */
        .form-input:-webkit-autofill,
        .form-input:-webkit-autofill:hover,
        .form-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #ffffff !important;
            -webkit-box-shadow: 0 0 0px 1000px rgba(45, 30, 24, 0.85) inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--primary-gold-light);
            font-size: 0.8rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            letter-spacing: 0.5px;
        }
        .toggle-pw:hover { color: #ffffff; }

        /* Options Row */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            margin-bottom: 1.6rem;
            color: rgba(255, 255, 255, 0.85);
        }

        .form-options label {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
        }

        .form-options input[type="checkbox"] {
            accent-color: var(--primary-gold);
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .form-link {
            color: var(--primary-gold-light);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .form-link:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        /* Submit Button */
        .btn-submit {
            display: block;
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, #c59b6d 0%, #9e7a4d 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.92rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(197, 155, 109, 0.35);
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #d8ab7b 0%, #b88e5d 100%);
            box-shadow: 0 8px 25px rgba(197, 155, 109, 0.5);
            transform: translateY(-2px);
        }

        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 1.6rem;
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        /* Trust Badges */
        .login-trust {
            display: flex;
            gap: 1.25rem;
            margin-top: 2rem;
            padding-top: 1.35rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .trust-icon {
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .trust-icon svg {
            width: 15px;
            height: 15px;
            stroke: var(--primary-gold-light);
            fill: none;
            stroke-width: 2;
        }

        .trust-text {
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.75);
            line-height: 1.3;
        }

        /* ── Quote Floating on Right side of Image ── */
        .image-quote {
            position: absolute;
            z-index: 2;
            bottom: 3rem;
            right: 4rem;
            max-width: 450px;
            text-align: right;
            pointer-events: none;
        }

        .image-quote p {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.45rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.45;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.6);
            font-style: italic;
            margin-bottom: 0.5rem;
        }

        .image-quote span {
            font-family: 'Outfit', sans-serif;
            font-size: 0.78rem;
            color: var(--primary-gold-light);
            letter-spacing: 2px;
            font-weight: 600;
            text-shadow: 0 1px 10px rgba(0, 0, 0, 0.5);
        }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .login-page {
                justify-content: center;
                align-items: center;
                padding: 2rem 1.5rem;
                min-height: 100vh;
                min-height: 100dvh;
            }
            .login-card {
                margin-left: 0;
                max-width: 460px;
                padding: 2.25rem 1.75rem;
                background: rgba(28, 18, 14, 0.75);
            }
            .image-quote {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .login-page {
                padding: 1.25rem 1rem;
            }
            .login-card {
                padding: 2rem 1.25rem;
                border-radius: 20px;
                max-width: 100%;
            }
            .login-heading {
                font-size: 1.85rem;
            }
            .login-subheading {
                font-size: 0.85rem;
                margin-bottom: 1.25rem;
            }
            .login-trust {
                gap: 0.75rem;
            }
            .trust-item {
                flex: 1;
            }
            .trust-text {
                font-size: 0.68rem;
            }
        }

        @media (max-width: 420px) {
            .login-page {
                padding: 1rem 0.75rem;
            }
            .login-card {
                padding: 1.5rem 1rem;
                border-radius: 16px;
            }
            .login-heading {
                font-size: 1.65rem;
            }
            .login-brand-name {
                font-size: 1.25rem;
            }
            .form-options {
                font-size: 0.78rem;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .login-trust {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 0.4rem;
                padding-top: 1rem;
                margin-top: 1.5rem;
            }
            .trust-item {
                flex-direction: column;
                text-align: center;
                gap: 0.25rem;
            }
            .trust-text {
                font-size: 0.62rem;
                line-height: 1.2;
            }
            .trust-icon {
                margin: 0 auto;
                width: 26px;
                height: 26px;
            }
            .trust-icon svg {
                width: 13px;
                height: 13px;
            }
        }

        .g-signin-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }
    </style>
</head>
<body>

<main class="login-page">
    <!-- Seamless Glass Card -->
    <div class="login-card">
        <a href="<?= BASE_URL ?>" class="login-brand">
            <span class="login-brand-icon">🌸</span>
            <div class="login-brand-text">
                <span class="login-brand-name">Liên Hoa</span>
                <span class="login-brand-sub">ĐỒ LAM PHẬT GIÁO</span>
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
        
        <?php $info = \App\Helpers\SessionHelper::getFlash('info'); ?>
        <?php if ($info): ?>
            <div class="alert-box" style="background: #e3f2fd; border: 1px solid #90caf9; color: #0d47a1;">
                <?= htmlspecialchars($info) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
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

            <div class="divider">hoặc</div>

            <script src="https://accounts.google.com/gsi/client" async defer></script>
            <div id="g_id_onload"
                 data-client_id="<?= getenv('GOOGLE_CLIENT_ID') ?: (defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '') ?>"
                 data-login_uri="<?= rtrim(BASE_URL, '/') ?>/auth/google"
                 data-auto_prompt="false">
            </div>
            
            <div class="g-signin-wrapper">
                <div class="g_id_signin"
                     data-type="standard"
                     data-size="large"
                     data-theme="outline"
                     data-text="sign_in_with"
                     data-shape="rectangular"
                     data-logo_alignment="left"
                     data-width="320">
                </div>
            </div>
        </form>

        <p class="register-link">
            Bạn chưa có tài khoản? <a href="<?= BASE_URL ?>register" class="form-link">Đăng ký ngay</a>
        </p>

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
