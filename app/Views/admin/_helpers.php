<?php

if (!function_exists('adminE')) {
    function adminE($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('adminMoney')) {
    function adminMoney($value) {
        return number_format((float)$value, 0, ',', '.') . ' ₫';
    }
}

if (!function_exists('adminImageUrl')) {
    function adminImageUrl($image) {
        $image = trim((string)$image);
        if ($image === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $image)) {
            return $image;
        }

        if (preg_match('/^public\/uploads\//', $image)) {
            return BASE_URL . $image;
        }

        if (preg_match('/^uploads\//', $image)) {
            return BASE_URL . 'public/' . $image;
        }

        if (preg_match('/^assets\//', $image)) {
            return BASE_URL . $image;
        }

        return BASE_URL . 'assets/images/' . $image;
    }
}

if (!function_exists('adminGenderLabel')) {
    function adminGenderLabel($gender) {
        $labels = [
            'men' => 'Nam',
            'women' => 'Nữ'
        ];

        return $labels[$gender] ?? 'Chưa phân loại';
    }
}

if (!function_exists('adminColorLabel')) {
    function adminColorLabel($color) {
        $labels = [
            'White' => 'Trắng',
            'Brown' => 'Nâu',
            'Gray' => 'Xám',
            'Blue' => 'Lam'
        ];

        return $labels[$color] ?? $color;
    }
}

if (!function_exists('adminColorClass')) {
    function adminColorClass($color) {
        $classes = [
            'Black' => 'black',
            'Red' => 'red',
            'White' => 'white'
        ];

        return $classes[$color] ?? 'black';
    }
}


