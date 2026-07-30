<?php

if (!function_exists('adminE')) {
    function adminE($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('adminMoney')) {
    function adminMoney($value) {
        return number_format((float)$value, 0, ',', '.') . ' VND';
    }
}

if (!function_exists('adminImageUrl')) {
    function adminImageUrl($image) {
        $image = (string)$image;
        if ($image === '') {
            return '';
        }

        if (preg_match('/^public\/uploads\//', $image)) {
            return BASE_URL . $image;
        }

        if (preg_match('/^uploads\//', $image)) {
            return BASE_URL . 'public/' . $image;
        }

        return BASE_URL . 'assets/images/' . $image;
    }
}

if (!function_exists('adminStart')) {
    function adminStart($title, $active, $flash = null) {
        include __DIR__ . '/../partials/header.php';
        ?>
        <style>
            .admin-title { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; font-family: "Segoe UI", Arial, sans-serif; }
            .admin-title h1 { font-family: "Segoe UI", Arial, sans-serif !important; font-size: 2rem; margin: 0; letter-spacing: 0 !important; text-transform: none; line-height: 1.25; font-weight: 800; }
            .admin-panel { background: #fff; border: 1px solid #e1e1e1; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: 0 14px 30px rgba(0,0,0,.04); }
            .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .9rem; }
            .admin-field label { display: block; font: 800 .9rem var(--font-ui); margin-bottom: .45rem; color: #111; }
            .admin-field input, .admin-field select, .admin-field textarea, .admin-table input, .admin-table select {
                width: 100%;
                min-height: 46px;
                padding: .72rem .85rem;
                border: 1px solid #cfcfcf;
                border-radius: 4px;
                background: #fff;
                color: #111;
                font-family: var(--font-ui);
                font-weight: 600;
                outline: none;
                transition: border-color .18s ease, box-shadow .18s ease;
            }
            .admin-field input:focus, .admin-field select:focus, .admin-field textarea:focus, .admin-table input:focus, .admin-table select:focus {
                border-color: #111;
                box-shadow: 0 0 0 3px rgba(220, 38, 38, .12);
            }
            .admin-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border: 1px solid #e1e1e1; border-radius: 6px; overflow: hidden; box-shadow: 0 14px 30px rgba(0,0,0,.04); }
            .admin-table th, .admin-table td { padding: 1rem; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; font-family: var(--font-ui); font-size: .95rem; }
            .admin-table th { background: #111; font-size: .78rem; text-transform: uppercase; color: #fff; letter-spacing: .04em; }
            .admin-table tbody tr:nth-child(even) { background: #fbfbfb; }
            .admin-table tbody tr:hover { background: #fff5f5; }
            .admin-actions { display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; }
            .admin-btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 4px; min-height: 44px; padding: .72rem 1rem; background: #111; color: #fff; text-decoration: none; font: 800 .9rem var(--font-ui); cursor: pointer; transition: transform .15s ease, box-shadow .15s ease, background .15s ease; }
            .admin-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 20px rgba(0,0,0,.12); }
            .admin-btn.light { background: #f0f0f0; color: #111; }
            .admin-btn.danger { background: #dc2626; color: #fff; }
            .admin-btn.delete { background: #6d28d9; color: #fff; }
            .admin-btn.ok { background: #111; color: #fff; }
            .admin-badge { display: inline-flex; padding: .34rem .7rem; border-radius: 999px; background: #eee; font: 800 .8rem var(--font-ui); }
            .admin-badge.ok { background: #111; color: #fff; }
            .admin-badge.off { background: #fee2e2; color: #991b1b; }
            .admin-flash { margin-bottom: 1rem; padding: .9rem 1rem; border-radius: 4px; font-family: var(--font-ui); background: #111; color: #fff; }
            .admin-flash.error { background: #dc2626; color: #fff; }
            .admin-flash.success { background: #111; color: #fff; }
            .admin-thumb { width: 72px; height: 72px; object-fit: contain; background: #f3f3f3; border-radius: 4px; border: 1px solid #eee; }
        </style>
        <div class="admin-container">
            <aside class="admin-sidebar">
                <ul>
                    <li><a href="<?= BASE_URL ?>admin" class="<?= $active === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>admin/products" class="<?= $active === 'products' ? 'active' : '' ?>">Sản phẩm</a></li>
                    <li><a href="<?= BASE_URL ?>admin/categories" class="<?= $active === 'categories' ? 'active' : '' ?>">Danh mục</a></li>
                    <li><a href="<?= BASE_URL ?>admin?page=orders">Đơn hàng</a></li>
                    <li><a href="<?= BASE_URL ?>admin/inventory" class="<?= $active === 'inventory' ? 'active' : '' ?>">Kho hàng</a></li>
                    <li><a href="<?= BASE_URL ?>admin/coupons" class="<?= $active === 'coupons' ? 'active' : '' ?>">Mã giảm giá</a></li>
                    <li><a href="<?= BASE_URL ?>admin?page=users">Người dùng</a></li>
                    <li><a href="<?= BASE_URL ?>admin?page=settings">Cài đặt</a></li>
                </ul>
            </aside>
            <main class="admin-content">
                <div class="admin-title">
                    <h1><?= adminE($title) ?></h1>
                </div>
                <?php if ($flash): ?>
                    <div class="admin-flash <?= adminE($flash['type'] ?? '') ?>"><?= adminE($flash['message'] ?? '') ?></div>
                <?php endif; ?>
        <?php
    }
}

if (!function_exists('adminEnd')) {
    function adminEnd() {
        ?>
            </main>
        </div>
        <?php include __DIR__ . '/../partials/footer.php';
    }
}
