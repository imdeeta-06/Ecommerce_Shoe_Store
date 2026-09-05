<?php
require_once __DIR__ . '/../_helpers.php';
$isEdit = !empty($product);
$variantSizes = ['Free Size', 'S', 'M', 'L', '8 mm', '10 mm', '12 mm', '14 mm', '16 mm', '18 mm', '20 mm'];
$variantColors = ['White', 'Brown', 'Gray', 'Blue'];
$title = $isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm';
$currentTaxCategory = \App\Services\TaxService::normalizeCategory((string)($product['tax_category'] ?? 'standard_reduced'));
$currentTaxRate = \App\Services\TaxService::rateFor($currentTaxCategory, $product['tax_rate'] ?? null);
adminStart($title, 'products', $flash ?? null);
?>

<style nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
.product-form-layout{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:2rem}.product-info-grid{grid-template-columns:1fr 1fr}.product-field-wide{grid-column:span 2}.product-form-actions{border-top:1px solid var(--admin-border);margin:2rem -1.5rem 0;padding:1.5rem 1.5rem 0;display:flex;justify-content:flex-end;gap:1rem}
@media(max-width:850px){.product-form-layout{grid-template-columns:1fr}.product-info-grid{grid-template-columns:1fr}.product-field-wide{grid-column:auto}}
@media(max-width:600px){.product-form-actions{margin:1.5rem -1rem 0;padding:1rem 1rem 0}.product-form-actions .admin-btn{flex:1}.product-form-layout aside{padding:1rem!important}}
</style>

