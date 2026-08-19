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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8b6d5c;
            --primary-hover: #755a4b;
            --bg-page: #f9f6f0;
            --text-main: #2c1e16;
            --text-muted: #8c827a;
            --border-color: #dcd7cd;
            --font-serif: 'Playfair Display', Georgia, serif;
            --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1.5rem;
        }

        .auth-container {
            width: 100%;
            max-width: 480px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Tabs Toggle */
        .auth-tabs {
            display: flex;
            background: #fff;
            padding: 6px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(139, 109, 92, 0.05);
            border: 1px solid var(--border-color);
        }

        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .auth-tab.active {
            background-color: var(--primary-color);
            color: #fff;
            box-shadow: 0 4px 10px rgba(139, 109, 92, 0.15);
        }

        /* Form Card */
        .auth-card {
            background: #fff;
            border-radius: 24px;
            padding: 3rem 2.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(139, 109, 92, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Logo Badge */
        .logo-badge {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a88b77, #8b6d5c);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 15px rgba(139, 109, 92, 0.2);
        }

        .logo-badge svg {
            width: 38px;
            height: 38px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .auth-header h2 {
            font-family: var(--font-serif);
            font-size: 2rem;
            color: var(--text-main);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .auth-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Forms */
        form {
            width: 100%;
        }

        .form-group {
            width: 100%;
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #5c524a;
        }

        .form-link {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-decoration: underline;
            transition: color 0.2s;
        }

        .form-link:hover {
            color: var(--primary-color);
        }

        .input-wrapper {
            position: relative;
            width: 100%;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            font-family: var(--font-sans);
            font-size: 0.95rem;
            color: var(--text-main);
            background: #fff;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 109, 92, 0.1);
        }

        .form-input::placeholder {
            color: #bcae9e;
        }

        .btn-toggle-pw {
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-family: var(--font-sans);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-main);
        }

        .auth-options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: #7c726a;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            user-select: none;
        }

        .remember-me input {
            accent-color: var(--primary-color);
            width: 16px;
            height: 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        /* Buttons */
        .btn-submit {
            width: 100%;
            padding: 1.1rem;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 100px;
            font-family: var(--font-sans);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 109, 92, 0.2);
            text-align: center;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            width: 100%;
            margin: 2rem 0;
            color: #a89f95;
            font-size: 0.85rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }

        .divider:not(:empty)::before {
            margin-right: 1rem;
        }

        .divider:not(:empty)::after {
            margin-left: 1rem;
        }

        .btn-google {
            width: 100%;
            padding: 1rem;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 100px;
            color: #4a423a;
            font-family: var(--font-sans);
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
        }

        .btn-google:hover {
            background: #fbfaf8;
            border-color: #c0b5a8;
        }

        /* Flash messages */
        .alert-box {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .alert-box.error {
            background: #fdf2f2;
            border: 1px solid #f8b4b4;
            color: #c81e1e;
        }

        .alert-box.success {
            background: #f3faf7;
            border: 1px solid #def7ec;
            color: #03543f;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <!-- Tabs Selector -->
    <div class="auth-tabs">
        <a href="<?= BASE_URL ?>login" class="auth-tab active">Đăng nhập</a>
        <a href="<?= BASE_URL ?>register" class="auth-tab">Đăng ký</a>
    </div>

    <!-- Form Card -->
    <div class="auth-card">
        <!-- Logo Badge with Lotus SVG -->
        <div class="logo-badge">
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M32 10C32 10 23 23 23 37C23 46 32 50 32 50C32 50 41 46 41 37C41 23 32 10 32 10Z" fill="#ffa7c4" />
                <path d="M32 16C32 16 17 28 17 40C17 49 25 52 32 52C39 52 47 49 47 40C47 28 32 16 32 16Z" fill="#ff7da7" opacity="0.85" />
                <path d="M32 23C32 23 12 32 12 43C12 51 21 53 32 53C43 53 52 51 52 43C52 32 32 23 32 23Z" fill="#e64c72" opacity="0.7" />
                <circle cx="32" cy="45" r="4" fill="#ffd166" />
                <circle cx="32" cy="45" r="2" fill="#fff" />
            </svg>
        </div>

        <div class="auth-header">
            <h2>Chào mừng trở lại</h2>
            <p>Đăng nhập để tiếp tục mua sắm</p>
        </div>

        <!-- Flash alerts -->
        <?php if ($error): ?>
            <div class="alert-box error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-box success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= BASE_URL ?>login" method="POST">
            <div class="form-group">
                <div class="form-label-row">
                    <label class="form-label" for="email">Email</label>
                </div>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" class="form-input" placeholder="email@example.com" required value="<?= htmlspecialchars($oldEmail) ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="form-label-row">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <a href="<?= BASE_URL ?>forgot-password" class="form-link">Quên mật khẩu?</a>
                </div>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                    <button type="button" id="togglePassword" class="btn-toggle-pw">Hiện</button>
                </div>
            </div>

            <div class="auth-options-row">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ tôi</span>
                </label>
            </div>

            <button type="submit" class="btn-submit">Đăng nhập</button>

            <div class="divider">hoặc</div>

            <button type="button" class="btn-google" onclick="alert('Tính năng đăng nhập bằng Google đang được bảo trì. Vui lòng đăng nhập bằng Email!')">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.64 9.2c0-.63-.06-1.25-.16-1.84H9v3.47h4.84c-.21 1.12-.84 2.07-1.8 2.72v2.24h2.9c1.7-1.57 2.7-3.87 2.7-6.59z" fill="#4285F4"/>
                    <path d="M9 18c2.43 0 4.47-.8 5.96-2.2l-2.9-2.24c-.8.54-1.84.87-3.06.87-2.35 0-4.33-1.58-5.04-3.71H.95v2.3C2.43 15.89 5.5 18 9 18z" fill="#34A853"/>
                    <path d="M3.96 10.72c-.18-.54-.28-1.12-.28-1.72s.1-1.18.28-1.72V5H.95A8.99 8.99 0 000 9c0 1.45.35 2.82.95 4.05l3.01-2.33z" fill="#FBBC05"/>
                    <path d="M9 3.58c1.32 0 2.5.45 3.44 1.35L15 2.4C13.46.96 11.43 0 9 0 5.5 0 2.43 2.11.95 5.05l3.01 2.33c.71-2.13 2.69-3.71 5.04-3.71z" fill="#EA4335"/>
                </svg>
                <span>Đăng nhập bằng Google</span>
            </button>
        </form>
    </div>
</div>

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
