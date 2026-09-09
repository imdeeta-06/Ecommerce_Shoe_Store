<?php
require_once __DIR__ . '/../_helpers.php';
adminStart('Khách hàng & tài khoản', 'users', !empty($flash) ? ['type' => isset($flash['error']) ? 'error' : 'success', 'message' => implode(' ', $flash)] : null);
?>
<section class="admin-panel">
    <div class="admin-actions"><a class="admin-btn primary" href="<?= BASE_URL ?>admin/users/create">Thêm quản trị viên</a></div>
    <form method="get" style="margin:1rem 0;display:flex;gap:.75rem;flex-wrap:wrap;"><input name="keyword" value="<?= adminE($keyword) ?>" placeholder="Tìm theo tên hoặc email" aria-label="Tên hoặc email"><button class="admin-btn light">Tìm kiếm</button></form>
    <p><?= (int)$result['total'] ?> tài khoản</p>
    <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th>Họ tên</th><th>Email</th><th>Điện thoại</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
    <?php foreach ($users as $user): ?>
        <tr><td><?= adminE($user['full_name']) ?></td><td><?= adminE($user['email']) ?></td><td><?= adminE($user['phone'] ?? '') ?></td><td><?= $user['role'] === 'admin' ? 'Quản trị viên' : 'Khách hàng' ?></td><td><?= (int)$user['status'] === 1 ? 'Đang hoạt động' : 'Đã khóa' ?></td><td>
        <?php if ($user['role'] !== 'admin'): ?>
            <form method="post" action="<?= BASE_URL ?>admin/users/status"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="status" value="<?= (int)$user['status'] === 1 ? 0 : 1 ?>"><button class="admin-btn light"><?= (int)$user['status'] === 1 ? 'Khóa' : 'Mở khóa' ?></button></form>
        <?php else: ?>Tài khoản quản trị được bảo vệ<?php endif; ?>
        </td></tr>
    <?php endforeach; ?>
    <?php if (!$users): ?><tr><td colspan="6">Không tìm thấy tài khoản.</td></tr><?php endif; ?>
    </tbody></table></div>
    <nav class="admin-actions" aria-label="Phân trang khách hàng" style="margin-top:1rem;">
        <?php if ($page > 1): ?><a class="admin-btn light" href="?<?= adminE(http_build_query(['keyword'=>$keyword,'page'=>$page-1])) ?>">← Trước</a><?php endif; ?>
        <span>Trang <?= (int)$page ?> / <?= (int)$totalPages ?></span>
        <?php if ($page < $totalPages): ?><a class="admin-btn light" href="?<?= adminE(http_build_query(['keyword'=>$keyword,'page'=>$page+1])) ?>">Sau →</a><?php endif; ?>
    </nav>
</section>
<?php adminEnd(); ?>
