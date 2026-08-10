<?php
require_once __DIR__ . '/../_helpers.php';
$statusLabels = ['pending' => 'Chờ xử lý', 'in_progress' => 'Đang xử lý', 'resolved' => 'Đã phản hồi', 'closed' => 'Đã đóng'];
adminStart('Hỗ trợ khách hàng', 'support', !empty($flash) ? ['type' => ($flash['error'] ?? null) ? 'error' : 'success', 'message' => implode(' ', $flash)] : null);
?>
<section class="admin-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div><h2 class="admin-panel-title">Yêu cầu hỗ trợ</h2><p style="color:#666;">Khách gửi từ trang hỗ trợ sẽ được lưu mã ticket. Hệ thống có thể gửi email xác nhận tự động sau khi cấu hình SMTP.</p></div>
        <form method="post" action="<?= BASE_URL ?>admin/support/send-auto-replies"><button class="admin-btn primary" type="submit">Gửi phản hồi tự động</button></form>
    </div>
    <form method="get" action="<?= BASE_URL ?>admin/support" style="display:flex;gap:.75rem;align-items:end;max-width:420px;margin:1.5rem 0;"><div class="admin-field" style="flex:1;margin:0;"><label>Lọc trạng thái</label><select name="status"><option value="">Tất cả</option><?php foreach ($statusLabels as $key => $label): ?><option value="<?= $key ?>" <?= ($_GET['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div><button class="admin-btn light" type="submit">Lọc</button></form>
    <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Mã</th><th>Khách hàng</th><th>Chủ đề / nội dung</th><th>Ngày gửi</th><th>Phản hồi tự động</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
        <?php foreach ($tickets as $ticket): ?>
            <tr><td><strong><?= adminE($ticket['ticket_code']) ?></strong></td><td><?= adminE($ticket['name']) ?><br><small><?= adminE($ticket['email']) ?><?= !empty($ticket['phone']) ? ' · ' . adminE($ticket['phone']) : '' ?></small></td><td><strong><?= adminE($ticket['subject']) ?></strong><br><span style="color:#666;white-space:pre-line;"><?= adminE(mb_substr($ticket['message'], 0, 180)) ?><?= mb_strlen($ticket['message']) > 180 ? '…' : '' ?></span></td><td><?= adminE($ticket['created_at']) ?></td><td><span class="admin-badge <?= $ticket['auto_reply_status'] === 'sent' ? 'success' : ($ticket['auto_reply_status'] === 'failed' ? 'error' : 'warning') ?>"><?= adminE(['pending' => 'Chờ gửi', 'sent' => 'Đã gửi', 'failed' => 'Gửi lỗi'][$ticket['auto_reply_status']] ?? $ticket['auto_reply_status']) ?></span><?php if (!empty($ticket['auto_reply_last_error'])): ?><br><small style="color:#b91c1c;">Lần thử <?= (int)$ticket['auto_reply_attempts'] ?>/3</small><?php endif; ?></td><td><span class="admin-badge <?= $ticket['status'] === 'resolved' || $ticket['status'] === 'closed' ? 'success' : 'warning' ?>"><?= adminE($statusLabels[$ticket['status']] ?? $ticket['status']) ?></span></td><td><form method="post" action="<?= BASE_URL ?>admin/support/status"><input type="hidden" name="id" value="<?= (int)$ticket['id'] ?>"><select name="status" onchange="this.form.submit()" aria-label="Cập nhật trạng thái"><?php foreach ($statusLabels as $key => $label): ?><option value="<?= $key ?>" <?= $ticket['status'] === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></form></td></tr>
        <?php endforeach; ?>
        <?php if (empty($tickets)): ?><tr><td colspan="7" style="text-align:center;padding:2rem;color:#666;">Chưa có yêu cầu hỗ trợ.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php adminEnd(); ?>
