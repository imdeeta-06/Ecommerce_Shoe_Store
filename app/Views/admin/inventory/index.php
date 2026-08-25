<?php
require_once __DIR__ . '/../_helpers.php';
$variantSizes = ['Free Size', 'S', 'M', 'L', '8 mm', '10 mm', '12 mm', '14 mm', '16 mm', '18 mm', '20 mm'];
adminStart('Quản lý Kho hàng', 'inventory', $flash ?? null);
?>

<style>
.inventory-variants-table {
    min-width: 1040px;
    table-layout: fixed;
}
.inventory-variants-table th:nth-child(1),
.inventory-variants-table td:nth-child(1) { width: 42%; }
.inventory-variants-table th:nth-child(2),
.inventory-variants-table td:nth-child(2) { width: 13%; }
.inventory-variants-table th:nth-child(3),
.inventory-variants-table td:nth-child(3) { width: 13%; }
.inventory-variants-table th:nth-child(4),
.inventory-variants-table td:nth-child(4) { width: 12%; }
.inventory-variants-table th:nth-child(5),
.inventory-variants-table td:nth-child(5) { width: 14%; }
.inventory-variants-table th:nth-child(6),
.inventory-variants-table td:nth-child(6) { width: 120px; }
.inventory-variants-table td:first-child {
    overflow-wrap: anywhere;
}
.inventory-variants-table input,
.inventory-variants-table select {
    min-width: 0;
}
.inventory-variants-table td:nth-child(2) select,
.inventory-variants-table td:nth-child(3) select {
    min-width: 96px;
}
.inventory-variants-table td:nth-child(4) input {
    min-width: 100px;
}
.inventory-variants-table td:nth-child(5) input {
    min-width: 128px;
}
.inventory-variants-table .admin-actions {
    flex-wrap: nowrap;
    gap: 0.35rem;
}
.inventory-variants-table .admin-actions form {
    margin: 0;
}
.inventory-edit-row td {
    background: #faf7f4;
    padding: 1rem 1.25rem;
}
.inventory-edit-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(130px, 1fr)) auto auto;
    align-items: end;
    gap: 0.75rem;
}
.inventory-edit-form label {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
}
.inventory-edit-form input,
.inventory-edit-form select {
    width: 100%;
    min-width: 0;
    padding: 0.625rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
    color: #111;
    font: inherit;
    font-weight: 400;
}
.inventory-stock-form {
    display: grid;
    grid-template-columns: 1fr 1fr 2fr auto;
    align-items: end;
    gap: 0.75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--admin-border);
}
.inventory-stock-form label {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
}
.inventory-stock-form input,
.inventory-stock-form select {
    width: 100%;
    min-width: 0;
    padding: 0.625rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
    color: #111;
    font: inherit;
    font-weight: 400;
}
.inventory-more-btn {
    display: block;
    margin: 1rem auto 1.25rem;
}
@media (max-width: 900px) {
    .inventory-edit-form {
        grid-template-columns: repeat(2, minmax(140px, 1fr));
    }
    .inventory-stock-form {
        grid-template-columns: repeat(2, minmax(140px, 1fr));
    }
}
</style>

<?php if (empty($variants)): ?>
    <div class="admin-flash error">Chưa có phân loại hàng (variant) nào. Hãy tạo phân loại trong chi tiết sản phẩm.</div>
<?php endif; ?>

<div class="admin-title" style="margin-bottom: 2rem;">
    <div>
        
        <p>Theo dõi và cập nhật tồn kho của từng phân loại sản phẩm.</p>
    </div>
</div>

<div class="admin-search-bar">
    <label for="inventorySearch">Tìm sản phẩm trong kho</label>
    <input type="search" id="inventorySearch" placeholder="Nhập tên sản phẩm, danh mục, size hoặc màu..." autocomplete="off">
</div>

