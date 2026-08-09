<?php
$tab = $_GET['tab'] ?? 'account';
$profileName = !empty($user['display_name']) ? $user['display_name'] : ($user['full_name'] ?? 'User');
include __DIR__ . '/partials/header.php';
?>

<div class="client-page">
    <h1 class="client-title">Tài khoản của tôi</h1>
    
    <div class="client-layout-2">
        <!-- Sidebar -->
        <aside>
            <div class="client-avatar-wrapper">
                <form id="avatarForm" action="?tab=account" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_avatar">
                    <img src="<?= !empty($user['avatar']) ? BASE_URL . $user['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($profileName).'&background=111&color=fff&size=200' ?>" alt="Avatar" class="client-avatar-img">
                    <div>
                        <label for="avatar_upload" class="client-avatar-label">Thay đổi ảnh</label>
                        <input type="file" id="avatar_upload" name="avatar" accept="image/*" style="display: none;" onchange="document.getElementById('avatarForm').submit();">
                    </div>
                </form>
            </div>
            
            <ul class="client-menu">
                <li><a href="?tab=account" class="<?= $tab === 'account' ? 'active' : '' ?>">Thông tin cá nhân</a></li>
                <li><a href="?tab=address" class="<?= $tab === 'address' ? 'active' : '' ?>">Địa chỉ</a></li>
                <li><a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>">Đơn hàng</a></li>
                <li><a href="<?= BASE_URL ?>wishlist">Danh sách yêu thích</a></li>
                <li><a href="<?= BASE_URL ?>logout" style="color: #ef4444;">Đăng xuất</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main>
            <?php if ($tab === 'account'): ?>
                <h2 class="client-section-title">Thông tin chi tiết</h2>
                
                <?php if (!empty($_SESSION['account_error'])): ?>
                    <div class="client-flash error">
                        <?= htmlspecialchars($_SESSION['account_error']) ?>
                    </div>
                    <?php unset($_SESSION['account_error']); ?>
                <?php endif; ?>
                <?php if (!empty($_SESSION['account_success'])): ?>
                    <div class="client-flash success">
                        <?= htmlspecialchars($_SESSION['account_success']) ?>
                    </div>
                    <?php unset($_SESSION['account_success']); ?>
                <?php endif; ?>
                
                <form action="?tab=account" method="POST">
                    <input type="hidden" name="action" value="update_account">
                    
                    <div class="client-form-group">
                        <label class="client-label">Họ và Tên *</label>
                        <input type="text" name="full_name" class="client-input" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="client-form-group" style="margin-bottom: 2rem;">
                        <label class="client-label">Tên hiển thị *</label>
                        <input type="text" name="display_name" class="client-input" value="<?= htmlspecialchars($user['display_name'] ?? '') ?>" required>
                        <small style="color: #888; font-size: 0.75rem; margin-top: 0.5rem; display: block;">Tên này sẽ hiển thị ở phần đánh giá sản phẩm.</small>
                    </div>
                    
                    <div class="client-form-group">
                        <label class="client-label">Email *</label>
                        <input type="email" name="email" class="client-input" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>

                    <div class="client-form-group" style="margin-bottom: 3rem;">
                        <label class="client-label">Số điện thoại *</label>
                        <input type="tel" name="phone" class="client-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required maxlength="20">
                    </div>
                    
                    <h2 class="client-section-title">Mật khẩu</h2>
                    
                    <div class="client-form-group">
                        <label class="client-label">Mật khẩu cũ</label>
                        <input type="password" name="old_password" class="client-input" placeholder="Để trống nếu không đổi">
                    </div>
                    
                    <div class="client-form-group">
                        <label class="client-label">Mật khẩu mới</label>
                        <input type="password" name="new_password" class="client-input">
                    </div>
                    
                    <div class="client-form-group" style="margin-bottom: 2rem;">
                        <label class="client-label">Xác nhận mật khẩu mới</label>
                        <input type="password" name="repeat_password" class="client-input">
                    </div>
                    
                    <button type="submit" class="client-btn">Lưu thay đổi</button>
                </form>
            
            <?php elseif ($tab === 'address'): ?>
                <h2 class="client-section-title">Địa chỉ của bạn</h2>

                <?php
                $editingAddress = $_GET['edit'] ?? '';
                $addressCards = [
                    'billing' => 'Địa chỉ thanh toán',
                    'shipping' => 'Địa chỉ giao hàng'
                ];
                ?>
                
                <div style="display: grid; gap: 2rem;">
                    <?php foreach ($addressCards as $addressType => $addressTitle): ?>
                        <?php $currentAddress = $addressByScope[$addressType] ?? []; ?>
                        <div class="client-card">
                            <div class="client-card-header">
                                <span><?= htmlspecialchars($addressTitle) ?></span>
                                <a href="?tab=address&edit=<?= htmlspecialchars($addressType) ?>" class="client-text-link">Sửa</a>
                            </div>

                            <?php if ($editingAddress === $addressType): ?>
                                <form action="?tab=address" method="POST" style="margin-top: 1.5rem;">
                                    <input type="hidden" name="action" value="update_address">
                                    <input type="hidden" name="address_scope" value="<?= htmlspecialchars($addressType) ?>">

                                    <div class="client-form-group">
                                        <label class="client-label">Số nhà, Tên đường</label>
                                        <input type="text" name="address_line" class="client-input" required value="<?= htmlspecialchars($currentAddress['address_line'] ?? '') ?>">
                                    </div>
                                    <div class="client-form-group">
                                        <label class="client-label">Phường/Xã, Quận/Huyện, Tỉnh/Thành phố</label>
                                        <input type="text" name="ward_district_city" class="client-input" required value="<?= htmlspecialchars($currentAddress['ward_district_city'] ?? '') ?>">
                                    </div>
                                    <button type="submit" class="client-btn client-btn-sm">Lưu địa chỉ</button>
                                </form>
                            <?php else: ?>
                                <div class="client-card-body">
                                    <p style="font-weight: 500; color: #111;"><?= htmlspecialchars($currentAddress['address_line'] ?? 'Chưa cung cấp') ?></p>
                                    <p><?= htmlspecialchars($currentAddress['ward_district_city'] ?? 'Vui lòng cập nhật địa chỉ để giao hàng nhanh chóng') ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($tab === 'orders'): ?>
                <h2 class="client-section-title">Đơn hàng của tôi</h2>
                
                <?php
                $order_status = strtolower($_GET['status'] ?? 'all');
                $statuses = ['all' => 'Tất cả', 'pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị', 'shipping' => 'Đang giao', 'delivered' => 'Giao thành công', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã huỷ', 'canceled' => 'Đã huỷ'];
                ?>
                <div class="client-tabs">
                    <?php foreach ($statuses as $statusKey => $statusLabel): ?>
                        <a href="?tab=orders&status=<?= $statusKey ?>" class="client-tab <?= $order_status === $statusKey ? 'active' : '' ?>">
                            <?= htmlspecialchars($statusLabel) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($orders)): ?>
                    <div style="text-align: center; padding: 4rem 0;">
                        <h3 style="font-family: var(--font-heading); font-size: 1.25rem; text-transform: uppercase; margin-bottom: 0.5rem;">Chưa có đơn hàng nào</h3>
                        <p style="color: #666; margin-bottom: 2rem;">Bạn chưa thực hiện đơn đặt hàng nào hoặc đơn hàng chưa hiển thị.</p>
                        <a href="<?= BASE_URL ?>shop" class="client-btn">Mua sắm ngay</a>
                    </div>
                <?php else: ?>
                    <div class="client-data-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="client-data-item">
                                <div class="client-data-row">
                                    <span class="client-data-label">Mã đơn hàng</span>
                                    <span class="client-data-val"><?= htmlspecialchars($order['order_code'] ?? ('#' . $order['id'])) ?></span>
                                </div>
                                <div class="client-data-row">
                                    <span class="client-data-label">Ngày đặt</span>
                                    <span class="client-data-val" style="color: #666; font-size: 0.85rem;"><?= htmlspecialchars($order['created_at'] ?? '') ?></span>
                                </div>
                                <div class="client-data-row">
                                    <span class="client-data-label">Người nhận</span>
                                    <span class="client-data-val" style="color: #666; font-size: 0.85rem;">
                                        <?= htmlspecialchars($order['shipping_name'] ?? '') ?> - <?= htmlspecialchars($order['shipping_phone'] ?? '') ?>
                                    </span>
                                </div>
                                <div class="client-data-row" style="margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;">
                                    <span class="client-data-label">Trạng thái</span>
                                    <span class="client-data-val" style="text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; color: #111;">
                                        <?= htmlspecialchars($statuses[$order['status']] ?? $order['status']) ?>
                                    </span>
                                </div>
                                <div class="client-data-row">
                                    <span class="client-data-label">Tổng tiền</span>
                                    <span class="client-data-val" style="font-size: 1.1rem;"><?= number_format((float)($order['final_amount'] ?? 0), 0, ',', '.') ?> ₫</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
