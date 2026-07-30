<?php
require_once __DIR__ . '/../_helpers.php';
$isEdit = !empty($product);
$variantSizes = ['EU 36', 'EU 37', 'EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44', 'EU 45'];
adminStart($isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm', 'products', $flash ?? null);
?>

<form class="admin-panel" method="post" enctype="multipart/form-data" action="<?= $isEdit ? BASE_URL . 'admin/products/edit?id=' . (int)$product['id'] : BASE_URL . 'admin/products/create' ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
    <?php endif; ?>

    <div class="admin-grid">
        <div class="admin-field">
            <label>Name</label>
            <input type="text" name="name" required value="<?= adminE($product['name'] ?? '') ?>">
        </div>
        <div class="admin-field">
            <label>Slug</label>
            <input type="text" name="slug" value="<?= adminE($product['slug'] ?? '') ?>" placeholder="Auto from name">
        </div>
        <div class="admin-field">
            <label>Category</label>
            <select name="category_id">
                <option value="">None</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>" <?= (string)($product['category_id'] ?? '') === (string)$category['id'] ? 'selected' : '' ?>>
                        <?= adminE($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field">
            <label>Base price</label>
            <input type="number" name="base_price" min="0" step="1000" value="<?= adminE($product['base_price'] ?? 0) ?>">
        </div>
        <div class="admin-field">
            <label>Type</label>
            <input type="text" name="type" value="<?= adminE($product['type'] ?? '') ?>" placeholder="Running shoes men">
        </div>
        <div class="admin-field">
            <label>Gender</label>
            <select name="gender">
                <option value="">None</option>
                <option value="men" <?= ($product['gender'] ?? '') === 'men' ? 'selected' : '' ?>>Men</option>
                <option value="women" <?= ($product['gender'] ?? '') === 'women' ? 'selected' : '' ?>>Women</option>
            </select>
        </div>
        <div class="admin-field">
            <label>Status</label>
            <select name="status">
                <option value="1" <?= (string)($product['status'] ?? '1') === '1' ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (string)($product['status'] ?? '') === '0' ? 'selected' : '' ?>>Hidden</option>
            </select>
        </div>
        <div class="admin-field">
            <label>Upload image</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif">
            <small style="display:block; margin-top:.35rem; color:#666;">Accepted: JPG, PNG, WEBP, AVIF. Max 2MB.</small>
        </div>
    </div>

    <div class="admin-field" style="margin-top: .75rem;">
        <label>Description</label>
        <textarea name="description" rows="5"><?= adminE($product['description'] ?? '') ?></textarea>
    </div>

    <div class="admin-actions" style="justify-content: flex-end; margin-top: 1rem;">
        <a class="admin-btn light" href="<?= BASE_URL ?>admin/products">Back</a>
        <button class="admin-btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
    </div>
</form>

<?php if ($isEdit): ?>
    <section class="admin-panel">
        <h2>Images</h2>
        <div class="admin-actions">
            <?php foreach ($images as $image): ?>
                <div style="border:1px solid #eee; border-radius:8px; padding:.75rem; background:#fafafa;">
                    <img class="admin-thumb" src="<?= adminE(adminImageUrl($image['image_url'])) ?>" alt="">
                    <div class="admin-actions" style="margin-top:.5rem;">
                        <?php if ((int)$image['is_primary'] === 1): ?>
                            <span class="admin-badge ok">Primary</span>
                        <?php else: ?>
                            <form method="post" action="<?= BASE_URL ?>admin/products/images/primary">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>">
                                <button class="admin-btn light" type="submit">Set primary</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= BASE_URL ?>admin/products/images/delete" onsubmit="return confirm('Delete this image?')">
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>">
                            <button class="admin-btn danger" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($images)): ?>
                <p>No images yet. Upload one in the form above.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="admin-panel">
        <h2>Variants</h2>
        <form id="variant-add-form" method="post" action="<?= BASE_URL ?>admin/products/variants/add ?>">
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
        </form>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Color</th>
                    <th>Stock</th>
                    <th>Price modifier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($variants as $variant): ?>
                    <?php $variantFormId = 'variant-edit-' . (int)$variant['id']; ?>
                    <?php
                    $selectedSize = $variant['size'];
                    if (preg_match('/^\d{2}$/', $selectedSize)) {
                        $selectedSize = 'EU ' . $selectedSize;
                    }
                    if (!in_array($selectedSize, $variantSizes, true)) {
                        $selectedSize = 'EU 42';
                    }
                    $selectedColor = $variant['color'];
                    $legacyColors = ['Đỏ' => 'Red', 'Trắng' => 'White', 'Đen' => 'Black', 'đỏ' => 'Red', 'trắng' => 'White', 'đen' => 'Black', 'red' => 'Red', 'white' => 'White', 'black' => 'Black'];
                    if (isset($legacyColors[$selectedColor])) {
                        $selectedColor = $legacyColors[$selectedColor];
                    }
                    ?>
                    <tr>
                        <td>
                            <select form="<?= $variantFormId ?>" name="size">
                                <?php foreach ($variantSizes as $size): ?>
                                    <option value="<?= adminE($size) ?>" <?= $selectedSize === $size ? 'selected' : '' ?>><?= adminE($size) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                            <td>
                                <select form="<?= $variantFormId ?>" name="color">
                                    <?php foreach (['Black', 'Red', 'White'] as $color): ?>
                                        <option value="<?= adminE($color) ?>" <?= $selectedColor === $color ? 'selected' : '' ?>><?= adminE($color) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        <td><input form="<?= $variantFormId ?>" type="number" name="stock_quantity" value="<?= (int)$variant['stock_quantity'] ?>"></td>
                        <td><input form="<?= $variantFormId ?>" type="number" name="price_modifier" step="1000" value="<?= adminE($variant['price_modifier']) ?>"></td>
                        <td class="admin-actions">
                            <form id="<?= $variantFormId ?>" method="post" action="<?= BASE_URL ?>admin/products/variants/update">
                                <input type="hidden" name="id" value="<?= (int)$variant['id'] ?>">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            </form>
                            <button class="admin-btn light" form="<?= $variantFormId ?>" type="submit">Save</button>
                            <form method="post" action="<?= BASE_URL ?>admin/products/variants/delete" onsubmit="return confirm('Delete this variant?')">
                                <input type="hidden" name="id" value="<?= (int)$variant['id'] ?>">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <button class="admin-btn danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td>
                        <select form="variant-add-form" name="size" required>
                            <?php foreach ($variantSizes as $size): ?>
                                <option value="<?= adminE($size) ?>" <?= $size === 'EU 42' ? 'selected' : '' ?>><?= adminE($size) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select form="variant-add-form" name="color" required>
                            <option value="Black" selected>Black</option>
                            <option value="Red">Red</option>
                            <option value="White">White</option>
                        </select>
                    </td>
                    <td><input form="variant-add-form" type="number" name="stock_quantity" value="0"></td>
                    <td><input form="variant-add-form" type="number" name="price_modifier" step="1000" value="0"></td>
                    <td><button class="admin-btn" form="variant-add-form" type="submit">Add variant</button></td>
                </tr>
            </tbody>
        </table>
    </section>
<?php endif; ?>

<?php adminEnd(); ?>

