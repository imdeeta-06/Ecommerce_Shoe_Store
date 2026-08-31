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
    <title>Xác minh Email - Liên Hoa</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>assets/images/lien-hoa-favicon.svg?v=3">
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
            margin-bottom: 1rem;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
        }

        .btn-resend {
            width: 100%;
            padding: 1.1rem;
            background: #fff;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 100px;
            font-family: var(--font-sans);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-resend:hover {
            background: #fdfaf8;
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
            <h2>Xác minh Email</h2>
            <p>Vui lòng kiểm tra hộp thư email của bạn</p>
        </div>

        <div id="alert-container">
            <?php if ($error): ?>
                <div class="alert-box error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-box success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($info): ?>
                <div class="alert-box info"><?= htmlspecialchars($info) ?></div>
            <?php endif; ?>
        </div>

        <form id="verify-form" onsubmit="event.preventDefault(); verifyOtp();">
            <div class="form-group">
                <label class="form-label" for="otp">Mã OTP (6 chữ số)</label>
                <input type="text" id="otp" name="otp" class="form-input" maxlength="6" required autocomplete="off">
            </div>
            <button type="submit" class="btn-submit" id="btn-verify">Xác nhận</button>
        </form>

        <form id="resend-form" onsubmit="event.preventDefault(); resendOtp();">
            <button type="submit" class="btn-resend" id="btn-resend">Gửi lại mã OTP</button>
        </form>
    </div>
</div>

<script>
    const csrfToken = <?= json_encode(\App\Helpers\SessionHelper::csrfToken(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function showAlert(message, type) {
        const container = document.getElementById('alert-container');
        container.innerHTML = `<div class="alert-box ${type}">${message}</div>`;
    }

    async function verifyOtp() {
        const btn = document.getElementById('btn-verify');
        const otp = document.getElementById('otp').value;
        if (!otp) return;

        btn.disabled = true;
        btn.textContent = 'Đang xác thực...';

        try {
            const response = await fetch('<?= BASE_URL ?>verify-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ otp: otp })
            });
            const data = await response.json();

            if (response.ok && data.success) {
                showAlert(data.message, 'success');
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                showAlert(data.message || 'Lỗi không xác định', 'error');
            }
        } catch (error) {
            showAlert('Có lỗi xảy ra khi kết nối đến máy chủ.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Xác nhận';
        }
    }

    async function resendOtp() {
        const btn = document.getElementById('btn-resend');
        btn.disabled = true;
        btn.textContent = 'Đang gửi...';

        try {
            const response = await fetch('<?= BASE_URL ?>resend-verification-otp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                }
            });
            const data = await response.json();

            if (response.ok && data.success) {
                showAlert(data.message, 'success');
            } else {
                showAlert(data.message || 'Lỗi không xác định', 'error');
            }
        } catch (error) {
            showAlert('Có lỗi xảy ra khi kết nối đến máy chủ.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Gửi lại mã OTP';
        }
    }
</script>
</body>
</html>