if (!function_exists('adminStart')) {
    function adminStart($title, $active, $flash = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $adminName = $_SESSION['user_name'] ?? 'Admin';
        $adminAvatar = !empty($_SESSION['user_avatar']) ? BASE_URL . $_SESSION['user_avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($adminName).'&background=111&color=fff&size=40';
        ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= adminE($title) ?> - Liên Hoa Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-bg: #fcfaf6;
            --admin-sidebar: #3e2723;
            --admin-primary: #b8976b;
            --admin-text: #3e2723;
            --admin-text-light: #796e65;
            --admin-border: #ebdcc6;
            --font-ui: 'Outfit', sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { max-width: 100%; overflow-x: hidden; }
        body { font-family: var(--font-ui); background: var(--admin-bg); color: var(--admin-text); display: flex; min-height: 100vh; }

        .admin-sidebar { width: 260px; background: var(--admin-sidebar); color: #fff; display: flex; flex-direction: column; flex-shrink: 0; transition: all 0.3s ease; }
        .admin-sidebar-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .admin-brand { padding: 1.5rem 2rem; font-size: 1.75rem; font-weight: 800; letter-spacing: 2px; text-decoration: none; color: #fff; display: block; }
        .admin-sidebar-close { display: none; background: none; border: none; color: #fff; font-size: 2rem; cursor: pointer; padding: 0.5rem 1rem; line-height: 1; }
        .admin-sidebar-backdrop { display: none; }
        
        .admin-nav { flex: 1; padding: 1.5rem 0; overflow-y: auto; }
        .admin-nav ul { list-style: none; }
        .admin-nav a { display: flex; align-items: center; padding: 0.85rem 2rem; color: #9ca3af; text-decoration: none; font-weight: 500; font-size: 0.95rem; border-left: 3px solid transparent; transition: all 0.2s; }
        .admin-nav a:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .admin-nav a.active { color: #fff; background: rgba(255,255,255,0.08); border-left-color: #fff; font-weight: 600; }

        .admin-main { flex: 1; display: flex; flex-direction: column; min-width: 0; width: 100%; }
        .admin-topbar { height: 70px; background: #fff; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; flex-shrink: 0; }
        .admin-topbar-left { display: flex; align-items: center; gap: 1rem; }
        .admin-sidebar-toggle { display: none; flex-direction: column; justify-content: space-between; width: 24px; height: 18px; background: transparent; border: none; cursor: pointer; padding: 0; }
        .admin-sidebar-toggle span { width: 100%; height: 2.5px; background: var(--admin-sidebar); border-radius: 2px; }
        .admin-topbar-title { font-weight: 600; font-size: 1.1rem; color: var(--admin-text-light); }
        .admin-user { display: flex; align-items: center; gap: 1rem; }
        .admin-user span { font-weight: 600; font-size: 0.9rem; }
        .admin-user img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid var(--admin-border); }
        .admin-logout { color: #dc2626; font-size: 0.85rem; font-weight: 600; text-decoration: none; margin-left: 0.5rem; }

        .admin-content { padding: 2rem; flex: 1; overflow-y: auto; max-width: 100%; }
        .admin-title { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .admin-title h1 { font-size: 1.75rem; font-weight: 700; color: #111; letter-spacing: -0.02em; }

        .admin-panel { background: #fff; border: 1px solid var(--admin-border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); max-width: 100%; }
        .admin-panel-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--admin-border); }

        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; }
        .admin-field { margin-bottom: 1.25rem; }
        .admin-field label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
        .admin-field input, .admin-field select, .admin-field textarea, .admin-table input, .admin-table select {
            width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; color: #111; font-family: var(--font-ui); font-size: 0.95rem; transition: all 0.2s;
        }
        .admin-field input:focus, .admin-field select:focus, .admin-field textarea:focus { border-color: #111; outline: none; box-shadow: 0 0 0 3px rgba(0,0,0,0.1); }

        .admin-table-wrapper { background: #fff; border: 1px solid var(--admin-border); border-radius: 12px; overflow-x: auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); width: 100%; max-width: 100%; -webkit-overflow-scrolling: touch; }
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; min-width: 600px; }
        .admin-table th, .admin-table td { padding: 1rem 1.25rem; border-bottom: 1px solid var(--admin-border); font-size: 0.9rem; }
        .admin-table th { background: #f9fafb; font-weight: 600; color: #4b5563; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        .admin-table tbody tr:hover { background: #f3f4f6; }
        .admin-table td { vertical-align: middle; }

        .admin-actions { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .admin-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 6px; font-weight: 600; font-size: 0.875rem; border: none; cursor: pointer; text-decoration: none; transition: all 0.2s; font-family: var(--font-ui); white-space: nowrap; }
        .admin-btn-sm { padding: 0.4rem 0.75rem; font-size: 0.8rem; border-radius: 4px; }
        .admin-btn.primary { background: var(--admin-sidebar); color: #fff; }
        .admin-btn.primary:hover { background: var(--admin-primary); color: var(--admin-sidebar); transform: translateY(-1px); box-shadow: 0 4px 6px rgba(74, 59, 50, 0.15); }
        .admin-btn.light { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .admin-btn.light:hover { background: #e5e7eb; }
        .admin-btn.danger { background: #ef4444; color: #fff; }
        .admin-btn.danger:hover { background: #dc2626; }

        .admin-badge { display: inline-flex; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
        .admin-badge.success { background: #dcfce7; color: #166534; }
        .admin-badge.error { background: #fee2e2; color: #991b1b; }
        .admin-badge.warning { background: #fef9c3; color: #854d0e; }
        .admin-badge.neutral { background: #f3f4f6; color: #374151; }
        @media (max-width: 900px) {
            .admin-sidebar { width: 210px; }
            .admin-content { padding: 1.25rem; }
            .admin-topbar { padding: 0 1.25rem; }
        }
        @media (max-width: 680px) {
            .admin-sidebar { width: 64px; }
            .admin-brand { padding: 1rem 0; text-align: center; font-size: 0; }
            .admin-brand:first-letter { font-size: 1.4rem; }
            .admin-nav a { padding: 0.8rem 0; justify-content: center; font-size: 0; }
            .admin-nav a:first-letter { font-size: 1rem; }
            .admin-topbar { flex-wrap: wrap; padding: 0.75rem 1rem; }
            .admin-topbar-title { display: none; }
            .admin-search { order: 1; flex-basis: 100%; max-width: none; }
            .admin-user { margin-left: auto; gap: 0.4rem; }
            .admin-user span { display: none; }
            .admin-content { padding: 1rem; }
            .admin-title h1 { font-size: 1.4rem; }
            .admin-form-layout, .admin-grid { grid-template-columns: 1fr !important; }
            .admin-panel { padding: 1rem; }
        }

        .admin-flash { margin-bottom: 1.5rem; padding: 1rem 1.25rem; border-radius: 8px; font-weight: 500; font-size: 0.95rem; display: flex; align-items: center; gap: 0.75rem; }
        .admin-flash.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .admin-flash.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .admin-thumb { width: 48px; height: 48px; object-fit: contain; background: #f3f4f6; border-radius: 6px; border: 1px solid var(--admin-border); }
        .admin-thumb-lg { width: 100px; height: 100px; }

        /* Dashboard Specific */
        .stat-card { display: flex; flex-direction: column; padding: 1.5rem; background: #fff; border-radius: 12px; border: 1px solid var(--admin-border); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-title { color: var(--admin-text-light); font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #111; line-height: 1; margin-bottom: 0.5rem; }
        .stat-change { font-size: 0.875rem; font-weight: 500; }
        .stat-change.up { color: #16a34a; }
        .stat-change.down { color: #dc2626; }

        /* Responsive Admin Styles */
        @media (max-width: 992px) {
            body { flex-direction: column; }
            .admin-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 280px;
                max-width: 85vw;
                height: 100vh;
                height: 100dvh;
                z-index: 3000;
                transform: translateX(-100%);
                box-shadow: 4px 0 25px rgba(0, 0, 0, 0.3);
            }
            .admin-sidebar.active {
                transform: translateX(0);
            }
            .admin-sidebar-close {
                display: block;
            }
            .admin-sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 2999;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            .admin-sidebar-backdrop.active {
                opacity: 1;
                visibility: visible;
            }
            .admin-sidebar-toggle {
                display: flex;
            }
            .admin-topbar {
                padding: 0 1.25rem;
                height: 64px;
            }
            .admin-content {
                padding: 1.5rem 1.25rem;
            }
            .admin-title h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 600px) {
            .admin-topbar {
                padding: 0 1rem;
            }
            .admin-topbar-title {
                display: none;
            }
            .admin-user span {
                display: none;
            }
            .admin-content {
                padding: 1.25rem 0.85rem;
            }
            .admin-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
                margin-bottom: 1.25rem;
            }
            .admin-title h1 {
                font-size: 1.35rem;
            }
            .admin-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .stat-card {
                padding: 1rem;
            }
            .stat-value {
                font-size: 1.5rem;
            }
            .admin-panel {
                padding: 1rem;
            }
            .admin-table th, .admin-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.82rem;
            }
            .admin-btn {
                padding: 0.5rem 0.85rem;
                font-size: 0.82rem;
            }
        }
    </style>
</head>
<body>
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <a href="<?= BASE_URL ?>admin" class="admin-brand">🌸 Liên Hoa</a>
            <button type="button" class="admin-sidebar-close" id="adminSidebarClose" aria-label="Đóng menu">&times;</button>
        </div>
        <nav class="admin-nav">
            <ul>
                <li><a href="<?= BASE_URL ?>admin" class="<?= $active === 'dashboard' ? 'active' : '' ?>">Bảng điều khiển</a></li>
                <li><a href="<?= BASE_URL ?>admin/products" class="<?= $active === 'products' ? 'active' : '' ?>">Sản phẩm</a></li>
                <li><a href="<?= BASE_URL ?>admin/categories" class="<?= $active === 'categories' ? 'active' : '' ?>">Danh mục</a></li>
                <li><a href="<?= BASE_URL ?>admin/orders" class="<?= $active === 'orders' ? 'active' : '' ?>">Đơn hàng</a></li>
                <li><a href="<?= BASE_URL ?>admin/inventory" class="<?= $active === 'inventory' ? 'active' : '' ?>">Kho hàng</a></li>
                <li><a href="<?= BASE_URL ?>admin/coupons" class="<?= $active === 'coupons' ? 'active' : '' ?>">Mã giảm giá</a></li>
                <li><a href="<?= BASE_URL ?>admin/after-sales" class="<?= $active === 'after-sales' ? 'active' : '' ?>">Đổi trả & bảo hành</a></li>
                <li><a href="<?= BASE_URL ?>admin/marketing" class="<?= $active === 'marketing' ? 'active' : '' ?>">Marketing</a></li>
                <li><a href="<?= BASE_URL ?>admin/support" class="<?= $active === 'support' ? 'active' : '' ?>">Hỗ trợ khách hàng</a></li>
                <li><a href="<?= BASE_URL ?>admin?page=users">Khách hàng</a></li>
                <li><a href="<?= BASE_URL ?>admin?page=settings">Cài đặt</a></li>
                <li><a href="<?= BASE_URL ?>" style="margin-top: 2rem; color: #60a5fa;">Xem trang chủ</a></li>
            </ul>
        </nav>
    </aside>
    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Mở menu quản trị">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="admin-topbar-title">Hệ thống Quản trị</div>
            </div>
            <div class="admin-user">
                <img src="<?= $adminAvatar ?>" alt="Admin">
                <span>Xin chào, <?= htmlspecialchars($adminName) ?></span>
                <a href="<?= BASE_URL ?>logout" class="admin-logout">Đăng xuất</a>
            </div>
        </header>

        <div class="admin-content">
            <div class="admin-title">
                <h1><?= adminE($title) ?></h1>
            </div>

            <?php if (!empty($flash['message'])): ?>
                <div class="admin-flash <?= adminE($flash['type'] ?? 'info') ?>">
                    <?= adminE($flash['message']) ?>
                </div>
            <?php endif; ?>
        <?php
    }
}

if (!function_exists('adminEnd')) {
    function adminEnd() {
        ?>
        </div> <!-- /.admin-content -->
    </main> <!-- /.admin-main -->

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('adminSidebarToggle');
        const closeBtn = document.getElementById('adminSidebarClose');
        const sidebar = document.getElementById('adminSidebar');
        const backdrop = document.getElementById('adminSidebarBackdrop');

        function toggleSidebar(open) {
            if (sidebar) sidebar.classList.toggle('active', open);
            if (backdrop) backdrop.classList.toggle('active', open);
            document.body.style.overflow = open ? 'hidden' : '';
        }

        if (toggleBtn) toggleBtn.addEventListener('click', function() { toggleSidebar(true); });
        if (closeBtn) closeBtn.addEventListener('click', function() { toggleSidebar(false); });
        if (backdrop) backdrop.addEventListener('click', function() { toggleSidebar(false); });
    });
    </script>
</body>
</html>
        <?php
    }
}
