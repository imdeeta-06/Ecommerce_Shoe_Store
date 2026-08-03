<?php
require_once __DIR__ . '/../_helpers.php';
$isEdit = !empty($product);
$variantSizes = ['EU 36', 'EU 37', 'EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44', 'EU 45'];
$variantColors = ['Black', 'Red', 'White'];
$title = $isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm';
adminStart($title, 'products', $flash ?? null);
?>

<form class="admin-panel" method="post" enctype="multipart/form-data" action="<?= $isEdit ? BASE_URL . 'admin/products/edit?id=' . (int)$product['id'] : BASE_URL . 'admin/products/create' ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <section>
            <div class="admin-panel-title">
                <div>Thông tin chung</div>
                <p style="color: var(--admin-text-light); font-size: 0.85rem; font-weight: normal; text-transform: none; margin-top: 0.25rem;">Các thông tin hiển thị trên trang cửa hàng.</p>
            </div>

            <div class="admin-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="admin-field" style="grid-column: span 2;">
                    <label>Tên sản phẩm *</label>
                    <input type="text" name="name" required value="<?= adminE($product['name'] ?? '') ?>" placeholder="Ví dụ: Nike Air Zoom Pegasus">
                </div>
                <div class="admin-field">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= adminE($product['slug'] ?? '') ?>" placeholder="Tự tạo từ tên nếu để trống">
                </div>
                <div class="admin-field">
                    <label>Danh mục</label>
                    <select name="category_id">
                        <option value="">Chưa chọn danh mục</option>
                        <?php foreach ($categories as $category): ?>
                            <?php $categoryInactive = (int)($category['status'] ?? 1) === 0; ?>
                            <option value="<?= (int)$category['id'] ?>" <?= (string)($product['category_id'] ?? '') === (string)$category['id'] ? 'selected' : '' ?>>
                                <?= adminE($category['name'] . ($categoryInactive ? ' (Đang ẩn)' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-field">
                    <label>Giá gốc (VNĐ) *</label>
                    <input type="number" name="base_price" min="0" step="1000" required value="<?= adminE($product['base_price'] ?? 0) ?>">
                </div>
                <div class="admin-field">
                    <label>Phân loại *</label>
                    <input type="text" name="type" required value="<?= adminE($product['type'] ?? '') ?>" placeholder="Ví dụ: Running, Lifestyle">
                </div>
                <div class="admin-field">
                    <label>Giới tính</label>
                    <select name="gender">
                        <option value="">Chưa phân loại</option>
                        <option value="men" <?= ($product['gender'] ?? '') === 'men' ? 'selected' : '' ?>>Nam</option>
                        <option value="women" <?= ($product['gender'] ?? '') === 'women' ? 'selected' : '' ?>>Nữ</option>
                    </select>
                </div>
                <div class="admin-field">
                    <label>Trạng thái hiển thị</label>
                    <select name="status">
                        <option value="1" <?= (string)($product['status'] ?? '1') === '1' ? 'selected' : '' ?>>Đang hiển thị</option>
                        <option value="0" <?= (string)($product['status'] ?? '') === '0' ? 'selected' : '' ?>>Đã ẩn</option>
                    </select>
                </div>
                <div class="admin-field" style="display:flex;align-items:center;gap:.65rem;padding-top:1.65rem;">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?> style="width:18px;height:18px;">
                    <label for="is_featured" style="margin:0;">Đưa vào sản phẩm nổi bật</label>
                </div>
            </div>
        </section>

        <aside style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--admin-border);">
            <div class="admin-panel-title">
                <div>Nội dung & ảnh</div>
                <p style="color: var(--admin-text-light); font-size: 0.85rem; font-weight: normal; text-transform: none; margin-top: 0.25rem;">Ảnh tải lên đầu tiên sẽ là ảnh chính.</p>
            </div>

            <div class="admin-field">
                <label>Ảnh sản phẩm mới</label>
                <div style="border: 2px dashed #d1d5db; border-radius: 6px; padding: 1rem; text-align: center; background: #fff;">
                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.avif" style="border: none; padding: 0; margin-bottom: 0.5rem; background: transparent;">
                    <p style="color: #9ca3af; font-size: 0.8rem;">Định dạng: JPG, PNG, WEBP. Tối đa 2MB.</p>
                </div>
            </div>

            <div class="admin-field" style="margin-top: 1.5rem;">
                <label>Mô tả chi tiết</label>
                <textarea name="description" rows="10" placeholder="Nhập mô tả ngắn gọn về chất liệu, form dáng, công nghệ..."><?= adminE($product['description'] ?? '') ?></textarea>
            </div>
        </aside>
    </div>

    <div style="border-top: 1px solid var(--admin-border); margin: 2rem -1.5rem 0 -1.5rem; padding: 1.5rem 1.5rem 0 1.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
        <a class="admin-btn light" href="<?= BASE_URL ?>admin/products">Hủy bỏ</a>
        <button class="admin-btn primary" type="submit"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo sản phẩm' ?></button>
    </div>
</form>

<?php if ($isEdit): ?>
    <section class="admin-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 1rem;">
            <div>
                <div class="admin-panel-title" style="margin-bottom: 0; border: none; padding: 0;">Thư viện ảnh</div>
                <p style="color: var(--admin-text-light); font-size: 0.9rem; margin-top: 0.25rem;">Quản lý tất cả hình ảnh của sản phẩm.</p>
            </div>
            <span class="admin-badge neutral"><?= count($images) ?> ảnh</span>
        </div>

        <?php if (!empty($images)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1.5rem;">
                <?php foreach ($images as $image): ?>
                    <div style="border: 1px solid var(--admin-border); border-radius: 8px; overflow: hidden; background: #fff; position: relative;">
                        <div style="aspect-ratio: 1; background: #f3f4f6; display: flex; align-items: center; justify-content: center; padding: 1rem;">
                            <img src="<?= adminE(adminImageUrl($image['image_url'])) ?>" alt="Ảnh sản phẩm" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </div>
                        <div style="padding: 1rem; border-top: 1px solid var(--admin-border); display: flex; flex-direction: column; gap: 0.5rem;">
                            <?php if ((int)$image['is_primary'] === 1): ?>
                                <span class="admin-badge success" style="justify-content: center; width: 100%;">Ảnh đại diện</span>
                            <?php else: ?>
                                <form method="post" action="<?= BASE_URL ?>admin/products/images/primary" style="margin: 0;">
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                    <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>">
                                    <button class="admin-btn-sm admin-btn light" type="submit" style="width: 100%;">Làm ảnh chính</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= BASE_URL ?>admin/products/images/delete" onsubmit="return confirm('Xóa ảnh này khỏi sản phẩm?')" style="margin: 0;">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>">
                                <button class="admin-btn-sm admin-btn danger" type="submit" style="width: 100%;">Xóa ảnh</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #6b7280; background: #f9fafb; border-radius: 8px; border: 2px dashed var(--admin-border);">
                Sản phẩm chưa có ảnh. Hãy tải ảnh ở form phía trên.
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 1rem;">
            <div>
                <div class="admin-panel-title" style="margin-bottom: 0; border: none; padding: 0;">Phân loại biến thể</div>
                <p style="color: var(--admin-text-light); font-size: 0.9rem; margin-top: 0.25rem;">Quản lý kích cỡ, màu sắc, tồn kho và chênh lệch giá.</p>
            </div>
            <span class="admin-badge neutral"><?= count($variants) ?> phân loại</span>
        </div>

        <form id="variant-add-form" method="post" action="<?= BASE_URL ?>admin/products/variants/add">
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
        </form>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kích cỡ (Size)</th>
                        <th>Màu sắc</th>
                        <th>Tồn kho</th>
                        <th>Cộng giá (VNĐ)</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variants as $variant): ?>
                        <?php
                        $variantFormId = 'variant-edit-' . (int)$variant['id'];
                        $selectedSize = trim((string)$variant['size']);
                        if (preg_match('/^\d{2}$/', $selectedSize)) {
                            $selectedSize = 'EU ' . $selectedSize;
                        }
                        if (!in_array($selectedSize, $variantSizes, true)) {
                            $selectedSize = 'EU 42';
                        }

                        $legacyColors = [
                            'Đỏ' => 'Red',
                            'Trắng' => 'White',
                            'Đen' => 'Black',
                            'đỏ' => 'Red',
                            'trắng' => 'White',
                            'đen' => 'Black',
                            'red' => 'Red',
                            'white' => 'White',
                            'black' => 'Black'
                        ];
                        $selectedColor = $legacyColors[$variant['color']] ?? $variant['color'];
                        ?>
                        <tr>
                            <td>
                                <select form="<?= $variantFormId ?>" name="size" style="width: 100px;">
                                    <?php foreach ($variantSizes as $size): ?>
                                        <option value="<?= adminE($size) ?>" <?= $selectedSize === $size ? 'selected' : '' ?>><?= adminE($size) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1); background-color: <?= strtolower($selectedColor) === 'white' ? '#f8f9fa' : (strtolower($selectedColor) === 'red' ? '#dc2626' : '#111') ?>;"></div>
                                    <select form="<?= $variantFormId ?>" name="color" style="width: 120px;">
                                        <?php foreach ($variantColors as $color): ?>
                                            <option value="<?= adminE($color) ?>" <?= $selectedColor === $color ? 'selected' : '' ?>><?= adminColorLabel($color) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                            <td><input form="<?= $variantFormId ?>" type="number" min="0" name="stock_quantity" value="<?= (int)$variant['stock_quantity'] ?>" style="width: 100px;"></td>
                            <td><input form="<?= $variantFormId ?>" type="number" min="0" name="price_modifier" step="1000" value="<?= adminE($variant['price_modifier']) ?>" style="width: 120px;"></td>
                            <td>
                                <div class="admin-actions">
                                    <form id="<?= $variantFormId ?>" method="post" action="<?= BASE_URL ?>admin/products/variants/update" style="margin: 0;">
                                        <input type="hidden" name="id" value="<?= (int)$variant['id'] ?>">
                                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                    </form>
                                    <button class="admin-btn-sm admin-btn light" form="<?= $variantFormId ?>" type="submit">Lưu</button>
                                    <form method="post" action="<?= BASE_URL ?>admin/products/variants/delete" onsubmit="return confirm('Xóa phân loại này?')" style="margin: 0;">
                                        <input type="hidden" name="id" value="<?= (int)$variant['id'] ?>">
                                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                        <button class="admin-btn-sm admin-btn danger" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr style="background: #f9fafb;">
                        <td>
                            <select form="variant-add-form" name="size" required style="width: 100px;">
                                <?php foreach ($variantSizes as $size): ?>
                                    <option value="<?= adminE($size) ?>" <?= $size === 'EU 42' ? 'selected' : '' ?>><?= adminE($size) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <select form="variant-add-form" name="color" required style="width: 120px;">
                                    <?php foreach ($variantColors as $color): ?>
                                        <option value="<?= adminE($color) ?>" <?= $color === 'Black' ? 'selected' : '' ?>><?= adminColorLabel($color) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </td>
                        <td><input form="variant-add-form" type="number" min="0" name="stock_quantity" value="0" style="width: 100px;"></td>
                        <td><input form="variant-add-form" type="number" min="0" name="price_modifier" step="1000" value="0" style="width: 120px;"></td>
                        <td><button class="admin-btn-sm admin-btn primary" form="variant-add-form" type="submit">+ Thêm mới</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php adminEnd(); ?>
