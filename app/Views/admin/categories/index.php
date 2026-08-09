<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Quản lý danh mục', 'categories', $flash ?? null);
?>

<div class="admin-panel" style="margin-bottom: 2rem;">
    <div style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 1rem;">
        <div class="admin-panel-title" style="margin-bottom: 0; border: none; padding: 0;">Thêm danh mục mới</div>
        <p style="color: var(--admin-text-light); font-size: 0.9rem; margin-top: 0.25rem;">Slug có thể để trống, hệ thống sẽ tự tạo từ tên danh mục.</p>
    </div>

    <form class="admin-grid" method="post" action="<?= BASE_URL ?>admin/categories/create" style="align-items: end; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="admin-field" style="margin-bottom: 0;">
            <label>Tên danh mục *</label>
            <input type="text" name="name" required placeholder="Ví dụ: Running">
        </div>
        <div class="admin-field" style="margin-bottom: 0;">
            <label>Slug</label>
            <input type="text" name="slug" placeholder="Tự tạo từ tên...">
        </div>
        <div class="admin-field" style="margin-bottom: 0;">
            <label>Trạng thái</label>
            <select name="status">
                <option value="1">Đang hiển thị</option>
                <option value="0">Đã ẩn</option>
            </select>
        </div>
        <div class="admin-actions">
            <button class="admin-btn primary" type="submit" style="width: 100%;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
                Thêm danh mục
            </button>
        </div>
    </form>
</div>

<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên danh mục</th>
                <th>Slug</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <?php $isActive = (int)$category['status'] === 1; ?>
                <tr>
                    <td style="color: #6b7280; font-family: monospace;">#<?= (int)$category['id'] ?></td>
                    <td style="font-weight: 600; color: #111;"><?= adminE($category['name']) ?></td>
                    <td style="color: #6b7280; font-size: 0.9rem;"><?= adminE($category['slug']) ?></td>
                    <td>
                        <span class="admin-badge <?= $isActive ? 'success' : 'neutral' ?>">
                            <?= $isActive ? 'Hiển thị' : 'Đã ẩn' ?>
                        </span>
                    </td>
                    <td>
                        <form class="admin-actions" method="post" action="<?= BASE_URL ?>admin/categories/delete" onsubmit="return confirm('<?= $isActive ? 'Ẩn danh mục này?' : 'Hiển thị lại danh mục này?' ?>')" style="margin: 0;">
                            <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                            <button class="admin-btn-sm admin-btn <?= $isActive ? 'warning' : 'primary' ?>" type="submit">
                                <?= $isActive ? 'Ẩn' : 'Hiện' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem 1rem; color: #6b7280;">
                        Chưa có danh mục nào.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php adminEnd(); ?>
