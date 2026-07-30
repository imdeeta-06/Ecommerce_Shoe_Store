<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Danh mục', 'categories', $flash ?? null);
?>

<form class="admin-panel admin-grid" method="post" action="<?= BASE_URL ?>admin/categories/create">
    <div class="admin-field">
        <label>Name</label>
        <input type="text" name="name" required>
    </div>
    <div class="admin-field">
        <label>Slug</label>
        <input type="text" name="slug" placeholder="Auto from name">
    </div>
    <div class="admin-field">
        <label>Status</label>
        <select name="status">
            <option value="1">Active</option>
            <option value="0">Hidden</option>
        </select>
    </div>
    <div class="admin-field" style="align-self:end;">
        <button class="admin-btn" type="submit">Add category</button>
    </div>
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $category): ?>
            <?php $isActive = (int)$category['status'] === 1; ?>
            <tr>
                <td><?= (int)$category['id'] ?></td>
                <td><strong><?= adminE($category['name']) ?></strong></td>
                <td><?= adminE($category['slug']) ?></td>
                <td>
                    <span class="admin-badge <?= $isActive ? 'ok' : 'off' ?>">
                        <?= $isActive ? 'Active' : 'Hidden' ?>
                    </span>
                </td>
                <td class="admin-actions">
                    <form method="post" action="<?= BASE_URL ?>admin/categories/delete">
                        <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                        <button class="admin-btn <?= $isActive ? 'danger' : 'ok' ?>" type="submit">
                            <?= $isActive ? 'Hide' : 'Show' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php adminEnd(); ?>
