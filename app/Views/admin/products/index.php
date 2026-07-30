<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Sản phẩm', 'products', $flash ?? null);
?>

<div class="admin-panel">
    <form method="get" action="<?= BASE_URL ?>admin/products" class="admin-grid">
        <div class="admin-field">
            <label>Search</label>
            <input type="text" name="keyword" value="<?= adminE($_GET['keyword'] ?? '') ?>" placeholder="Name or slug">
        </div>
        <div class="admin-field">
            <label>Category</label>
            <select name="category_id">
                <option value="">All</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>" <?= (string)($_GET['category_id'] ?? '') === (string)$category['id'] ? 'selected' : '' ?>>
                        <?= adminE($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field">
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <option value="1" <?= (string)($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (string)($_GET['status'] ?? '') === '0' ? 'selected' : '' ?>>Hidden</option>
            </select>
        </div>
        <div class="admin-field">
            <label>Gender</label>
            <select name="gender">
                <option value="">All</option>
                <option value="men" <?= ($_GET['gender'] ?? '') === 'men' ? 'selected' : '' ?>>Men</option>
                <option value="women" <?= ($_GET['gender'] ?? '') === 'women' ? 'selected' : '' ?>>Women</option>
            </select>
        </div>
        <div class="admin-field" style="align-self:end;">
            <button class="admin-btn" type="submit">Filter</button>
            <a class="admin-btn light" href="<?= BASE_URL ?>admin/products">Reset</a>
        </div>
    </form>
</div>

<div class="admin-actions" style="justify-content: flex-end; margin-bottom: 1rem;">
    <a class="admin-btn" href="<?= BASE_URL ?>admin/products/create">Add product</a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
            <?php $isActive = (int)$product['status'] === 1; ?>
            <?php
            $genderLabel = ($product['gender'] ?? '') === 'women' ? 'Women' : (($product['gender'] ?? '') === 'men' ? 'Men' : '');
            $metaParts = array_filter([
                trim((string)($product['category_name'] ?? '')),
                $genderLabel
            ]);
            ?>
            <tr>
                <td><?= (int)$product['id'] ?></td>
                <td>
                    <?php if (!empty($product['image'])): ?>
                        <img class="admin-thumb" src="<?= adminE(adminImageUrl($product['image'])) ?>" alt="">
                    <?php else: ?>
                        <span class="admin-badge">No image</span>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?= adminE($product['name']) ?></strong><br>
                    <small><?= adminE(implode(' / ', $metaParts)) ?></small>
                </td>
                <td><?= adminE($product['category_name'] ?? '') ?></td>
                <td><?= adminMoney($product['base_price'] ?? 0) ?></td>
                <td>
                    <span class="admin-badge <?= (int)$product['status'] === 1 ? 'ok' : 'off' ?>">
                        <?= (int)$product['status'] === 1 ? 'Active' : 'Hidden' ?>
                    </span>
                </td>
                <td>
                    <div class="admin-actions">
                        <a class="admin-btn light" href="<?= BASE_URL ?>admin/products/edit?id=<?= (int)$product['id'] ?>">Edit</a>
                        <form method="post" action="<?= BASE_URL ?>admin/products/delete" onsubmit="return confirm('<?= $isActive ? 'Hide' : 'Show' ?> this product?')">
                            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                            <input type="hidden" name="status" value="<?= $isActive ? 0 : 1 ?>">
                            <button class="admin-btn <?= $isActive ? 'danger' : 'ok' ?>" type="submit"><?= $isActive ? 'Hide' : 'Show' ?></button>
                        </form>
                        <form method="post" action="<?= BASE_URL ?>admin/products/destroy" onsubmit="return confirm('Delete this product permanently?')">
                            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                            <button class="admin-btn delete" type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <tr><td colspan="7">No products found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php adminEnd(); ?>
