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

        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #5c524a;
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
    </style>
</head>
<body>

<div class="auth-container">
    <!-- Tabs Selector -->
    <div class="auth-tabs">
        <a href="<?= BASE_URL ?>login" class="auth-tab">Đăng nhập</a>
        <a href="<?= BASE_URL ?>register" class="auth-tab active">Đăng ký</a>
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
            <h2>Đăng ký thành viên</h2>
            <p>Tạo tài khoản mới cùng PaceUp</p>
        </div>

        <!-- Flash alerts -->
        <?php if ($error): ?>
            <div class="alert-box error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= BASE_URL ?>register" method="POST">
            <div class="form-group">
                <label class="form-label" for="full_name">Họ và tên</label>
                <input type="text" id="full_name" name="full_name" class="form-input" placeholder="Nguyễn Văn A" required value="<?= htmlspecialchars($old['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="email@example.com" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone" class="form-input" placeholder="09xxxxxxxx" maxlength="20" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label" for="confirm_password">Xác nhận mật khẩu</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Đăng ký</button>
        </form>
    </div>
</div>

</body>
</html>
