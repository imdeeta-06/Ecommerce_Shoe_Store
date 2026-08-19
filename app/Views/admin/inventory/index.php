<?php
require_once __DIR__ . '/../_helpers.php';
$variantSizes = ['EU 36', 'EU 37', 'EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44', 'EU 45'];
adminStart('Quản lý Kho hàng', 'inventory', $flash ?? null);
?>

<?php if (empty($variants)): ?>
    <div class="admin-flash error">Chưa có phân loại hàng (variant) nào. Bạn cần tạo phân loại trước khi có thể cập nhật tồn kho.</div>
<?php endif; ?>

<div class="admin-title" style="margin-bottom: 2rem;">
    <div>
        <h1>Quản lý Kho hàng</h1>
        <p>Thêm phân loại sản phẩm và theo dõi lịch sử xuất/nhập kho.</p>
    </div>
</div>

<div class="admin-form-layout" style="grid-template-columns: 1fr 1fr; margin-bottom: 2rem;">
    <!-- Create Variant Form -->
    <section class="admin-panel" style="margin-bottom: 0;">
        <h2 style="font-size: 1.25rem; font-family: var(--font-heading); margin-bottom: 1.5rem; color: #111; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">Tạo phân loại (Variant) nhanh</h2>
        <form method="post" action="<?= BASE_URL ?>admin/inventory/variants/create">
            <div class="admin-field">
                <label>Sản phẩm</label>
                <select name="product_id" required>
                    <option value="">-- Chọn sản phẩm --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int)$product['id'] ?>">
                            <?= adminE($product['name']) ?><?= !empty($product['category_name']) ? ' / ' . adminE($product['category_name']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="admin-field">
                    <label>Kích cỡ (Size)</label>
                    <select name="size" required>
                        <?php foreach ($variantSizes as $size): ?>
                            <option value="<?= adminE($size) ?>" <?= $size === 'EU 42' ? 'selected' : '' ?>><?= adminE($size) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-field">
                    <label>Màu sắc</label>
                    <select name="color" required>
                        <option value="Black" selected>Đen (Black)</option>
                        <option value="Red">Đỏ (Red)</option>
                        <option value="White">Trắng (White)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="admin-field">
                    <label>Tồn kho ban đầu</label>
                    <input type="number" name="stock_quantity" min="0" value="0">
                </div>
                <div class="admin-field">
                    <label>Giá cộng thêm (₫)</label>
                    <input type="number" name="price_modifier" step="1000" value="0">
                </div>
            </div>

            <div class="admin-field" style="margin-bottom: 0; margin-top: 1rem;">
                <button class="admin-btn primary" type="submit" style="width: 100%;">Tạo phân loại</button>
            </div>
        </form>
    </section>

    <!-- Update Stock Form -->
    <section class="admin-panel" style="margin-bottom: 0;">
        <h2 style="font-size: 1.25rem; font-family: var(--font-heading); margin-bottom: 1.5rem; color: #111; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">Cập nhật số lượng tồn kho</h2>
        <form method="post" action="<?= BASE_URL ?>admin/inventory/update">
            <div class="admin-field">
                <label>Phân loại sản phẩm (Variant)</label>
                <select name="variant_id" required <?= empty($variants) ? 'disabled' : '' ?>>
                    <option value=""><?= empty($variants) ? 'Chưa có phân loại nào' : '-- Chọn phân loại --' ?></option>
                    <?php foreach ($variants as $variant): ?>
                        <option value="<?= (int)$variant['id'] ?>">
                            <?= adminE($variant['product_name']) ?> / <?= adminE($variant['size']) ?> / <?= adminE($variant['color']) ?> / Tồn: <?= (int)$variant['stock_quantity'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="admin-field">
                    <label>Loại giao dịch</label>
                    <select name="change_type">
                        <option value="in">Nhập kho (Thêm)</option>
                        <option value="out">Xuất kho (Trừ)</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label>Số lượng</label>
                    <input type="number" name="quantity" min="1" required>
                </div>
            </div>

            <div class="admin-field">
                <label>Lý do / Ghi chú</label>
                <input type="text" name="reason" placeholder="Ví dụ: Nhập hàng đợt 1, Khách đổi trả...">
            </div>

            <div class="admin-field" style="margin-bottom: 0; margin-top: 1rem;">
                <button class="admin-btn primary" type="submit" style="width: 100%;" <?= empty($variants) ? 'disabled' : '' ?>>Cập nhật tồn kho</button>
            </div>
        </form>
    </section>
</div>

<div class="admin-table-wrapper">
    <h2 style="font-size: 1.25rem; font-family: var(--font-heading); margin-bottom: 1rem; color: #111; padding: 1.5rem 1.5rem 0 1.5rem;">Lịch sử kho hàng & Số lượng hiện tại</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Danh mục</th>
                <th>Size / Màu</th>
                <th>Tồn kho hiện tại</th>
                <th>Biến động</th>
                <th>Lý do</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <?php if ($log['product_name']): ?>
                            <strong style="color: #111;"><?= adminE($log['product_name']) ?></strong>
                        <?php else: ?>
                            <span style="color: #999; font-style: italic;">Sản phẩm đã xoá</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['category_name']): ?>
                            <?= adminE($log['category_name']) ?>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['size'] || $log['color']): ?>
                            <?= adminE($log['size']) ?> / <?= adminE($log['color']) ?>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="admin-badge <?= (int)$log['stock_quantity'] > 0 ? 'ok' : 'off' ?>">
                            <?= (int)$log['stock_quantity'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="admin-badge <?= (int)$log['quantity_changed'] >= 0 ? 'success' : 'danger' ?>">
                            <?= (int)$log['quantity_changed'] >= 0 ? '+' : '' ?><?= (int)$log['quantity_changed'] ?>
                        </span>
                    </td>
                    <td style="color: #555;"><?= adminE($log['reason']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 3rem 1rem; color: #6b7280;">Chưa có lịch sử xuất nhập kho.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php adminEnd(); ?>

