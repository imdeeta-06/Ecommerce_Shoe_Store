<?php
$error = \App\Helpers\SessionHelper::getFlash('error') ?? '';
$success = \App\Helpers\SessionHelper::getFlash('success') ?? '';
$info = \App\Helpers\SessionHelper::getFlash('info') ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác minh OTP Đặt lại mật khẩu - PaceUp</title>
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
            font-size: 1.5rem;
            letter-spacing: 8px;
            text-align: center;
            color: var(--text-main);
            background: #fff;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 109, 92, 0.1);
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
        
        .alert-box.info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            color: #0d47a1;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Nhập mã OTP</h2>
            <p>Mã OTP đã được gửi đến email của bạn</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-box error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($info): ?>
            <div class="alert-box info">
                <?= htmlspecialchars($info) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>verify-reset-otp" method="POST">
            <div class="form-group">
                <label class="form-label" for="otp">Mã OTP (6 chữ số)</label>
                <input type="text" id="otp" name="otp" class="form-input" maxlength="6" required autocomplete="off">
            </div>
            <button type="submit" class="btn-submit">Xác nhận</button>
        </form>
    </div>
</div>

</body>
</html>
