<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Thêm quản trị viên', 'users', $flash ?? null);
?>

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

<div class="admin-panel" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 1rem;">
        <div>
            <div class="admin-panel-title" style="margin-bottom: 0; border: none; padding: 0;">Thêm quản trị viên</div>
        </div>
        <a class="admin-btn primary" href="<?= BASE_URL ?>admin/products" style="background: #f5f5f5; color: #333; font-weight: 600; border: 1px solid #e5e7eb;">
            &larr; Quay lại danh sách
        </a>
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
                <label>Họ và Tên *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($old['full_name'] ?? '') ?>">
            </div>
            <div class="admin-form-field">
                <label>Tên hiển thị</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($old['display_name'] ?? '') ?>">
            </div>
            <div class="admin-form-field">
                <label>Email *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
            </div>
            <div class="admin-form-field">
                <label>Số điện thoại</label>
                <input type="tel" name="phone" maxlength="20" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
            </div>
            <div class="admin-form-field">
                <label>Mật khẩu *</label>
                <input type="password" name="password" required>
            </div>
            <div class="admin-form-field">
                <label>Xác nhận mật khẩu *</label>
                <input type="password" name="confirm_password" required>
            </div>
            <div class="admin-form-field">
                <label>Vai trò</label>
                <input type="text" value="Admin" readonly>
                <input type="hidden" name="role" value="admin">
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
            <a href="<?= BASE_URL ?>admin/products" class="admin-btn" style="padding: 0.85rem 1.5rem; background: #f5f5f5; color: #333; border-radius: 6px; text-decoration: none; font-weight: 600; border: 1px solid #ddd;">Hủy</a>
            <button type="submit" class="admin-btn primary" style="padding: 0.85rem 1.5rem; border-radius: 6px; border: none; cursor: pointer; background: #000; color: #fff;">Tạo tài khoản</button>
        </div>
    </form>
</div>

<?php adminEnd(); ?>
