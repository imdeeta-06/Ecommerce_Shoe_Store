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
    <title><?= htmlspecialchars($metaTitle ?? 'Liên Hoa - Pháp phục & Đồ lam Phật giáo cao cấp', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Mua đồ lam đi chùa, pháp phục Tăng – Ni, túi đeo đi chùa, vòng tay trầm hương và tràng hạt tại Liên Hoa.', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($canonicalUrl)): ?><link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-left">
                <span>🌸 Kính chào quý Phật tử — Giao hàng miễn phí đơn từ 500.000đ</span>
            </div>
            <div class="top-bar-right">
                <a href="<?= BASE_URL ?>admin">Khu vực người bán</a>
                <span class="top-bar-divider">|</span>
                <a href="<?= BASE_URL ?>admin">Quản trị</a>
            </div>
        </div>
    </div>

    <header class="header">
        <div class="logo">
            <a href="<?= BASE_URL ?>" class="logo-wrapper">
                <span class="logo-icon">🌸</span>
                <div class="logo-text-group">
                    <span class="logo-main">Liên Hoa</span>
                    <span class="logo-sub">ĐỒ LAM PHẬT GIÁO</span>
                </div>
            </a>
        </div>
        <nav class="nav-links">
            <a href="<?= BASE_URL ?>">Trang Chủ</a>
            <a href="<?= BASE_URL ?>shop">Sản Phẩm</a>
            <a href="<?= BASE_URL ?>about">Giới Thiệu</a>
            <a href="<?= BASE_URL ?>faqs">Blog</a>
            <a href="<?= BASE_URL ?>support">Liên Hệ</a>
        </nav>
        <div class="nav-actions">
            <form class="search-bar" action="<?= BASE_URL ?>shop" method="GET">
                <button type="submit" aria-label="Search" style="border: 0; background: transparent; padding: 0; display: flex; align-items: center; color: inherit; cursor: pointer;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
                <input type="text" name="q" placeholder="Tìm kiếm" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>
            <a href="<?= BASE_URL ?>wishlist" class="icon-btn" title="Yêu thích">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            </a>
            <a href="<?= BASE_URL ?>cart" class="icon-btn" title="Giỏ hàng">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-dropdown">
                    <a href="#" class="icon-btn btn-user-pill" title="Tài khoản" style="padding: 0.35rem 0.85rem; height: auto; border-radius: 100px; display: inline-flex; align-items: center; background: transparent; border: 1px solid var(--border-color); color: var(--text-color); font-weight: 500; font-size: 0.85rem;">
                        <img src="<?= !empty($_SESSION['user_avatar']) ? BASE_URL . $_SESSION['user_avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['user_name'] ?? 'User').'&background=8d5b4c&color=fff&size=40' ?>" alt="Avatar" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; margin-right: 6px;">
                        Tài khoản
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
                <a href="<?= BASE_URL ?>login" class="btn-user-pill">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Tài khoản
                </a>
            <?php endif; ?>
        </div>
    </header>

    <script>
    const BASE_URL = '<?= BASE_URL ?>';
    
    document.addEventListener('DOMContentLoaded', () => {
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
