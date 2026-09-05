<?php
require_once __DIR__ . '/../_helpers.php';
$variantSizes = ['Free Size', 'S', 'M', 'L', '8 mm', '10 mm', '12 mm', '14 mm', '16 mm', '18 mm', '20 mm'];
adminStart('Quản lý Kho hàng', 'inventory', $flash ?? null);
?>

<style nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
.inventory-variants-table {
    min-width: 1040px;
    table-layout: fixed;
}
.inventory-variants-table th:nth-child(1), .inventory-variants-table td:nth-child(1) { width: 28%; }
.inventory-variants-table th:nth-child(2), .inventory-variants-table td:nth-child(2) { width: 10%; }
.inventory-variants-table th:nth-child(3), .inventory-variants-table td:nth-child(3) { width: 12%; }
.inventory-variants-table th:nth-child(4), .inventory-variants-table td:nth-child(4) { width: 10%; }
.inventory-variants-table th:nth-child(5), .inventory-variants-table td:nth-child(5) { width: 10%; }
.inventory-variants-table th:nth-child(6), .inventory-variants-table td:nth-child(6) { width: 10%; }
.inventory-variants-table th:nth-child(7), .inventory-variants-table td:nth-child(7) { width: 12%; }
.inventory-variants-table th:nth-child(8), .inventory-variants-table td:nth-child(8) { width: 120px; text-align:right; }

.inventory-variants-table td:first-child {
    overflow-wrap: anywhere;
}
.inventory-variants-table input,
.inventory-variants-table select {
    min-width: 0;
}
.inventory-variants-table .admin-actions {
    display: flex;
    justify-content: flex-end;
    flex-wrap: nowrap;
    gap: 0.35rem;
}
.inventory-variants-table .admin-actions form {
    margin: 0;
}
.inventory-edit-row td {
    background: #f8fafc;
    padding: 1.5rem !important;
    border-bottom: 2px solid #e2e8f0;
}

.inventory-edit-form, .inventory-stock-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}
.inventory-edit-form label, .inventory-stock-form label {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
    flex: 1 1 140px;
}
.inventory-edit-form input, .inventory-edit-form select,
.inventory-stock-form input, .inventory-stock-form select {
    width: 100%;
    min-width: 0;
    padding: 0.625rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #fff;
    color: #111;
    font: inherit;
    font-size: 0.9rem;
}
.inventory-stock-form label[for="reason"] {
    flex: 2 1 280px;
}
.form-actions-row {
    flex: 1 1 100%;
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    margin-top: 0.5rem;
}
.inventory-stock-form {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px dashed #cbd5e1;
}

