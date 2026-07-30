<?php adminStart('Mã giảm giá', 'coupons', $flash ?? null); ?>

<div class="admin-actions" style="justify-content: flex-end; margin-bottom: 1rem;">
    <a class="admin-btn" href="<?= BASE_URL ?>admin/coupons/create">Thêm mã giảm giá</a>
</div>

<div class="admin-panel">
    <?php if (empty($coupons)): ?>
        <p>Chưa có mã giảm giá nào.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Giảm %</th>
                        <th>Giảm tối đa (Cố định)</th>
                        <th>Đơn tối thiểu</th>
                        <th>Lượt dùng</th>
                        <th>Thời hạn</th>
                        <th>Trạng thái</th>
                        <th width="150">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $now = date('Y-m-d H:i:s');
                    foreach ($coupons as $c): 
                        $isExpired = !empty($c['expiry_date']) && $now > $c['expiry_date'];
                        $isNotStarted = !empty($c['start_date']) && $now < $c['start_date'];
                        $isDepleted = !empty($c['usage_limit']) && $c['used_count'] >= $c['usage_limit'];
                        
                        $statusClass = 'ok';
                        $statusText = 'Hoạt động';
                        if ($isExpired) {
                            $statusClass = 'off';
                            $statusText = 'Hết hạn';
                        } elseif ($isNotStarted) {
                            $statusClass = 'off';
                            $statusText = 'Chưa bắt đầu';
                        } elseif ($isDepleted) {
                            $statusClass = 'off';
                            $statusText = 'Hết lượt';
                        }
                    ?>
                        <tr>
                            <td><strong><?= adminE($c['code']) ?></strong></td>
                            <td><?= $c['discount_percent'] ? adminE($c['discount_percent']) . '%' : '-' ?></td>
                            <td><?= $c['max_discount'] ? adminMoney($c['max_discount']) : '-' ?></td>
                            <td><?= adminMoney($c['min_order_amount']) ?></td>
                            <td><?= (int)$c['used_count'] ?> / <?= $c['usage_limit'] ? (int)$c['usage_limit'] : '∞' ?></td>
                            <td>
                                <small>
                                    Từ: <?= $c['start_date'] ? date('d/m/Y H:i', strtotime($c['start_date'])) : 'Bây giờ' ?><br>
                                    Đến: <?= $c['expiry_date'] ? date('d/m/Y H:i', strtotime($c['expiry_date'])) : 'Không' ?>
                                </small>
                            </td>
                            <td><span class="admin-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                            <td>
                                <div class="admin-actions">
                                    <a class="admin-btn light" href="<?= BASE_URL ?>admin/coupons/edit?id=<?= (int)$c['id'] ?>">Sửa</a>
                                    <form method="post" action="<?= BASE_URL ?>admin/coupons/delete" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?')">
                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                        <button class="admin-btn danger" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php adminEnd(); ?>
