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
    <title>PaceUp - Chuyên phân phối sản phẩm Nike</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Open+Sans:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <header class="header">
        <div class="logo">
            <a href="<?= BASE_URL ?>">PACEUP</a>
        </div>
        <nav class="nav-links">
            <a href="<?= htmlspecialchars(BASE_URL . 'shop?gender=men') ?>">Nam</a>
            <a href="<?= htmlspecialchars(BASE_URL . 'shop?gender=women') ?>">Nữ</a>
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
                    <a href="#" class="icon-btn" title="Tài khoản" style="padding: 0; overflow: hidden; border-radius: 50%; border: 1px solid #ddd; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; background: #f5f5f5;">
                        <img src="<?= !empty($_SESSION['user_avatar']) ? BASE_URL . $_SESSION['user_avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['user_name'] ?? 'User').'&background=2A9D8F&color=fff&size=40' ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                    </a>
                    <div class="dropdown-menu">
                        <span class="dropdown-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="<?= BASE_URL ?>admin">Admin Panel</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>account">Tài khoản</a>
                        <a href="<?= BASE_URL ?>logout">Đăng xuất</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>login" style="display: inline-flex; align-items: center; background: #111; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-family: var(--font-ui); font-size: 0.9rem;">
                    Đăng nhập
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
