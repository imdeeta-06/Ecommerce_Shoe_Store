<?php include __DIR__ . '/../../partials/header.php'; ?>

<style>
    .admin-form-panel {
        background: #fff;
        border: 1px solid #f0f0f0;
        border-radius: 12px;
        padding: 2rem;
        max-width: 760px;
    }
    .admin-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .admin-form-field label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-family: var(--font-ui);
        font-size: 0.9rem;
    }
    .admin-form-field input {
        width: 100%;
        padding: 0.85rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-family: var(--font-ui);
    }
    .admin-form-errors {
        margin-bottom: 1rem;
        padding: 0.9rem 1rem;
        background: #feecec;
        color: #9d1c1c;
        border-radius: 6px;
        font-family: var(--font-ui);
    }
    @media (max-width: 800px) {
        .admin-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="admin-container">
    <aside class="admin-sidebar">
        <ul>
            <li><a href="<?= BASE_URL ?>admin?page=dashboard">Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>admin/products">Sản phẩm</a></li>
            <li><a href="<?= BASE_URL ?>admin/categories">Danh mục</a></li>
            <li><a href="<?= BASE_URL ?>admin?page=orders">Đơn hàng</a></li>
            <li><a href="<?= BASE_URL ?>admin/inventory">Kho hàng</a></li>
            <li><a href="<?= BASE_URL ?>admin?page=coupons">Mã giảm giá</a></li>
            <li><a href="<?= BASE_URL ?>admin/reviews">Đánh giá</a></li>
            <li><a href="<?= BASE_URL ?>admin?page=users" class="active">Người dùng</a></li>
            <li><a href="<?= BASE_URL ?>admin?page=settings">Cài đặt</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <h2>Thêm quản trị viên</h2>
            <a href="<?= BASE_URL ?>admin?page=users" style="font-weight: 600; font-family: var(--font-ui); color: #111;">&larr; Back to Users</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="admin-form-errors">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="admin-form-panel" method="post" action="<?= BASE_URL ?>admin/users/create">
            <div class="admin-form-grid">
                <div class="admin-form-field">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($old['full_name'] ?? '') ?>">
                </div>
                <div class="admin-form-field">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($old['display_name'] ?? '') ?>">
                </div>
                <div class="admin-form-field">
                    <label>Email *</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>
                <div class="admin-form-field">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" maxlength="20" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
                <div class="admin-form-field">
                    <label>Password *</label>
                    <input type="password" name="password" required>
                </div>
                <div class="admin-form-field">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <div class="admin-form-field">
                    <label>Role</label>
                    <input type="text" value="Admin" readonly>
                    <input type="hidden" name="role" value="admin">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>admin?page=users" class="btn" style="padding: 0.85rem 1.5rem; background: #f5f5f5; color: #333; border-radius: 6px; text-decoration: none; font-weight: 600;">Cancel</a>
                <button type="submit" class="btn btn-dark" style="padding: 0.85rem 1.5rem; border-radius: 6px;">Create Admin</button>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