<form class="admin-panel" method="post" enctype="multipart/form-data" action="<?= $isEdit ? BASE_URL . 'admin/products/edit?id=' . (int)$product['id'] : BASE_URL . 'admin/products/create' ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
    <?php endif; ?>

    <div class="product-form-layout">
        <section>
            <div class="admin-panel-title">
                <div>Thông tin chung</div>
                <p style="color: var(--admin-text-light); font-size: 0.85rem; font-weight: normal; text-transform: none; margin-top: 0.25rem;">Các thông tin hiển thị trên trang cửa hàng.</p>
            </div>

            <div class="admin-grid product-info-grid">
                <div class="admin-field product-field-wide">
                    <label>Tên sản phẩm *</label>
                    <input type="text" name="name" required value="<?= adminE($product['name'] ?? '') ?>" placeholder="Ví dụ: Áo tràng Hải Thanh Đài Loan">
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
                    <label>Giá bán hiện tại (VNĐ) *</label>
                    <input type="number" name="base_price" min="0" step="1000" required value="<?= adminE($product['base_price'] ?? 0) ?>">
                </div>
                <div class="admin-field">
                    <label>Giá niêm yết để gạch giá</label>
                    <input type="number" name="old_price" min="0" step="1000" value="<?= adminE($product['old_price'] ?? '') ?>" placeholder="Để trống nếu không giảm giá">
                    <small style="color:var(--admin-text-light);">Chỉ hiển thị khi giá này lớn hơn giá bán hiện tại.</small>
                </div>
                <div class="admin-field">
                    <label>Đơn vị tính</label>
                    <input name="unit_name" maxlength="30" value="<?= adminE($product['unit_name'] ?? 'Cái') ?>" placeholder="Cái, Bộ, Chuỗi...">
                </div>
                <div class="admin-field">
                    <label>Phân loại thuế GTGT *</label>
                    <select name="tax_category" id="taxCategory" required onchange="syncTaxRate()">
                        <?php foreach (($taxCategories ?? []) as $code => $tax): ?>
                            <option value="<?= adminE($code) ?>" data-rate="<?= adminE($tax['rate']) ?>" <?= $currentTaxCategory === $code ? 'selected' : '' ?>><?= adminE($tax['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:var(--admin-text-light);">Phải phân loại theo hồ sơ hàng hóa thực tế; hệ thống không đoán thuế từ tên sản phẩm.</small>
                </div>
                <div class="admin-field">
                    <label>Thuế suất lưu trên sản phẩm (%)</label>
                    <input id="taxRate" name="tax_rate" type="number" min="0" max="100" step="0.01" readonly value="<?= adminE($currentTaxRate) ?>">
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

    <div class="product-form-actions">
        <a class="admin-btn light" href="<?= BASE_URL ?>admin/products">Hủy bỏ</a>
        <button class="admin-btn primary" type="submit"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo sản phẩm' ?></button>
    </div>
</form>

<script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
function syncTaxRate(){const s=document.getElementById('taxCategory');const r=document.getElementById('taxRate');if(s&&r)r.value=s.options[s.selectedIndex].dataset.rate||'0';}
</script>

<?php if ($isEdit): ?>
    <section class="admin-panel">
        <div class="admin-section-head" style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 1rem;">
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
        <div class="admin-section-head" style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 1rem;">
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
                        <th>Ảnh theo màu</th>
                        <th>SKU / Barcode</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <th>Giá vốn</th>
                        <th>KL (g)</th>
                        <th>Cộng giá (VNĐ)</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variants as $variant): ?>
                        <?php
                        $variantFormId = 'variant-edit-' . (int)$variant['id'];
                        $selectedSize = trim((string)$variant['size']);
                        $selectedColor = trim((string)$variant['color']);
                        ?>
                        <tr>
                            <td>
                                <input form="<?= $variantFormId ?>" name="size" value="<?= adminE($selectedSize) ?>" required style="width:100px;">
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 16px; height: 16px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1); background-color: <?= strtolower($selectedColor) === 'white' ? '#f8f9fa' : (strtolower($selectedColor) === 'red' ? '#dc2626' : '#111') ?>;"></div>
                                    <input form="<?= $variantFormId ?>" name="color" value="<?= adminE($selectedColor) ?>" required style="width:120px;">
                                </div>
                            </td>
                            <td>
                                <select form="<?= $variantFormId ?>" name="image_url" style="width:170px;">
                                    <option value="">Dùng ảnh chính</option>
                                    <?php foreach ($images as $imageIndex => $image): ?>
                                        <option value="<?= adminE($image['image_url']) ?>" <?= ($variant['image_url'] ?? '') === $image['image_url'] ? 'selected' : '' ?>>Ảnh <?= $imageIndex + 1 ?><?= !empty($image['is_primary']) ? ' (chính)' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($variant['image_url'])): ?><img src="<?= adminE(adminImageUrl($variant['image_url'])) ?>" alt="Ảnh biến thể" style="display:block;width:52px;height:52px;object-fit:contain;margin-top:6px;border:1px solid #ddd;border-radius:6px;"><?php endif; ?>
                            </td>
                            <td><input form="<?= $variantFormId ?>" name="sku" value="<?= adminE($variant['sku'] ?? '') ?>" placeholder="SKU" style="width:145px;"><br><input form="<?= $variantFormId ?>" name="barcode" value="<?= adminE($variant['barcode'] ?? '') ?>" placeholder="Barcode" style="width:145px;margin-top:4px;"></td>
                            <td><input form="<?= $variantFormId ?>" type="number" min="0" name="stock_quantity" value="<?= (int)$variant['stock_quantity'] ?>" style="width: 100px;"></td>
                            <td><select form="<?= $variantFormId ?>" name="status"><option value="1" <?= !empty($variant['status']) ? 'selected' : '' ?>>Đang bán</option><option value="0" <?= empty($variant['status']) ? 'selected' : '' ?>>Ngừng bán</option></select></td>
                            <td><input form="<?= $variantFormId ?>" type="number" min="0" name="cost_price" step="1000" value="<?= adminE($variant['cost_price'] ?? 0) ?>" style="width:110px;"></td>
                            <td><input form="<?= $variantFormId ?>" type="number" min="1" name="weight_grams" value="<?= (int)($variant['weight_grams'] ?? 500) ?>" style="width:80px;"><input form="<?= $variantFormId ?>" type="hidden" name="length_cm" value="<?= adminE($variant['length_cm'] ?? 25) ?>"><input form="<?= $variantFormId ?>" type="hidden" name="width_cm" value="<?= adminE($variant['width_cm'] ?? 20) ?>"><input form="<?= $variantFormId ?>" type="hidden" name="height_cm" value="<?= adminE($variant['height_cm'] ?? 5) ?>"></td>
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
                            <input form="variant-add-form" name="size" required value="Mặc định" style="width:100px;">
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <input form="variant-add-form" name="color" required value="Mặc định" style="width:120px;">
                            </div>
                        </td>
                        <td><select form="variant-add-form" name="image_url" style="width:170px;"><option value="">Dùng ảnh chính</option><?php foreach ($images as $imageIndex => $image): ?><option value="<?= adminE($image['image_url']) ?>">Ảnh <?= $imageIndex + 1 ?><?= !empty($image['is_primary']) ? ' (chính)' : '' ?></option><?php endforeach; ?></select></td>
                        <td><input form="variant-add-form" name="sku" placeholder="Tự sinh nếu trống" style="width:145px;"><br><input form="variant-add-form" name="barcode" placeholder="Tự sinh nếu trống" style="width:145px;margin-top:4px;"></td>
                        <td><input form="variant-add-form" type="number" min="0" name="stock_quantity" value="0" style="width: 100px;"></td>
                        <td><select form="variant-add-form" name="status"><option value="1">Đang bán</option><option value="0">Ngừng bán</option></select></td>
                        <td><input form="variant-add-form" type="number" min="0" name="cost_price" step="1000" value="0" style="width:110px;"></td>
                        <td><input form="variant-add-form" type="number" min="1" name="weight_grams" value="500" style="width:80px;"><input form="variant-add-form" type="hidden" name="length_cm" value="25"><input form="variant-add-form" type="hidden" name="width_cm" value="20"><input form="variant-add-form" type="hidden" name="height_cm" value="5"></td>
                        <td><input form="variant-add-form" type="number" min="0" name="price_modifier" step="1000" value="0" style="width: 120px;"></td>
                        <td><button class="admin-btn-sm admin-btn primary" form="variant-add-form" type="submit">+ Thêm mới</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php adminEnd(); ?>