<div class="admin-table-wrapper" style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.25rem; font-family: var(--font-heading); margin-bottom: 1rem; color: #111; padding: 1.5rem 1.5rem 0 1.5rem;">Danh sách phân loại trong kho</h2>
    <table class="admin-table inventory-variants-table">
        <thead>
            <tr><th>Sản phẩm</th><th>Size</th><th>Màu</th><th>Tồn kho</th><th>Giá cộng thêm</th><th>Thao tác</th></tr>
        </thead>
        <tbody>
            <?php foreach ($variants as $variant): ?>
                <?php $variantFormId = 'inventory-variant-' . (int)$variant['id']; ?>
                <tr class="inventory-row" data-variant-id="<?= (int)$variant['id'] ?>" data-search="<?= adminE(mb_strtolower(($variant['product_name'] ?? '') . ' ' . ($variant['category_name'] ?? '') . ' ' . ($variant['size'] ?? '') . ' ' . ($variant['color'] ?? ''), 'UTF-8')) ?>">
                    <td><?= adminE($variant['product_name'] ?? 'Sản phẩm đã xóa') ?></td>
                    <td><?= adminE($variant['size']) ?></td>
                    <td><?= adminE(adminColorLabel($variant['color'])) ?></td>
                    <td><?= (int)$variant['stock_quantity'] ?></td>
                    <td><?= adminMoney($variant['price_modifier']) ?></td>
                    <td><div class="admin-actions">
                        <button class="admin-btn-sm admin-btn light" type="button" onclick="toggleInventoryEdit(<?= (int)$variant['id'] ?>, this)">Sửa</button>
                        <form method="post" action="<?= BASE_URL ?>admin/inventory/variants/delete" onsubmit="return confirm('Xóa phân loại này?')"><input type="hidden" name="id" value="<?= (int)$variant['id'] ?>"><button class="admin-btn-sm admin-btn danger" type="submit">Xóa</button></form>
                    </div></td>
                </tr>
                <tr id="inventory-edit-<?= (int)$variant['id'] ?>" class="inventory-edit-row" hidden>
                    <td colspan="6">
                        <form method="post" action="<?= BASE_URL ?>admin/inventory/variants/update" class="inventory-edit-form">
                            <input type="hidden" name="id" value="<?= (int)$variant['id'] ?>">
                            <label>Size<select name="size">
                                <?php foreach ($variantSizes as $size): ?><option value="<?= adminE($size) ?>" <?= trim((string)$variant['size']) === $size ? 'selected' : '' ?>><?= adminE($size) ?></option><?php endforeach; ?>
                            </select></label>
                            <label>Màu<select name="color">
                                <?php foreach (['White', 'Brown', 'Gray', 'Blue'] as $color): ?><option value="<?= $color ?>" <?= $variant['color'] === $color ? 'selected' : '' ?>><?= adminE(adminColorLabel($color)) ?></option><?php endforeach; ?>
                            </select></label>
                            <label>Tồn kho hiện tại<input type="number" name="stock_quantity" min="0" value="<?= (int)$variant['stock_quantity'] ?>" required></label>
                            <label>Giá cộng thêm<input type="number" name="price_modifier" min="0" step="1000" value="<?= adminE($variant['price_modifier']) ?>"></label>
                            <button class="admin-btn primary" type="submit">Lưu thay đổi</button>
                            <button class="admin-btn light" type="button" onclick="toggleInventoryEdit(<?= (int)$variant['id'] ?>)">Hủy</button>
                        </form>
                        <form method="post" action="<?= BASE_URL ?>admin/inventory/update" class="inventory-stock-form">
                            <input type="hidden" name="variant_id" value="<?= (int)$variant['id'] ?>">
                            <label>Loại giao dịch<select name="change_type">
                                <option value="in">Nhập kho (Thêm)</option>
                                <option value="out">Xuất kho (Trừ)</option>
                            </select></label>
                            <label>Số lượng<input type="number" name="quantity" min="1" required></label>
                            <label>Lý do / Ghi chú<input type="text" name="reason" placeholder="Ví dụ: Nhập hàng đợt 1, Khách đổi trả..."></label>
                            <button class="admin-btn primary" type="submit">Cập nhật tồn kho</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="inventoryNoResults" hidden><td colspan="6" style="text-align: center; padding: 3rem 1rem; color: #6b7280;">Không tìm thấy sản phẩm phù hợp.</td></tr>
            <?php if (empty($variants)): ?><tr><td colspan="6" style="text-align:center;padding:2rem;color:#6b7280;">Chưa có phân loại sản phẩm.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <button type="button" class="admin-btn light inventory-more-btn" id="inventoryVariantsMore">Xem thêm</button>
</div>

<script>
document.getElementById('inventorySearch')?.addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    const rows = document.querySelectorAll('.inventory-row');
    let visible = 0;
    rows.forEach((row, index) => {
        const match = !query || row.dataset.search.includes(query);
        const withinLimit = index < inventoryVariantLimit;
        const shouldShow = match && (query || withinLimit);
        row.hidden = !shouldShow;
        const editRow = document.getElementById('inventory-edit-' + row.dataset.variantId);
        if (!shouldShow && editRow) editRow.hidden = true;
        if (shouldShow) visible++;
    });
    const empty = document.getElementById('inventoryNoResults');
    if (empty) empty.hidden = visible !== 0 || rows.length === 0;
    updateMoreButton('inventoryVariantsMore', rows.length, inventoryVariantLimit, Boolean(query));
});

let inventoryVariantLimit = 10;

function updateMoreButton(buttonId, total, limit, hasSearch) {
    const button = document.getElementById(buttonId);
    if (!button) return;
    button.hidden = hasSearch || total <= limit;
    button.textContent = limit >= total ? 'Đã hiển thị tất cả' : 'Xem thêm';
    button.disabled = limit >= total;
}

document.getElementById('inventoryVariantsMore')?.addEventListener('click', function () {
    inventoryVariantLimit += 10;
    document.getElementById('inventorySearch')?.dispatchEvent(new Event('input'));
});

document.querySelectorAll('.inventory-row').forEach((row, index) => {
    if (index >= inventoryVariantLimit) row.hidden = true;
});
document.getElementById('inventorySearch')?.dispatchEvent(new Event('input'));

function toggleInventoryEdit(variantId, button) {
    const editRow = document.getElementById('inventory-edit-' + variantId);
    if (!editRow) return;
    const willOpen = editRow.hidden;
    document.querySelectorAll('.inventory-edit-row').forEach(row => row.hidden = true);
    document.querySelectorAll('.inventory-row .admin-btn.light').forEach(editButton => editButton.textContent = 'Sửa');
    editRow.hidden = !willOpen;
    if (button) button.textContent = willOpen ? 'Đóng' : 'Sửa';
}
</script>

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
                <tr class="inventory-log-row">
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
    <button type="button" class="admin-btn light inventory-more-btn" id="inventoryLogsMore">Xem thêm</button>
</div>

<script>
let inventoryLogLimit = 10;
const inventoryLogRows = document.querySelectorAll('.inventory-log-row');

function renderInventoryLogs() {
    inventoryLogRows.forEach((row, index) => row.hidden = index >= inventoryLogLimit);
    updateMoreButton('inventoryLogsMore', inventoryLogRows.length, inventoryLogLimit, false);
}

document.getElementById('inventoryLogsMore')?.addEventListener('click', function () {
    inventoryLogLimit += 10;
    renderInventoryLogs();
});

renderInventoryLogs();
</script>

<?php adminEnd(); ?>