.inventory-more-btn {
    display: block;
    margin: 1rem auto 1.25rem;
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
            <tr><th>Sản phẩm</th><th>Size</th><th>Màu</th><th>Tồn vật lý</th><th>Đang giữ</th><th>Có thể bán</th><th>Giá cộng thêm</th><th style="text-align:right;">Thao tác</th></tr>
        </thead>
        <tbody>
            <?php foreach ($variants as $variant): ?>
                <?php $variantFormId = 'inventory-variant-' . (int)$variant['id']; ?>
                <tr class="inventory-row" data-variant-id="<?= (int)$variant['id'] ?>" data-search="<?= adminE(mb_strtolower(($variant['product_name'] ?? '') . ' ' . ($variant['category_name'] ?? '') . ' ' . ($variant['size'] ?? '') . ' ' . ($variant['color'] ?? ''), 'UTF-8')) ?>">
                    <td>
                        <strong style="color:var(--admin-primary); font-size:0.95rem;"><?= adminE($variant['product_name'] ?? 'Sản phẩm đã xóa') ?></strong><br>
                        <small style="color:#666;">SKU: <?= adminE($variant['sku'] ?? 'N/A') ?> · Barcode: <?= adminE($variant['barcode'] ?? 'N/A') ?></small>
                    </td>
                    <td><span style="font-weight:500;"><?= adminE($variant['size']) ?></span></td>
                    <td><?= adminE(adminColorLabel($variant['color'])) ?></td>
                    <?php
                    $physicalStock = (int)($variant['stock_quantity'] ?? 0);
                    $reservedStock = max(0, (int)($variant['reserved_quantity'] ?? 0));
                    $availableStock = max(0, $physicalStock - $reservedStock);
                    ?>
                    <td><?= $physicalStock ?></td>
                    <td><span style="color:#d97706;"><?= $reservedStock ?></span></td>
                    <td><strong style="font-size:1.05rem; color:<?= $availableStock > 0 ? '#16a34a' : '#dc2626' ?>;"><?= $availableStock ?></strong></td>
                    <td><span style="font-weight:500; color:#4b5563;"><?= adminMoney($variant['price_modifier']) ?></span></td>
                    <td>
                        <div class="admin-actions">
                            <button class="admin-btn-sm admin-btn light" type="button" onclick="toggleInventoryEdit(<?= (int)$variant['id'] ?>, this)">Sửa</button>
                            <form method="post" action="<?= BASE_URL ?>admin/inventory/variants/delete" onsubmit="return confirm('Xóa phân loại này?')"><input type="hidden" name="id" value="<?= (int)$variant['id'] ?>"><button class="admin-btn-sm admin-btn danger" type="submit">Xóa</button></form>
                        </div>
                    </td>
                </tr>
                <tr id="inventory-edit-<?= (int)$variant['id'] ?>" class="inventory-edit-row" hidden>
                    <td colspan="8">
                        <div style="font-size:1.05rem; font-weight:700; margin-bottom:1rem; color:#1e293b;">Thông tin thuộc tính phân loại</div>
                        <form method="post" action="<?= BASE_URL ?>admin/inventory/variants/update" class="inventory-edit-form">
                            <input type="hidden" name="id" value="<?= (int)$variant['id'] ?>">
                            <label>Size<input name="size" value="<?= adminE($variant['size']) ?>" required></label>
                            <label>Màu<input name="color" value="<?= adminE($variant['color']) ?>" required></label>
                            <label>SKU<input name="sku" value="<?= adminE($variant['sku'] ?? '') ?>"></label>
                            <label>Barcode<input name="barcode" value="<?= adminE($variant['barcode'] ?? '') ?>"></label>
                            <label>Tồn kho hiện tại<input type="number" name="stock_quantity" min="0" value="<?= (int)$variant['stock_quantity'] ?>" required></label>
                            <label>Giá cộng thêm<input type="number" name="price_modifier" min="0" step="1000" value="<?= (int)($variant['price_modifier'] ?? 0) ?>"></label>
                            <label>Giá vốn<input type="number" name="cost_price" min="0" step="1000" value="<?= (int)($variant['cost_price'] ?? 0) ?>"></label>
                            <label>Khối lượng (g)<input type="number" name="weight_grams" min="1" value="<?= (int)($variant['weight_grams'] ?? 500) ?>"></label>
                            <input type="hidden" name="length_cm" value="<?= adminE($variant['length_cm'] ?? 25) ?>">
                            <input type="hidden" name="width_cm" value="<?= adminE($variant['width_cm'] ?? 20) ?>">
                            <input type="hidden" name="height_cm" value="<?= adminE($variant['height_cm'] ?? 5) ?>">
                            <div class="form-actions-row">
                                <button class="admin-btn light" type="button" onclick="toggleInventoryEdit(<?= (int)$variant['id'] ?>)">Hủy</button>
                                <button class="admin-btn primary" type="submit">Lưu thông tin</button>
                            </div>
                        </form>

                        <div style="font-size:1.05rem; font-weight:700; margin-bottom:1rem; color:#1e293b; margin-top:2rem;">Điều chỉnh tồn kho thủ công</div>
                        <form method="post" action="<?= BASE_URL ?>admin/inventory/update" class="inventory-stock-form" style="margin-top:0; padding-top:0; border-top:none;">
                            <input type="hidden" name="variant_id" value="<?= (int)$variant['id'] ?>">
                            <label>Loại giao dịch<select name="change_type">
                                <option value="in">Nhập kho (+)</option>
                                <option value="out">Xuất kho (-)</option>
                            </select></label>
                            <label>Số lượng<input type="number" name="quantity" min="1" required placeholder="Nhập SL..."></label>
                            <label for="reason">Lý do / Ghi chú<input type="text" name="reason" id="reason" placeholder="Ví dụ: Nhập hàng đợt 1, Khách đổi trả..."></label>
                            <div style="flex: 0 0 auto; margin-bottom: 2px;">
                                <button class="admin-btn primary" type="submit" style="height: 42px; background-color:#16a34a;">Cập nhật tồn kho</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr id="inventoryNoResults" hidden><td colspan="8" style="text-align: center; padding: 3rem 1rem; color: #6b7280;">Không tìm thấy sản phẩm phù hợp.</td></tr>
            <?php if (empty($variants)): ?><tr><td colspan="8" style="text-align:center;padding:2rem;color:#6b7280;">Chưa có phân loại sản phẩm.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <button type="button" class="admin-btn light inventory-more-btn" id="inventoryVariantsMore">Xem thêm</button>
</div>

<script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
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

<script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
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
