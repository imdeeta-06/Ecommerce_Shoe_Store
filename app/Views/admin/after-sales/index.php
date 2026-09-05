<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Đổi trả, hoàn tiền & bảo hành', 'after-sales', !empty($flash) ? ['type' => ($flash['error'] ?? null) ? 'error' : 'success', 'message' => implode(' ', $flash)] : null);
$typeLabels = ['return' => 'Đổi trả', 'exchange' => 'Đổi sản phẩm', 'warranty' => 'Bảo hành', 'refund' => 'Hoàn tiền'];
$statusLabels = ['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'received' => 'Đã nhận hàng', 'replacement_shipped' => 'Đã gửi hàng thay thế', 'refunded' => 'Đã hoàn tiền', 'completed' => 'Hoàn tất'];
$refundLabels = ['not_requested' => 'Không áp dụng', 'pending' => 'Chờ hoàn', 'completed' => 'Đã hoàn', 'failed' => 'Hoàn lỗi'];
?>
<div class="admin-panel">
    <p style="color:#666;line-height:1.7;margin-bottom:1rem;">Duyệt số lượng thực tế, xác nhận đã nhận lại hàng, chọn hàng có thể nhập kho và nhập mã giao dịch khi hoàn tiền. Hệ thống chỉ cập nhật tồn kho/doanh thu ở đúng bước nghiệp vụ và có kiểm tra idempotent.</p>
    <div class="admin-table-wrapper">
        <table class="admin-table" style="min-width: 800px;">
            <thead>
                <tr>
                    <th style="width: 18%;">Đơn / Khách</th>
                    <th style="width: 18%;">Sản phẩm</th>
                    <th style="width: 15%;">Loại & số lượng</th>
                    <th style="width: 20%;">Lý do / bằng chứng</th>
                    <th style="width: 14%;">Trạng thái</th>
                    <th style="width: 15%;">Xử lý</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <?php $evidence = !empty($request['evidence_images']) ? array_filter(explode('||', $request['evidence_images'])) : []; ?>
                <tr>
                    <td style="white-space:nowrap; vertical-align:top;">
                        <strong style="color:var(--admin-primary); font-size:1rem;"><?= adminE($request['order_code']) ?></strong>
                        <div style="margin-top:0.3rem; font-weight:500;"><?= adminE($request['full_name']) ?></div>
                        <div style="color:#666; font-size:0.85rem;"><?= adminE($request['email']) ?></div>
                    </td>
                    <td style="vertical-align:top; font-weight:500; color:#333;">
                        <?= adminE($request['product_name'] ?? 'Sản phẩm') ?>
                    </td>
                    <td style="vertical-align:top;">
                        <div style="margin-bottom:0.3rem;"><span style="font-weight:600;"><?= adminE($typeLabels[$request['request_type']] ?? $request['request_type']) ?></span></div>
                        <div style="font-size:0.85rem; color:#555;">Yêu cầu: <strong><?= (int)$request['requested_quantity'] ?></strong></div>
                        <div style="font-size:0.85rem; color:#555;">Duyệt: <strong><?= (int)$request['approved_quantity'] ?></strong></div>
                    </td>
                    <td style="vertical-align:top; max-width:260px;">
                        <div style="font-size:0.9rem; margin-bottom:0.4rem; color:#444; line-height:1.4;"><?= nl2br(adminE($request['reason'])) ?></div>
                        <div style="font-size:0.8rem; color:#888;">Hạn: <?= adminE($request['return_deadline'] ?? 'Không xác định') ?></div>
                        <?php if ($evidence): ?>
                            <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-top:0.6rem;">
                                <?php foreach ($evidence as $image): ?>
                                    <a href="<?= adminE(adminImageUrl($image)) ?>" target="_blank" style="display:block; transition:transform 0.2s;">
                                        <img src="<?= adminE(adminImageUrl($image)) ?>" alt="Bằng chứng" style="width:40px; height:40px; object-fit:cover; border:1px solid #ddd; border-radius:4px;">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="vertical-align:top;">
                        <div style="margin-bottom:0.5rem;"><span class="admin-badge <?= $request['status']==='pending'?'warning':($request['status']==='rejected'?'error':'success') ?>"><?= adminE($statusLabels[$request['status']] ?? $request['status']) ?></span></div>
                        <div style="font-size:0.8rem; color:#666; margin-bottom:0.2rem;">Hoàn tiền: <strong style="color:#333;"><?= adminE($refundLabels[$request['refund_status'] ?? 'not_requested'] ?? ($request['refund_status'] ?? '')) ?></strong></div>
                        <div style="font-size:0.9rem; font-weight:600; color:#d97706;"><?= number_format((float)($request['refund_amount'] ?? 0), 0, ',', '.') ?> ₫</div>
                    </td>
                    <td style="vertical-align:top; background:#fafafa; border-left:1px solid #f0f0f0;">
                        <form method="post" action="<?= BASE_URL ?>admin/after-sales/update" style="min-width:220px;">
                            <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">

                            <div style="display:flex; gap:0.5rem; margin-bottom:0.6rem;">
                                <div style="flex:1;">
                                    <div style="font-size:0.75rem; color:#666; margin-bottom:0.2rem; font-weight:600;">Trạng thái</div>
                                    <select name="status" style="width:100%; padding:0.4rem 0.5rem; font-size:0.85rem; border:1px solid #ddd; border-radius:4px;">
                                        <?php foreach ($statusLabels as $status => $label): ?>
                                            <option value="<?= $status ?>" <?= $request['status'] === $status ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="width:70px;">
                                    <div style="font-size:0.75rem; color:#666; margin-bottom:0.2rem; font-weight:600;">SL duyệt</div>
                                    <input type="number" name="approved_quantity" min="1" max="<?= max(1, (int)$request['requested_quantity']) ?>" value="<?= max(1, (int)($request['approved_quantity'] ?: $request['requested_quantity'])) ?>" style="width:100%; padding:0.4rem 0.5rem; font-size:0.85rem; border:1px solid #ddd; border-radius:4px; text-align:center;">
                                </div>
                            </div>

                            <div style="margin-bottom:0.6rem;">
                                <div style="font-size:0.75rem; color:#666; margin-bottom:0.2rem; font-weight:600;">Hàng có thể nhập lại kho</div>
                                <select name="restockable" style="width:100%; padding:0.4rem 0.5rem; font-size:0.85rem; border:1px solid #ddd; border-radius:4px;">
                                    <option value="1" <?= (int)($request['restockable'] ?? 1) === 1 ? 'selected' : '' ?>>Có (Hàng còn bán được)</option>
                                    <option value="0" <?= (int)($request['restockable'] ?? 1) === 0 ? 'selected' : '' ?>>Không (Lỗi/Hỏng)</option>
                                </select>
                            </div>

                            <?php if (in_array($request['request_type'], ['exchange', 'warranty'], true)): ?>
                                <div style="margin-bottom:0.6rem; padding:0.6rem; background:#fff; border:1px solid #eee; border-radius:4px;">
                                    <div style="font-size:0.75rem; color:#8b5cf6; margin-bottom:0.4rem; font-weight:700;">THÔNG TIN ĐỔI/BẢO HÀNH</div>
                                    <select name="replacement_variant_id" style="width:100%; padding:0.4rem 0.5rem; font-size:0.8rem; border:1px solid #ddd; border-radius:4px; margin-bottom:0.4rem;">
                                        <option value="">-- Chọn variant thay thế --</option>
                                        <?php foreach ($replacementVariants as $variant): ?>
                                            <?php if ((int)$variant['product_id'] !== (int)$request['original_product_id']) continue; ?>
                                            <option value="<?= (int)$variant['id'] ?>" <?= (int)($request['replacement_variant_id'] ?? 0) === (int)$variant['id'] ? 'selected' : '' ?>>
                                                <?= adminE($variant['size'] . ' · ' . $variant['color'] . ' (tồn ' . $variant['stock_quantity'] . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <div style="display:flex; gap:0.4rem; margin-bottom:0.4rem;">
                                        <input type="number" name="replacement_quantity" min="1" max="<?= max(1, (int)$request['approved_quantity']) ?>" value="<?= max(1, (int)($request['replacement_quantity'] ?: $request['approved_quantity'])) ?>" placeholder="SL" style="width:60px; padding:0.4rem; font-size:0.8rem; border:1px solid #ddd; border-radius:4px;">
                                        <input name="replacement_shipping_carrier" value="<?= adminE($request['replacement_shipping_carrier'] ?? '') ?>" placeholder="Đơn vị giao hàng" style="flex:1; padding:0.4rem; font-size:0.8rem; border:1px solid #ddd; border-radius:4px;">
                                    </div>
                                    <input name="replacement_tracking_code" value="<?= adminE($request['replacement_tracking_code'] ?? '') ?>" placeholder="Mã vận đơn" style="width:100%; padding:0.4rem; font-size:0.8rem; border:1px solid #ddd; border-radius:4px;">
                                </div>
                            <?php endif; ?>

                            <div style="margin-bottom:0.6rem;">
                                <input name="refund_transaction_code" value="<?= adminE($request['refund_transaction_code'] ?? '') ?>" placeholder="<?= ($request['payment_method']??'')==='paypal'?'PayPal tự tạo mã khi hoàn':'Mã giao dịch hoàn tiền' ?>" <?= ($request['payment_method']??'')==='paypal'?'readonly':'' ?> style="width:100%; padding:0.4rem 0.5rem; font-size:0.85rem; border:1px solid #ddd; border-radius:4px;">
                            </div>

                            <div style="margin-bottom:0.6rem;">
                                <textarea name="resolution_note" rows="2" placeholder="Ghi chú nội bộ..." style="width:100%; padding:0.4rem 0.5rem; font-size:0.85rem; border:1px solid #ddd; border-radius:4px; resize:vertical;"><?= adminE($request['resolution_note'] ?? '') ?></textarea>
                            </div>

                            <button class="admin-btn primary" type="submit" style="width:100%; justify-content:center; padding:0.6rem; font-size:0.85rem; border-radius:4px; box-shadow:none;">Lưu thay đổi</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?><tr><td colspan="6" style="text-align:center;padding:3rem;color:#666;">Chưa có yêu cầu sau bán hàng nào.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php adminEnd(); ?>
