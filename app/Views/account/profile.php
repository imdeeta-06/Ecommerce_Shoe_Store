<?php
$flashMessages = \App\Helpers\SessionHelper::getAllFlash();
$avatar = $user['avatar'] ?? '';
$hasLocalPassword = isset($user['password']) && is_string($user['password']) && $user['password'] !== '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản - Liên Hoa</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>assets/images/lien-hoa-favicon.svg?v=3">
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

            <section style="margin-bottom: 4rem;">
                <h2 class="client-section-title"><?= $hasLocalPassword ? 'Đổi mật khẩu' : 'Tạo mật khẩu đăng nhập' ?></h2>
                <p style="color:#666;line-height:1.6;margin:-.5rem 0 2rem;max-width:720px;">
                    <?php if ($hasLocalPassword): ?>
                        Để bảo vệ tài khoản, bạn cần xác nhận mật khẩu hiện tại trước khi đặt mật khẩu mới.
                    <?php else: ?>
                        Tài khoản của bạn chưa có mật khẩu riêng. Hãy tạo mật khẩu để có thể đăng nhập bằng email ngoài phương thức Google.
                    <?php endif; ?>
                </p>

                <form action="<?= BASE_URL ?>change-password" method="POST" style="max-width:720px;">
                    <?= \App\Helpers\SessionHelper::csrfField() ?>

                    <?php if ($hasLocalPassword): ?>
                        <div class="client-form-group">
                            <label for="current_password" class="client-label">Mật khẩu hiện tại *</label>
                            <input type="password" id="current_password" name="current_password" class="client-input" required autocomplete="current-password" maxlength="72">
                        </div>
                    <?php endif; ?>

                    <div class="client-form-group">
                        <label for="new_password" class="client-label">Mật khẩu mới *</label>
                        <input type="password" id="new_password" name="new_password" class="client-input" required minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="password_help">
                        <small id="password_help" style="display:block;color:#666;margin-top:.5rem;line-height:1.5;">Từ 8 đến 72 ký tự và không được trùng với mật khẩu hiện tại.</small>
                    </div>

                    <div class="client-form-group" style="margin-bottom:2rem;">
                        <label for="confirm_new_password" class="client-label">Nhập lại mật khẩu mới *</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" class="client-input" required minlength="8" maxlength="72" autocomplete="new-password">
                    </div>

                    <button type="submit" class="client-btn"><?= $hasLocalPassword ? 'Cập nhật mật khẩu' : 'Tạo mật khẩu' ?></button>
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
                <h2 class="client-section-title">Yêu cầu hỗ trợ của tôi</h2>
                <?php $ticketStatusLabels=['pending'=>'Chờ tiếp nhận','in_progress'=>'Đang xử lý','resolved'=>'Đã giải quyết','closed'=>'Đã đóng']; ?>
                <?php if (empty($supportTickets)): ?><p style="color:#666;">Bạn chưa gửi yêu cầu hỗ trợ. <a href="<?= BASE_URL ?>support">Gửi yêu cầu mới</a>.</p><?php else: ?>
                    <div style="display:grid;gap:1rem;"><?php foreach($supportTickets as $ticket): ?><article style="border:1px solid #eee;padding:1rem 1.25rem;"><div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;"><strong><?= htmlspecialchars($ticket['ticket_code']) ?> — <?= htmlspecialchars($ticket['subject']) ?></strong><span><?= htmlspecialchars($ticketStatusLabels[$ticket['status']]??$ticket['status']) ?></span></div><p style="margin:.6rem 0;color:#555;"><?= nl2br(htmlspecialchars(mb_substr($ticket['message'],0,300))) ?></p><small>Cập nhật: <?= htmlspecialchars($ticket['updated_at']) ?></small></article><?php endforeach; ?></div>
                <?php endif; ?>
            </section>
            <?php if (!empty($customerInvoices)): ?><section style="margin-top:4rem;"><h2 class="client-section-title">Hóa đơn bán hàng</h2><?php foreach($customerInvoices as $invoice):?><p style="border-bottom:1px solid #eee;padding:.7rem 0;"><a target="_blank" rel="noopener" href="<?= BASE_URL ?>invoice/view?id=<?= (int)$invoice['id'] ?>"><?= htmlspecialchars($invoice['invoice_series'].'-'.str_pad($invoice['invoice_number'],7,'0',STR_PAD_LEFT)) ?></a> · Đơn <?= htmlspecialchars($invoice['order_code']) ?> · <?= htmlspecialchars($invoice['status']) ?> · <?= number_format($invoice['total_amount'],0,',','.') ?> ₫</p><?php endforeach;?></section><?php endif; ?>

            <section style="margin-top:4rem;">
                <h2 class="client-section-title">Đơn hàng & hậu mãi</h2>
                <?php $profileStatusLabels = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị', 'shipping' => 'Đang giao', 'delivered' => 'Giao thành công', 'completed' => 'Hoàn thành', 'canceled' => 'Đã hủy']; ?>
                <?php if (empty($orders)): ?>
                    <p style="color:#666;">Bạn chưa có đơn hàng.</p>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <article style="border: 1px solid var(--border-color, #e0e0e0); border-radius: 8px; margin-bottom: 2rem; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden;">
                            <!-- Order Header -->
                            <div style="background: var(--primary-light, #fcfaf5); padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-color, #e0e0e0);">
                                <div>
                                    <strong style="font-size: 1.1rem; color: var(--primary-dark, #333); letter-spacing: 0.5px;"><?= htmlspecialchars($order['order_code']) ?></strong>
                                    <div style="font-size: 0.85rem; color: #666; margin-top: 0.4rem; line-height: 1.5;">
                                        Giao hàng: <?= htmlspecialchars($order['shipping_carrier'] ?: 'Chưa có đơn vị') ?> · Mã vận đơn: <?= htmlspecialchars($order['tracking_code'] ?: 'Chưa cập nhật') ?><br>
                                        Phí ship: <?= number_format((float)($order['shipping_fee'] ?? 0), 0, ',', '.') ?> ₫ · <a href="<?= BASE_URL ?>order/receipt?order_id=<?= (int)$order['id'] ?>" target="_blank" rel="noopener" style="color: var(--primary-color); text-decoration: underline;">In biên lai</a>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary-dark, #333);"><?= number_format((float)$order['final_amount'], 0, ',', '.') ?> ₫</div>
                                    <div style="margin-top: 0.3rem;">
                                        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 1px; background: <?= $order['status'] === 'completed' ? '#e8f5e9; color: #2e7d32;' : '#f0f0f0; color: #555;' ?>"><?= htmlspecialchars($profileStatusLabels[$order['status']] ?? $order['status']) ?></span>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <form action="<?= BASE_URL ?>account/orders/cancel" method="post" style="display:inline; margin-left: 10px;" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
                                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                                <button type="submit" style="border:0; background:none; text-decoration:underline; color:#b91c1c; cursor:pointer; font-size: 0.85rem;">Hủy đơn</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div style="padding: 1.5rem;">
                                <?php foreach (($order['items'] ?? []) as $item): ?>
                                    <div style="border: 1px solid var(--border-color, #f0f0f0); border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem; background: #faf9f7;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #ddd; padding-bottom: 1rem; margin-bottom: 1rem;">
                                            <div>
                                                <strong style="font-size: 1.05rem; color: var(--primary-dark, #333);"><?= htmlspecialchars($item['product_name'] ?? 'Sản phẩm') ?></strong>
                                                <div style="color: #666; font-size: 0.9rem; margin-top: 0.2rem;">
                                                    Phân loại: <?= htmlspecialchars($item['size'] ?? '') ?> - <?= htmlspecialchars($item['color'] ?? '') ?>
                                                </div>
                                            </div>
                                            <div style="font-weight: 600; color: var(--primary-color);">x<?= (int)$item['quantity'] ?></div>
                                        </div>

                                        <?php if (in_array($order['status'], ['delivered', 'completed'], true)): ?>
                                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                                <!-- Đánh giá -->
                                                <form action="<?= BASE_URL ?>review/store" method="post" style="background: #fff; padding: 1.25rem; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                                                    <input type="hidden" name="order_item_id" value="<?= (int)$item['id'] ?>">
                                                    <h4 style="font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; color: var(--primary-dark); margin-bottom: 1rem; border-bottom: 2px solid var(--primary-light); display: inline-block; padding-bottom: 0.3rem;">Đánh giá sản phẩm</h4>

                                                    <div class="client-form-group star-rating-widget" style="margin-bottom: 0.8rem; display: flex; align-items: center; gap: 0.5rem; -webkit-tap-highlight-color: transparent;">
                                                        <input type="hidden" name="rating" value="5" class="rating-value-input">
                                                        <div class="stars-group" style="display: flex; cursor: pointer; color: #f59e0b;">
                                                            <svg class="star-icon" data-val="1" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                                            <svg class="star-icon" data-val="2" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                                            <svg class="star-icon" data-val="3" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                                            <svg class="star-icon" data-val="4" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                                            <svg class="star-icon" data-val="5" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                                        </div>
                                                        <span class="rating-text" style="font-size: 0.85rem; color: #666; font-style: italic;">(Rất hài lòng)</span>
                                                    </div>
                                                    <div class="client-form-group" style="margin-bottom: 1rem;">
                                                        <textarea name="comment" class="client-input" rows="2" placeholder="Chia sẻ trải nghiệm thanh tịnh của bạn..." style="padding: 0.6rem; font-size: 0.9rem; resize: vertical;"></textarea>
                                                    </div>
                                                    <button class="client-btn" type="submit" style="width: 100%; padding: 0.6rem; font-size: 0.85rem;">Gửi đánh giá</button>
                                                </form>

                                                <!-- Đổi trả -->
                                                <form action="<?= BASE_URL ?>after-sale/request" method="post" enctype="multipart/form-data" style="background: #fff; padding: 1.25rem; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                                                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760">
                                                    <input type="hidden" name="order_item_id" value="<?= (int)$item['id'] ?>">
                                                    <h4 style="font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; color: var(--primary-dark); margin-bottom: 1rem; border-bottom: 2px solid var(--primary-light); display: inline-block; padding-bottom: 0.3rem;">Hỗ trợ sau mua</h4>

                                                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.8rem;">
                                                        <select name="request_type" class="client-input" style="flex: 2; padding: 0.5rem; font-size: 0.9rem;">
                                                            <option value="return">Đổi trả</option>
                                                            <option value="exchange">Đổi sản phẩm</option>
                                                            <option value="refund">Hoàn tiền</option>
                                                            <option value="warranty">Bảo hành</option>
                                                        </select>
                                                        <input type="number" name="requested_quantity" class="client-input" min="1" max="<?= (int)$item['quantity'] ?>" value="<?= (int)$item['quantity'] ?>" required style="flex: 1; padding: 0.5rem; font-size: 0.9rem;" title="Số lượng">
                                                    </div>
                                                    <textarea name="reason" class="client-input" rows="2" required placeholder="Nêu rõ tình trạng sản phẩm..." style="margin-bottom: 0.8rem; padding: 0.6rem; font-size: 0.9rem; resize: vertical;"></textarea>
                                                    <div style="margin-bottom: 1rem;">
                                                        <label class="client-label" style="font-size: 0.8rem; color: #666; margin-bottom: 0.3rem;">Ảnh minh chứng (Tối đa 5 ảnh)</label>
                                                        <input type="file" name="evidence[]" accept=".jpg,.jpeg,.png,.webp,.avif" multiple style="max-width:100%; font-size: 0.8rem;" formnovalidate>
                                                    </div>
                                                    <button class="client-btn" type="submit" style="width: 100%; padding: 0.6rem; font-size: 0.85rem; background: var(--text-muted, #555);">Gửi yêu cầu hỗ trợ</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($afterSaleRequests)): ?>
                    <h3 class="client-section-title" style="font-size:1.1rem;">Yêu cầu sau bán hàng đã gửi</h3>
                    <?php
                    $afterSaleTypeLabels = ['return' => 'Đổi trả', 'exchange' => 'Đổi sản phẩm', 'refund' => 'Hoàn tiền', 'warranty' => 'Bảo hành'];
                    $afterSaleStatusLabels = ['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'received' => 'Đã nhận hàng', 'replacement_shipped' => 'Đã gửi hàng thay thế', 'refunded' => 'Đã hoàn tiền', 'completed' => 'Hoàn tất'];
                    $afterSaleRefundLabels = ['not_requested' => 'Không áp dụng', 'pending' => 'Chờ hoàn', 'completed' => 'Đã hoàn', 'failed' => 'Hoàn lỗi'];
                    foreach ($afterSaleRequests as $request):
                    ?>
                        <p style="border-bottom:1px solid #eee;padding:.6rem 0;"><strong><?= htmlspecialchars($request['order_code']) ?></strong> · <?= htmlspecialchars($afterSaleTypeLabels[$request['request_type']] ?? $request['request_type']) ?> · SL <?= (int)($request['approved_quantity'] ?: $request['requested_quantity']) ?> · <?= htmlspecialchars($afterSaleStatusLabels[$request['status']] ?? $request['status']) ?><?php if (!empty($request['refund_status'])): ?> · Hoàn tiền: <?= htmlspecialchars($afterSaleRefundLabels[$request['refund_status']] ?? $request['refund_status']) ?><?php endif; ?><?php if (!empty($request['replacement_tracking_code']) || !empty($request['replacement_shipping_carrier'])): ?><br><small>Hàng thay thế: <?= htmlspecialchars($request['replacement_shipping_carrier'] ?: 'Đơn vị vận chuyển chưa cập nhật') ?><?= !empty($request['replacement_tracking_code']) ? ' · Mã vận đơn ' . htmlspecialchars($request['replacement_tracking_code']) : '' ?></small><?php endif; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const texts = { 1: '(Không tốt)', 2: '(Tạm được)', 3: '(Bình thường)', 4: '(Hài lòng)', 5: '(Rất hài lòng)' };
    document.querySelectorAll('.star-rating-widget').forEach(widget => {
        const input = widget.querySelector('.rating-value-input');
        const textSpan = widget.querySelector('.rating-text');
        const stars = widget.querySelectorAll('.star-icon');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const val = parseInt(this.getAttribute('data-val'));
                input.value = val;
                textSpan.textContent = texts[val] || '';

                stars.forEach(s => {
                    if (parseInt(s.getAttribute('data-val')) <= val) {
                        s.setAttribute('fill', 'currentColor');
                        s.style.color = '#f59e0b';
                    } else {
                        s.setAttribute('fill', 'none');
                        s.style.color = '#cbd5e1';
                    }
                });
            });

            // Hover effect
            star.addEventListener('mouseenter', function() {
                const val = parseInt(this.getAttribute('data-val'));
                stars.forEach(s => {
                    if (parseInt(s.getAttribute('data-val')) <= val) {
                        s.style.transform = 'scale(1.1)';
                        s.style.transition = 'transform 0.1s';
                    }
                });
            });
            star.addEventListener('mouseleave', function() {
                stars.forEach(s => {
                    s.style.transform = 'scale(1)';
                });
            });
        });
    });
});
</script>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
