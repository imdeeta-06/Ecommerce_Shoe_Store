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
    <title>Đăng nhập - Pháp Phục An Nhiên</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600&family=Be+Vietnam+Pro:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
    <style>
        .zen-auth-page {
            min-height: 100vh;
            display: flex;
            background-color: #fbfbf8;
            color: #3e2723;
            font-family: 'Be Vietnam Pro', 'Outfit', sans-serif;
        }
        .zen-auth-card {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2rem;
            background: #e2d3d3;
            border-radius: 1.25rem;
            box-shadow: 0 10px 30px rgba(62, 39, 35, 0.06);
            border: 1px solid #f2e9dc;
        }
        .zen-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: #3e2723;
            letter-spacing: 1px;
            margin-bottom: 0.25rem;
        }
        .zen-subtitle {
            color: #796e65;
            font-size: 0.95rem;
            font-style: italic;
            font-family: 'Cormorant Garamond', serif;
        }
        .zen-lotus-icon {
            width: 42px;
            height: 42px;
            margin: 0 auto 0.75rem auto;
            display: block;
            fill: #b8976b;
        }
        .zen-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #5c4a3e;
            margin-bottom: 0.5rem;
        }
        .zen-input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid #e2d7c7;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            color: #3e2723;
            background-color: #fdfbf7;
            transition: all 0.25s ease;
        }
        .zen-input:focus {
            outline: none;
            border-color: #b8976b;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(184, 151, 107, 0.15);
        }
        .zen-btn {
            display: block;
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, #3e2723 0%, #5a3c35 100%);
            color: #fdfbf7;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(62, 39, 35, 0.15);
        }
        .zen-btn:hover {
            background: linear-gradient(135deg, #5a3c35 0%, #b8976b 100%);
            box-shadow: 0 6px 18px rgba(184, 151, 107, 0.25);
            transform: translateY(-1px);
        }
        .zen-link {
            color: #8c6d46;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .zen-link:hover {
            color: #3e2723;
            text-decoration: underline;
        }
        .zen-toggle-pw {
            position: absolute;
            right: 12px;
            top: 36px;
            background: none;
            border: none;
            cursor: pointer;
            color: #8c6d46;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .zen-toggle-pw:hover {
            color: #3e2723;
        }
    </style>
</head>
<body>

<main class="zen-auth-page auth-page">
    <div class="auth-form-wrapper">
        <div class="zen-auth-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <svg class="zen-lotus-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32 10C34.5 17 38 23 44 27C38 29 33 34 32 42C31 34 26 29 20 27C26 23 29.5 17 32 10Z" opacity="0.9"/>
                    <path d="M32 20C37 26 44 30 52 30C45 34 40 41 38 50C35 44 32 38 32 20Z" opacity="0.6"/>
                    <path d="M32 20C27 26 20 30 12 30C19 34 24 41 26 50C29 44 32 38 32 20Z" opacity="0.6"/>
                    <path d="M22 46C27 45 32 47 32 52C32 47 37 45 42 46C38 50 32 54 32 54C32 54 26 50 22 46Z" opacity="0.8"/>
                </svg>
                <h2 class="zen-title">ĐĂNG NHẬP</h2>
                <p class="zen-subtitle">Tâm an yên, phong thái tự tại</p>
            </div>
            
            <?php if ($error): ?>
                <div class="client-flash error" style="background-color: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="client-flash success" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form action="<?= BASE_URL ?>login" method="POST">
                <div class="client-form-group" style="margin-bottom: 1.25rem;">
                    <label class="zen-label" for="email">Địa chỉ Email</label>
                    <input type="email" id="email" name="email" class="zen-input" placeholder="nhapemail@example.com" required value="<?= htmlspecialchars($oldEmail) ?>">
                </div>
                <div class="client-form-group" style="position: relative; margin-bottom: 1.25rem;">
                    <label class="zen-label" for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="zen-input" placeholder="••••••••" required style="padding-right: 60px;">
                    <button type="button" id="togglePassword" class="zen-toggle-pw">Hiện</button>
                </div>
                
                <div class="client-auth-options" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem; margin-bottom: 1.75rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #5c4a3e;">
                        <input type="checkbox" name="remember" style="accent-color: #b8976b; width: 16px; height: 16px; cursor: pointer;"> Ghi nhớ tôi
                    </label>
                    <a href="<?= BASE_URL ?>forgot-password" class="zen-link">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="zen-btn">Đăng nhập</button>

                <p style="text-align: center; margin-top: 2rem; font-size: 0.875rem; color: #796e65;">
                    Chưa có tài khoản? <a href="<?= BASE_URL ?>register" class="zen-link" style="font-weight: 600;">Đăng ký ngay</a>
                </p>
            </form>
        </div>
    </div>
    <div class="auth-image" style="background-image: url('<?= BASE_URL ?>assets/images/zen-login-banner.jpg?v=<?= time() ?>') !important; background-size: cover; background-position: center;"></div>
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

