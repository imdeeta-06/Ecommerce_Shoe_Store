<?php
require_once __DIR__ . '/../_helpers.php';
$variantSizes = ['EU 36', 'EU 37', 'EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44', 'EU 45'];
adminStart('Kho hàng', 'inventory', $flash ?? null);
?>

<?php if (empty($variants)): ?>
    <div class="admin-flash error">Chưa có variant nào. Tạo variant trước rồi mới nhập/xuất kho được.</div>
<?php endif; ?>

<section class="admin-panel">
    <h2>Tạo variant nhanh</h2>
    <form class="admin-grid" method="post" action="<?= BASE_URL ?>admin/inventory/variants/create">
        <div class="admin-field">
            <label>Product</label>
            <select name="product_id" required>
                <option value="">Choose product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int)$product['id'] ?>">
                        <?= adminE($product['name']) ?><?= !empty($product['category_name']) ? ' / ' . adminE($product['category_name']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field">
            <label>Size</label>
            <select name="size" required>
                <?php foreach ($variantSizes as $size): ?>
                    <option value="<?= adminE($size) ?>" <?= $size === 'EU 42' ? 'selected' : '' ?>><?= adminE($size) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field">
            <label>Color</label>
            <select name="color" required>
                <option value="Black" selected>Black</option>
                <option value="Red">Red</option>
                <option value="White">White</option>
            </select>
        </div>
        <div class="admin-field">
            <label>Initial stock</label>
            <input type="number" name="stock_quantity" min="0" value="0">
        </div>
        <div class="admin-field">
            <label>Price modifier</label>
            <input type="number" name="price_modifier" step="1000" value="0">
        </div>
        <div class="admin-field" style="align-self:end;">
            <button class="admin-btn" type="submit">Create variant</button>
        </div>
    </form>
</section>

<form class="admin-panel admin-grid" method="post" action="<?= BASE_URL ?>admin/inventory/update">
    <div class="admin-field">
        <label>Variant</label>
        <select name="variant_id" required <?= empty($variants) ? 'disabled' : '' ?>>
            <option value=""><?= empty($variants) ? 'No variants available' : 'Choose variant' ?></option>
            <?php foreach ($variants as $variant): ?>
                <option value="<?= (int)$variant['id'] ?>">
                    <?= adminE($variant['product_name']) ?> / <?= adminE($variant['size']) ?> / <?= adminE($variant['color']) ?> / stock <?= (int)$variant['stock_quantity'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="admin-field">
        <label>Type</label>
        <select name="change_type">
            <option value="in">Import stock</option>
            <option value="out">Export stock</option>
        </select>
    </div>
    <div class="admin-field">
        <label>Quantity</label>
        <input type="number" name="quantity" min="1" required>
    </div>
    <div class="admin-field">
        <label>Reason</label>
        <input type="text" name="reason" placeholder="Manual update">
    </div>
    <div class="admin-field" style="align-self:end;">
        <button class="admin-btn" type="submit" <?= empty($variants) ? 'disabled' : '' ?>>Update stock</button>
    </div>
</form>

<section class="admin-panel">
    <h2>Inventory logs & current stock</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Size / Color</th>
                <th>Current stock</th>
                <th>Change</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= adminE($log['product_name']) ?></td>
                    <td><?= adminE($log['category_name']) ?></td>
                    <td><?= adminE($log['size']) ?> / <?= adminE($log['color']) ?></td>
                    <td>
                        <span class="admin-badge <?= (int)$log['stock_quantity'] > 0 ? 'ok' : 'off' ?>">
                            <?= (int)$log['stock_quantity'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="admin-badge <?= (int)$log['quantity_changed'] >= 0 ? 'ok' : 'off' ?>">
                            <?= (int)$log['quantity_changed'] ?>
                        </span>
                    </td>
                    <td><?= adminE($log['reason']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6">No inventory logs yet. Add variants from product edit page, then import/export stock here.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php adminEnd(); ?>

