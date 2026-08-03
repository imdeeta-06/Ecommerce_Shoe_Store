<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Quản lý sản phẩm', 'products', $flash ?? null);
?>

<div class="admin-panel" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 1rem;">
        <div>
            <div class="admin-panel-title" style="margin-bottom: 0; border: none; padding: 0;">Bộ lọc sản phẩm</div>
            <p style="color: var(--admin-text-light); font-size: 0.9rem; margin-top: 0.25rem;">Tìm nhanh theo tên, danh mục hoặc trạng thái.</p>
        </div>
        <a class="admin-btn primary" href="<?= BASE_URL ?>admin/products/create">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
            Thêm sản phẩm
        </a>
    </div>

    <form method="get" action="<?= BASE_URL ?>admin/products" class="admin-grid" style="align-items: end;">
        <div class="admin-field" style="margin-bottom: 0;">
            <label>Tìm kiếm</label>
            <input type="text" name="keyword" value="<?= adminE($_GET['keyword'] ?? '') ?>" placeholder="Tên hoặc slug...">
        </div>
        <div class="admin-field" style="margin-bottom: 0;">
            <label>Danh mục</label>
            <select name="category_id">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>" <?= (string)($_GET['category_id'] ?? '') === (string)$category['id'] ? 'selected' : '' ?>>
                        <?= adminE($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field" style="margin-bottom: 0;">
            <label>Trạng thái</label>
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="1" <?= (string)($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>Đang hiển thị</option>
                <option value="0" <?= (string)($_GET['status'] ?? '') === '0' ? 'selected' : '' ?>>Đã ẩn</option>
            </select>
        </div>
        <div class="admin-field" style="margin-bottom: 0;">
            <label>Giới tính</label>
            <select name="gender">
                <option value="">Tất cả</option>
                <option value="men" <?= ($_GET['gender'] ?? '') === 'men' ? 'selected' : '' ?>>Nam</option>
                <option value="women" <?= ($_GET['gender'] ?? '') === 'women' ? 'selected' : '' ?>>Nữ</option>
            </select>
        </div>
        <div class="admin-actions">
            <button class="admin-btn primary" type="submit">Lọc</button>
            <a class="admin-btn light" href="<?= BASE_URL ?>admin/products">Xóa lọc</a>
        </div>
    </form>
</div>

<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá gốc</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <?php
                $isActive = (int)$product['status'] === 1;
                $gender = $product['gender'] ?? '';
                $metaParts = array_filter([
                    '#' . (int)$product['id'],
                    adminGenderLabel($gender),
                    trim((string)($product['type'] ?? ''))
                ]);
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <?php if (!empty($product['image'])): ?>
                                <img class="admin-thumb" src="<?= adminE(adminImageUrl($product['image'])) ?>" alt="<?= adminE($product['name']) ?>">
                            <?php else: ?>
                                <div class="admin-thumb" style="display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 0.75rem;">N/A</div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight: 600; color: #111;"><?= adminE($product['name']) ?></div>
                                <div style="color: #6b7280; font-size: 0.85rem; margin-top: 0.25rem;"><?= adminE(implode(' · ', $metaParts)) ?></div>
                                <div style="color: #9ca3af; font-size: 0.8rem; margin-top: 0.15rem;">Slug: <?= adminE($product['slug'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-weight: 500; color: #374151;"><?= adminE($product['category_name'] ?? 'Chưa chọn') ?></td>
                    <td style="font-weight: 600; color: #111;"><?= adminMoney($product['base_price'] ?? 0) ?></td>
                    <td>
                        <span class="admin-badge <?= $isActive ? 'success' : 'neutral' ?>">
                            <?= $isActive ? 'Hiển thị' : 'Đã ẩn' ?>
                        </span>
                        <?php if (!empty($product['is_featured'])): ?><span class="admin-badge warning" style="margin-top:.35rem;">Nổi bật</span><?php endif; ?>
                    </td>
                    <td>
                        <div class="admin-actions">
                            <a class="admin-btn-sm admin-btn light" href="<?= BASE_URL ?>admin/products/edit?id=<?= (int)$product['id'] ?>">Sửa</a>
                            <form method="post" action="<?= BASE_URL ?>admin/products/delete" onsubmit="return confirm('<?= $isActive ? 'Ẩn sản phẩm này?' : 'Hiển thị lại sản phẩm này?' ?>')" style="margin:0;">
                                <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="status" value="<?= $isActive ? 0 : 1 ?>">
                                <button class="admin-btn-sm admin-btn <?= $isActive ? 'warning' : 'primary' ?>" type="submit"><?= $isActive ? 'Ẩn' : 'Hiện' ?></button>
                            </form>
                            <form method="post" action="<?= BASE_URL ?>admin/products/destroy" onsubmit="return confirm('Xóa vĩnh viễn sản phẩm này? Nếu sản phẩm đã có đơn hàng, hệ thống sẽ chặn thao tác để giữ lịch sử.')" style="margin:0;">
                                <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                <button class="admin-btn-sm admin-btn danger" type="submit">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem 1rem; color: #6b7280;">
                        Không tìm thấy sản phẩm phù hợp.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php adminEnd(); ?>
