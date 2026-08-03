<?php
$flashMessages = \App\Helpers\SessionHelper::getAllFlash();
$avatar = $user['avatar'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản - PaceUp</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="client-page">
    <h1 class="client-title">Tài khoản của tôi</h1>

    <div class="client-layout-2">
        <!-- Sidebar -->
        <aside>
            <div class="client-avatar-wrapper" style="text-align: center; margin-bottom: 2rem;">
                <?php if ($avatar): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($avatar) ?>" alt="Avatar" class="client-avatar-img" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                <?php else: ?>
                    <div style="width: 150px; height: 150px; border-radius: 50%; background: #f5f5f5; display: grid; place-items: center; font-weight: 700; font-size: 3rem; margin: 0 auto 1rem auto; color: #111;">
                        <?= htmlspecialchars(strtoupper(substr($user['full_name'] ?? 'U', 0, 1))) ?>
                    </div>
                <?php endif; ?>

                <form id="avatarForm" action="<?= BASE_URL ?>account/avatar" method="POST" enctype="multipart/form-data">
                    <label for="avatar_upload" class="client-text-link" style="cursor: pointer; text-decoration: underline; font-size: 0.9rem;">Thay đổi ảnh</label>
                    <input type="file" id="avatar_upload" name="avatar" accept=".jpg,.jpeg,.png,.webp" style="display: none;" onchange="document.getElementById('avatarForm').submit();">
                </form>
            </div>

            <ul class="client-menu" style="list-style: none; padding: 0;">
                <li><a href="<?= BASE_URL ?>account" class="active" style="display: block; padding: 1rem 0; color: #111; text-decoration: none; text-transform: uppercase; font-size: 0.85rem; font-weight: 600; letter-spacing: 1px;">Thông tin & Địa chỉ</a></li>
                <li><a href="<?= BASE_URL ?>logout" style="display: block; padding: 1rem 0; color: #ef4444; text-decoration: none; text-transform: uppercase; font-size: 0.85rem; font-weight: 600; letter-spacing: 1px;">Đăng xuất</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="client-main-content">
            <?php foreach ($flashMessages as $type => $message): ?>
                <div class="client-flash <?= $type === 'error' ? 'error' : 'success' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endforeach; ?>

            <section style="margin-bottom: 4rem;">
                <h2 class="client-section-title">Thông tin cá nhân</h2>

                <form action="<?= BASE_URL ?>account/update" method="POST">
                    <div class="client-form-group">
                        <label class="client-label">Họ và tên *</label>
                        <input type="text" name="full_name" class="client-input" required value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                    </div>

                    <div class="client-form-group">
                        <label class="client-label">Email (Không thể thay đổi)</label>
                        <input type="email" name="email" class="client-input" readonly value="<?= htmlspecialchars($user['email'] ?? '') ?>" style="color: #666; cursor: not-allowed; border-color: #eee;">
                    </div>

                    <div class="client-form-group" style="margin-bottom: 2rem;">
                        <label class="client-label">Số điện thoại</label>
                        <input type="tel" name="phone" class="client-input" maxlength="20" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>

                    <button type="submit" class="client-btn">Lưu thông tin</button>
                </form>
            </section>

            <section>
                <h2 class="client-section-title">Sổ địa chỉ</h2>

                <?php if (empty($addresses)): ?>
                    <p style="color: #666; margin-bottom: 2rem; font-size: 0.95rem;">Bạn chưa có địa chỉ nào lưu trong sổ địa chỉ.</p>
                <?php else: ?>
                    <div style="display: grid; gap: 1.5rem; margin-bottom: 3rem;">
                        <?php foreach ($addresses as $address): ?>
                            <div style="border: 1px solid #eee; padding: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h3 style="font-size: 1rem; margin-bottom: 0.5rem; font-family: var(--font-heading); color: #111;">
                                        <?= htmlspecialchars($address['address_line'] ?? '') ?>
                                        <?php if (!empty($address['is_default'])): ?>
                                            <span style="font-size: 0.7rem; background: #111; color: #fff; padding: 2px 6px; margin-left: 10px; vertical-align: middle; font-family: var(--font-body); letter-spacing: 1px; font-weight: normal;">MẶC ĐỊNH</span>
                                        <?php endif; ?>
                                    </h3>
                                    <p style="margin: 0; color: #666; font-size: 0.95rem; line-height: 1.6;"><?= htmlspecialchars($address['ward_district_city'] ?? '') ?></p>
                                </div>
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <?php if (empty($address['is_default'])): ?>
                                        <form action="<?= BASE_URL ?>account/addresses/default" method="POST" style="margin: 0;">
                                            <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                                            <button type="submit" style="background: none; border: none; color: #111; text-decoration: underline; cursor: pointer; font-size: 0.85rem; padding: 0;">Đặt mặc định</button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="<?= BASE_URL ?>account/addresses/delete" method="POST" style="margin: 0;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa địa chỉ này?');">
                                        <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                                        <button type="submit" style="background: none; border: none; color: #ef4444; text-decoration: underline; cursor: pointer; font-size: 0.85rem; padding: 0;">Xóa</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h3 class="client-section-title" style="font-size: 1.25rem;">Thêm địa chỉ mới</h3>
                <form action="<?= BASE_URL ?>account/addresses/add" method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div class="client-form-group">
                            <label class="client-label">Tên người nhận *</label>
                            <input type="text" name="recipient_name" class="client-input" required>
                        </div>
                        <div class="client-form-group">
                            <label class="client-label">Số điện thoại *</label>
                            <input type="text" name="phone" class="client-input" required>
                        </div>
                    </div>

                    <div class="client-form-group">
                        <label class="client-label">Số nhà, Tên đường *</label>
                        <input type="text" name="address" class="client-input" required>
                    </div>

                    <div class="client-form-group" style="margin-bottom: 2rem;">
                        <label class="client-label">Phường/Xã, Quận/Huyện, Tỉnh/Thành phố *</label>
                        <input type="text" name="city" class="client-input" required>
                    </div>

                    <label style="display: block; margin-bottom: 1.5rem; font-size: 0.9rem; color: #666; cursor: pointer;">
                        <input type="checkbox" name="is_default" value="1" style="margin-right: 0.5rem; cursor: pointer;"> Đặt làm địa chỉ mặc định
                    </label>

                    <button type="submit" class="client-btn">Thêm địa chỉ</button>
                </form>
            </section>

            <section style="margin-top:4rem;">
                <h2 class="client-section-title">Đơn hàng & hậu mãi</h2>
                <?php $profileStatusLabels = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị', 'shipping' => 'Đang giao', 'delivered' => 'Giao thành công', 'completed' => 'Hoàn thành', 'canceled' => 'Đã hủy']; ?>
                <?php if (empty($orders)): ?>
                    <p style="color:#666;">Bạn chưa có đơn hàng.</p>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <article style="border:1px solid #eee;padding:1.25rem;margin-bottom:1rem;">
                            <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;"><strong><?= htmlspecialchars($order['order_code']) ?></strong><span><?= htmlspecialchars($profileStatusLabels[$order['status']] ?? $order['status']) ?> · <?= number_format((float)$order['final_amount'], 0, ',', '.') ?> ₫ <?php if ($order['status'] === 'pending'): ?><form action="<?= BASE_URL ?>account/orders/cancel" method="post" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?')"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><button type="submit" style="border:0;background:none;text-decoration:underline;color:#b91c1c;cursor:pointer;">Hủy đơn</button></form><?php endif; ?></span></div>
                            <div style="font-size:.85rem;color:#666;margin-bottom:.75rem;">Giao hàng: <?= htmlspecialchars($order['shipping_carrier'] ?: 'Chưa có đơn vị') ?> · Mã vận đơn: <?= htmlspecialchars($order['tracking_code'] ?: 'Chưa cập nhật') ?> · Phí ship: <?= number_format((float)($order['shipping_fee'] ?? 0), 0, ',', '.') ?> ₫</div>
                            <?php foreach (($order['items'] ?? []) as $item): ?>
                                <div style="border-top:1px solid #f0f0f0;padding:1rem 0;">
                                    <div><strong><?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm') ?></strong> · Size <?= htmlspecialchars($item['size'] ?? '') ?> · Màu <?= htmlspecialchars($item['color'] ?? '') ?> · SL <?= (int)$item['quantity'] ?></div>
                                    <?php if (in_array($order['status'], ['delivered', 'completed'], true)): ?>
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem;">
                                            <form action="<?= BASE_URL ?>review/store" method="post">
                                                <input type="hidden" name="order_item_id" value="<?= (int)$item['id'] ?>">
                                                <label class="client-label">Đánh giá sau khi nhận hàng</label>
                                                <select name="rating" class="client-input"><option value="5">5 sao</option><option value="4">4 sao</option><option value="3">3 sao</option><option value="2">2 sao</option><option value="1">1 sao</option></select>
                                                <textarea name="comment" class="client-input" rows="2" placeholder="Chia sẻ trải nghiệm..."></textarea>
                                                <button class="client-btn client-btn-sm" type="submit">Gửi đánh giá</button>
                                            </form>
                                            <form action="<?= BASE_URL ?>after-sale/request" method="post" enctype="multipart/form-data">
                                                <input type="hidden" name="MAX_FILE_SIZE" value="10485760">
                                                <input type="hidden" name="order_item_id" value="<?= (int)$item['id'] ?>">
                                                <label class="client-label">Đổi trả / hoàn tiền / bảo hành</label>
                                                <select name="request_type" class="client-input"><option value="return">Đổi trả</option><option value="exchange">Đổi sản phẩm</option><option value="refund">Hoàn tiền</option><option value="warranty">Bảo hành</option></select>
                                                <input type="number" name="requested_quantity" class="client-input" min="1" max="<?= (int)$item['quantity'] ?>" value="<?= (int)$item['quantity'] ?>" required>
                                                <small style="display:block;color:#666;margin-bottom:.5rem;">Đổi trả/hoàn tiền trong 7 ngày từ khi giao hàng; bảo hành trong 180 ngày.</small>
                                                <textarea name="reason" class="client-input" rows="2" required placeholder="Nêu lý do và tình trạng sản phẩm..."></textarea>
                                                <label class="client-label" style="margin-top:.5rem;">Ảnh bằng chứng (tối đa 5 ảnh, mỗi ảnh 2MB)</label>
                                                <input type="file" name="evidence[]" accept=".jpg,.jpeg,.png,.webp,.avif" multiple style="max-width:100%;" formnovalidate>
                                                <button class="client-btn client-btn-sm" type="submit">Gửi yêu cầu</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($afterSaleRequests)): ?>
                    <h3 class="client-section-title" style="font-size:1.1rem;">Yêu cầu sau bán hàng đã gửi</h3>
                    <?php
                    $afterSaleTypeLabels = ['return' => 'Đổi trả', 'exchange' => 'Đổi sản phẩm', 'refund' => 'Hoàn tiền', 'warranty' => 'Bảo hành'];
                    $afterSaleStatusLabels = ['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'received' => 'Đã nhận hàng', 'refunded' => 'Đã hoàn tiền', 'completed' => 'Hoàn tất'];
                    $afterSaleRefundLabels = ['not_requested' => 'Không áp dụng', 'pending' => 'Chờ hoàn', 'completed' => 'Đã hoàn', 'failed' => 'Hoàn lỗi'];
                    foreach ($afterSaleRequests as $request):
                    ?>
                        <p style="border-bottom:1px solid #eee;padding:.6rem 0;"><strong><?= htmlspecialchars($request['order_code']) ?></strong> · <?= htmlspecialchars($afterSaleTypeLabels[$request['request_type']] ?? $request['request_type']) ?> · SL <?= (int)($request['approved_quantity'] ?: $request['requested_quantity']) ?> · <?= htmlspecialchars($afterSaleStatusLabels[$request['status']] ?? $request['status']) ?><?php if (!empty($request['refund_status'])): ?> · Hoàn tiền: <?= htmlspecialchars($afterSaleRefundLabels[$request['refund_status']] ?? $request['refund_status']) ?><?php endif; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
