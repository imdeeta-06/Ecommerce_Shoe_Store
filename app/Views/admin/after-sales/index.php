<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Đổi trả, hoàn tiền & bảo hành', 'after-sales', !empty($flash) ? ['type' => ($flash['error'] ?? null) ? 'error' : 'success', 'message' => implode(' ', $flash)] : null);
$typeLabels = ['return' => 'Đổi trả', 'exchange' => 'Đổi sản phẩm', 'warranty' => 'Bảo hành', 'refund' => 'Hoàn tiền'];
$statusLabels = ['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'received' => 'Đã nhận hàng', 'refunded' => 'Đã hoàn tiền', 'completed' => 'Hoàn tất'];
$refundLabels = ['not_requested' => 'Không áp dụng', 'pending' => 'Chờ hoàn', 'completed' => 'Đã hoàn', 'failed' => 'Hoàn lỗi'];
?>
<div class="admin-panel">
    <p style="color:#666;line-height:1.7;margin-bottom:1rem;">Duyệt số lượng thực tế, xác nhận đã nhận lại hàng, chọn hàng có thể nhập kho và nhập mã giao dịch khi hoàn tiền. Hệ thống chỉ cập nhật tồn kho/doanh thu ở đúng bước nghiệp vụ và có kiểm tra idempotent.</p>
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead><tr><th>Đơn / Khách</th><th>Sản phẩm</th><th>Loại & số lượng</th><th>Lý do / bằng chứng</th><th>Trạng thái</th><th>Xử lý</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <?php $evidence = !empty($request['evidence_images']) ? array_filter(explode('||', $request['evidence_images'])) : []; ?>
                <tr>
                    <td><strong><?= adminE($request['order_code']) ?></strong><br><?= adminE($request['full_name']) ?><br><small><?= adminE($request['email']) ?></small></td>
                    <td><?= adminE($request['product_name'] ?? 'Sản phẩm') ?></td>
                    <td><?= adminE($typeLabels[$request['request_type']] ?? $request['request_type']) ?><br>Yêu cầu: <?= (int)$request['requested_quantity'] ?><br>Duyệt: <?= (int)$request['approved_quantity'] ?></td>
                    <td style="max-width:260px;"><?= nl2br(adminE($request['reason'])) ?><br><small>Hạn: <?= adminE($request['return_deadline'] ?? 'Không xác định') ?></small><?php if ($evidence): ?><div style="display:flex;gap:.35rem;flex-wrap:wrap;margin-top:.5rem;"><?php foreach ($evidence as $image): ?><a href="<?= adminE(adminImageUrl($image)) ?>" target="_blank"><img src="<?= adminE(adminImageUrl($image)) ?>" alt="Bằng chứng" style="width:46px;height:46px;object-fit:cover;border:1px solid #ddd;border-radius:4px;"></a><?php endforeach; ?></div><?php endif; ?></td>
                    <td><span class="admin-badge neutral"><?= adminE($statusLabels[$request['status']] ?? $request['status']) ?></span><br><small>Hoàn tiền: <?= adminE($refundLabels[$request['refund_status'] ?? 'not_requested'] ?? ($request['refund_status'] ?? '')) ?></small><br><small><?= number_format((float)($request['refund_amount'] ?? 0), 0, ',', '.') ?> ₫</small></td>
                    <td>
                        <form method="post" action="<?= BASE_URL ?>admin/after-sales/update" style="min-width:250px;">
                            <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                            <label class="admin-field" style="display:block;margin-bottom:.4rem;">Trạng thái<select name="status" style="margin-top:.25rem;"><?php foreach ($statusLabels as $status => $label): ?><option value="<?= $status ?>" <?= $request['status'] === $status ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
                            <label class="admin-field" style="display:block;margin-bottom:.4rem;">Số lượng duyệt<input type="number" name="approved_quantity" min="1" max="<?= max(1, (int)$request['requested_quantity']) ?>" value="<?= max(1, (int)($request['approved_quantity'] ?: $request['requested_quantity'])) ?>"></label>
                            <label class="admin-field" style="display:block;margin-bottom:.4rem;">Hàng có thể nhập lại kho<select name="restockable"><option value="1" <?= (int)($request['restockable'] ?? 1) === 1 ? 'selected' : '' ?>>Có, hàng còn bán được</option><option value="0" <?= (int)($request['restockable'] ?? 1) === 0 ? 'selected' : '' ?>>Không, hàng lỗi/hỏng</option></select></label>
                            <input name="refund_transaction_code" value="<?= adminE($request['refund_transaction_code'] ?? '') ?>" placeholder="Mã giao dịch/biên nhận hoàn tiền">
                            <textarea name="resolution_note" rows="2" placeholder="Ghi chú xử lý..." style="margin-top:.4rem;"><?= adminE($request['resolution_note'] ?? '') ?></textarea>
                            <button class="admin-btn primary" type="submit" style="margin-top:.5rem;">Lưu xử lý</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?><tr><td colspan="6" style="text-align:center;padding:2rem;color:#666;">Chưa có yêu cầu sau bán hàng.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php adminEnd(); ?>
