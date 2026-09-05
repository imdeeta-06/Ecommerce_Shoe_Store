<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('productAssetPath')) {
    function productAssetPath($image) {
        if (!$image) return '';
        if (strpos($image, 'http') === 0) return $image;
        if (strpos($image, 'public/uploads/') === 0) return $image;
        if (strpos($image, 'uploads/') === 0) return 'public/' . $image;
        return 'assets/images/' . $image;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(\App\Helpers\SessionHelper::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($metaTitle ?? 'Liên Hoa - Pháp phục & Đồ lam Phật giáo cao cấp', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>assets/images/lien-hoa-favicon.svg?v=3">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/images/logo-lotus.jpg">
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Mua đồ lam đi chùa, pháp phục Tăng – Ni, túi đeo đi chùa, vòng tay trầm hương và tràng hạt tại Liên Hoa.', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($canonicalUrl)): ?><link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= (int)@filemtime(__DIR__ . '/../../public/assets/css/style.css') ?>">
</head>
<body>

    <header class="header">
        <div class="header-left">
            <button class="mobile-menu-toggle" id="mobileMenuBtn" aria-label="Mở menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            <div class="logo">
                <a href="<?= BASE_URL ?>" class="logo-wrapper">
                    <img src="<?= BASE_URL ?>assets/images/logo-lotus.jpg" alt="Liên Hoa" class="logo-icon-img">
                    <div class="logo-text-group">
                        <span class="logo-main">Liên Hoa</span>
                        <span class="logo-sub">ĐỒ LAM PHẬT GIÁO</span>
                    </div>
                </a>
            </div>
        </div>

        <nav class="nav-links" id="mainNavLinks">
            <div class="mobile-nav-header">
                <div class="logo-wrapper">
                    <img src="<?= BASE_URL ?>assets/images/logo-lotus.jpg" alt="Liên Hoa" class="logo-icon-img">
                    <span class="logo-main" style="font-size: 1.4rem;">Liên Hoa</span>
                </div>
                <button class="mobile-nav-close" id="mobileMenuClose" aria-label="Đóng menu">&times;</button>
            </div>
            <a href="<?= BASE_URL ?>" class="nav-item">Trang Chủ</a>
            <a href="<?= BASE_URL ?>shop" class="nav-item">Sản Phẩm</a>
            <a href="<?= BASE_URL ?>about" class="nav-item">Giới Thiệu</a>
            <a href="<?= BASE_URL ?>faqs" class="nav-item">Hỏi Đáp</a>
            <a href="<?= BASE_URL ?>support" class="nav-item">Liên Hệ</a>
            
            <div class="mobile-nav-footer">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="mobile-user-card">
                        <img src="<?= !empty($_SESSION['user_avatar']) ? BASE_URL . $_SESSION['user_avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['user_name'] ?? 'User').'&length=1&background=2A9D8F&color=fff&size=40' ?>" alt="Avatar" class="mobile-user-avatar">
                        <div>
                            <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản') ?></strong>
                            <div class="mobile-user-links">
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <a href="<?= BASE_URL ?>admin">Admin Panel</a> &bull;
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>account">Tài khoản</a> &bull;
                                <a href="<?= BASE_URL ?>logout" style="color: #dc2626;">Đăng xuất</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login" class="btn-login-mobile">Đăng nhập / Đăng ký</a>
                <?php endif; ?>
            </div>
        </nav>
        <div class="mobile-menu-backdrop" id="mobileMenuBackdrop"></div>

        <div class="nav-actions">
            <button type="button" class="icon-btn search-toggle-btn" id="searchToggleBtn" aria-label="Tìm kiếm" title="Tìm kiếm">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </button>
            <form class="search-bar" id="headerSearchBar" action="<?= BASE_URL ?>shop" method="GET">
                <button type="submit" aria-label="Search" style="border: 0; background: transparent; padding: 0; display: flex; align-items: center; color: inherit; cursor: pointer;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
                <input type="text" name="q" placeholder="Tìm kiếm pháp phục, chuỗi hạt..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                <button type="button" class="search-close-mobile" id="searchCloseBtn" aria-label="Đóng tìm kiếm">&times;</button>
            </form>
            <a href="<?= BASE_URL ?>wishlist" class="icon-btn" title="Yêu thích">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            </a>
            <a href="<?= BASE_URL ?>cart" class="icon-btn" title="Giỏ hàng">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-dropdown">
                    <a href="#" class="icon-btn user-avatar-btn" title="Tài khoản" style="padding: 0; overflow: hidden; border-radius: 50%; border: 1px solid #ddd; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; background: #f5f5f5;">
                        <img src="<?= !empty($_SESSION['user_avatar']) ? BASE_URL . $_SESSION['user_avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['user_name'] ?? 'User').'&length=1&background=2A9D8F&color=fff&size=40' ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                    </a>
                    <div class="dropdown-menu">
                        <span class="dropdown-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Tài khoản') ?></span>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="<?= BASE_URL ?>admin">Admin Panel</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>account">Tài khoản</a>
                        <a href="<?= BASE_URL ?>logout">Đăng xuất</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>login" class="btn-login-nav">
                    Đăng nhập
                </a>
            <?php endif; ?>
        </div>
    </header>

    <script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
    const BASE_URL = '<?= BASE_URL ?>';
    window.CSRF_TOKEN = <?= json_encode(\App\Helpers\SessionHelper::csrfToken(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    (() => {
        const originalFetch = window.fetch.bind(window);
        window.fetch = (input, init = {}) => {
            const method = String(init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
            const requestUrl = new URL(typeof input === 'string' ? input : input.url, window.location.href);
            if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && requestUrl.origin === window.location.origin) {
                const headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
                if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', window.CSRF_TOKEN);
                init = {...init, headers};
            }
            return originalFetch(input, init);
        };

        const secureForm = form => {
            const method = String(form.method || 'GET').toUpperCase();
            if (!['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) || form.querySelector('input[name="csrf_token"]')) return;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = window.CSRF_TOKEN;
            form.appendChild(input);
        };
        document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('form').forEach(secureForm));
        document.addEventListener('submit', event => secureForm(event.target), true);
    })();
    
    document.addEventListener('DOMContentLoaded', () => {
        // Mobile Menu Toggle logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenuClose = document.getElementById('mobileMenuClose');
        const mainNavLinks = document.getElementById('mainNavLinks');
        const mobileMenuBackdrop = document.getElementById('mobileMenuBackdrop');
        const searchToggleBtn = document.getElementById('searchToggleBtn');
        const headerSearchBar = document.getElementById('headerSearchBar');
        const searchCloseBtn = document.getElementById('searchCloseBtn');

        function toggleMobileMenu(open) {
            if (mainNavLinks) {
                mainNavLinks.classList.toggle('active', open);
            }
            if (mobileMenuBackdrop) {
                mobileMenuBackdrop.classList.toggle('active', open);
            }
            document.body.style.overflow = open ? 'hidden' : '';
        }

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => toggleMobileMenu(true));
        }
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', () => toggleMobileMenu(false));
        }
        if (mobileMenuBackdrop) {
            mobileMenuBackdrop.addEventListener('click', () => toggleMobileMenu(false));
        }

        // Mobile Search Toggle logic
        if (searchToggleBtn && headerSearchBar) {
            searchToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                headerSearchBar.classList.toggle('mobile-open');
                if (headerSearchBar.classList.contains('mobile-open')) {
                    const input = headerSearchBar.querySelector('input');
                    if (input) input.focus();
                }
            });
        }
        if (searchCloseBtn && headerSearchBar) {
            searchCloseBtn.addEventListener('click', () => {
                headerSearchBar.classList.remove('mobile-open');
            });
        }
        const cartIcon = document.querySelector('a[href="<?= BASE_URL ?>cart"]');
        if (cartIcon && !cartIcon.querySelector('.cart-badge')) {
            const badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.style.display = 'none';
            badge.textContent = '0';
            cartIcon.appendChild(badge);
            cartIcon.style.position = 'relative';
        }
        
        // Define global updateBadge if not defined
        if (typeof window.updateBadgeGlobal !== 'function') {
            window.updateBadgeGlobal = function(cartCount = null) {
                if (cartCount !== null) {
                    document.querySelectorAll('.cart-badge').forEach(b => {
                        b.textContent = cartCount;
                        b.style.display = cartCount > 0 ? 'flex' : 'none';
                    });
                    return;
                }
                
                fetch(BASE_URL + 'cart/get')
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const total = data.cart_count || 0;
                            document.querySelectorAll('.cart-badge').forEach(b => {
                                b.textContent = total;
                                b.style.display = total > 0 ? 'flex' : 'none';
                            });
                        }
                    })
                    .catch(e => console.error(e));
            };
        }
        window.updateBadgeGlobal();
    });
    </script>
