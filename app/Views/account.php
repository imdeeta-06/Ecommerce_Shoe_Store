<?php
$tab = $_GET['tab'] ?? 'account';
$profileName = !empty($user['display_name']) ? $user['display_name'] : ($user['full_name'] ?? 'User');
include __DIR__ . '/partials/header.php';
?>

<div class="acc-responsive-wrapper" style="max-width: 1200px; margin: 4rem auto; padding: 0 1.5rem;">
    <h1 class="acc-responsive-title" style="text-align: center; font-family: var(--font-heading); font-size: 3rem; margin-bottom: 4rem;">My Account</h1>
    
    <div class="acc-responsive-layout" style="display: grid; grid-template-columns: 280px 1fr; gap: 3rem; align-items: start;">
        <!-- Sidebar -->
        <div style="background: #f5f5f5; border-radius: 12px; padding: 2rem 1.5rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <form id="avatarForm" action="?tab=account" method="POST" enctype="multipart/form-data" style="position: relative; display: inline-block; margin-bottom: 1rem;">
                    <input type="hidden" name="action" value="update_avatar">
                    <!-- Avatar placeholder -->
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid #fff; margin: 0 auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <img src="<?= !empty($user['avatar']) ? BASE_URL . $user['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($profileName).'&background=2A9D8F&color=fff&size=100' ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <!-- Camera icon -->
                    <label for="avatar_upload" title="Thay đổi Avatar" style="position: absolute; bottom: 0; right: 0; background: #333; color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #fff;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        <input type="file" id="avatar_upload" name="avatar" accept="image/*" style="display: none;" onchange="document.getElementById('avatarForm').submit();">
                    </label>
                </form>
                <h3 style="font-family: var(--font-heading); font-size: 1.2rem; margin: 0;"><?= htmlspecialchars($profileName) ?></h3>
            </div>
            
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                <li><a href="?tab=account" style="display: block; padding: 0.8rem 1rem; color: <?= $tab === 'account' ? '#111' : '#666' ?>; text-decoration: none; font-weight: <?= $tab === 'account' ? '700' : '600' ?>; font-family: var(--font-ui); border-radius: 6px; <?= $tab === 'account' ? 'background: #ebebeb;' : '' ?>">Account</a></li>
                <li><a href="?tab=address" style="display: block; padding: 0.8rem 1rem; color: <?= $tab === 'address' ? '#111' : '#666' ?>; text-decoration: none; font-weight: <?= $tab === 'address' ? '700' : '600' ?>; font-family: var(--font-ui); border-radius: 6px; <?= $tab === 'address' ? 'background: #ebebeb;' : '' ?>">Address</a></li>
                <li><a href="?tab=orders" style="display: block; padding: 0.8rem 1rem; color: <?= $tab === 'orders' ? '#111' : '#666' ?>; text-decoration: none; font-weight: <?= $tab === 'orders' ? '700' : '600' ?>; font-family: var(--font-ui); border-radius: 6px; <?= $tab === 'orders' ? 'background: #ebebeb;' : '' ?>">Orders</a></li>
                <li><a href="<?= BASE_URL ?>wishlist" style="display: block; padding: 0.8rem 1rem; color: #666; text-decoration: none; font-weight: 600; font-family: var(--font-ui); border-radius: 6px;">Wishlist</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div>
            <?php if ($tab === 'account'): ?>
                <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 2rem;">Account Details</h2>
                <?php if (!empty($_SESSION['account_error'])): ?>
                    <div style="padding: 12px; border-radius: 6px; margin-bottom: 1rem; background: #fee; color: #c00; font-family: var(--font-ui);">
                        <?= htmlspecialchars($_SESSION['account_error']) ?>
                    </div>
                    <?php unset($_SESSION['account_error']); ?>
                <?php endif; ?>
                <?php if (!empty($_SESSION['account_success'])): ?>
                    <div style="padding: 12px; border-radius: 6px; margin-bottom: 1rem; background: #efe; color: #070; font-family: var(--font-ui);">
                        <?= htmlspecialchars($_SESSION['account_success']) ?>
                    </div>
                    <?php unset($_SESSION['account_success']); ?>
                <?php endif; ?>
                <form action="?tab=account" method="POST">
                    <input type="hidden" name="action" value="update_account">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">Full Name *</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required style="width: 100%; padding: 0.9rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                    </div>
                    
                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">Display Name *</label>
                        <input type="text" name="display_name" value="<?= htmlspecialchars($user['display_name'] ?? '') ?>" style="width: 100%; padding: 0.9rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                    </div>
                    <p style="font-size: 0.85rem; color: #888; margin-bottom: 1.5rem; font-style: italic;">This will be how your name will be displayed in the account section and in reviews</p>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required style="width: 100%; padding: 0.9rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                    </div>

                    <div style="margin-bottom: 2.5rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">Phone Number *</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required maxlength="20" style="width: 100%; padding: 0.9rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                    </div>
                    
                    <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 2rem;">Password</h2>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">Old Password</label>
                        <input type="password" name="old_password" placeholder="Old password" style="width: 100%; padding: 0.9rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">New Password</label>
                        <input type="password" name="new_password" placeholder="New password" style="width: 100%; padding: 0.9rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                    </div>
                    
                    <div style="margin-bottom: 2.5rem;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #666; margin-bottom: 0.5rem; text-transform: uppercase;">Repeat New Password</label>
                        <input type="password" name="repeat_password" placeholder="Repeat new password" style="width: 100%; padding: 0.9rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                    </div>
                    
                    <button type="submit" style="background: #111; color: #fff; border: none; padding: 1rem 2rem; border-radius: 6px; font-weight: 600; font-family: var(--font-ui); cursor: pointer;">Save changes</button>
                </form>
            
            <?php elseif ($tab === 'address'): ?>
                <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 2rem;">Address</h2>

                <?php
                $editingAddress = $_GET['edit'] ?? '';
                $addressCards = [
                    'billing' => 'Billing Address',
                    'shipping' => 'Shipping Address'
                ];
                ?>
                
                <div class="acc-responsive-address" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <?php foreach ($addressCards as $addressType => $addressTitle): ?>
                        <?php $currentAddress = $addressByScope[$addressType] ?? []; ?>
                        <div style="border: 1px solid #ddd; border-radius: 8px; padding: 2rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-family: var(--font-ui);"><?= htmlspecialchars($addressTitle) ?></h3>
                                <a href="?tab=address&edit=<?= htmlspecialchars($addressType) ?>" style="color: #666; text-decoration: none; display: flex; align-items: center; gap: 0.3rem; font-weight: 500;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    Edit
                                </a>
                            </div>

                            <?php if ($editingAddress === $addressType): ?>
                                <form action="?tab=address" method="POST">
                                    <input type="hidden" name="action" value="update_address">
                                    <input type="hidden" name="address_scope" value="<?= htmlspecialchars($addressType) ?>">

                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">Address line</label>
                                        <input type="text" name="address_line" required value="<?= htmlspecialchars($currentAddress['address_line'] ?? '') ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                                    </div>
                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">Ward/District/City</label>
                                        <input type="text" name="ward_district_city" required value="<?= htmlspecialchars($currentAddress['ward_district_city'] ?? '') ?>" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; font-family: var(--font-ui);">
                                    </div>
                                    <button type="submit" style="background: #111; color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 6px; font-weight: 600; font-family: var(--font-ui); cursor: pointer;">Save</button>
                                </form>
                            <?php else: ?>
                                <p style="font-weight: 600; margin: 0 0 0.5rem 0;"><?= htmlspecialchars($currentAddress['address_line'] ?? 'Not entered') ?></p>
                                <p style="color: #888; margin: 0; font-size: 0.9rem;"><?= htmlspecialchars($currentAddress['ward_district_city'] ?? 'Please update your address') ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($tab === 'orders'): ?>
                <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 2rem;">My Orders</h2>
                
                <?php
                $order_status = strtolower($_GET['status'] ?? 'all');
                $statuses = ['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'shipping' => 'Shipping', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
                ?>
                <div style="display: flex; gap: 0.8rem; margin-bottom: 4rem; flex-wrap: wrap;">
                    <?php foreach ($statuses as $statusKey => $statusLabel): ?>
                        <a href="?tab=orders&status=<?= $statusKey ?>" style="padding: 0.6rem 1.5rem; border-radius: 30px; border: 1px solid <?= $order_status === $statusKey ? '#111' : '#eee' ?>; background: <?= $order_status === $statusKey ? '#111' : '#fff' ?>; color: <?= $order_status === $statusKey ? '#fff' : '#666' ?>; text-decoration: none; font-weight: 500; font-family: var(--font-ui); font-size: 0.9rem; transition: 0.2s;">
                            <?= htmlspecialchars($statusLabel) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($orders)): ?>
                    <div style="text-align: center; padding: 4rem 0;">
                        <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; background: #f5f5f5; border-radius: 12px; margin-bottom: 1.5rem;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        </div>
                        <h3 style="margin: 0 0 0.5rem 0; font-family: var(--font-heading); font-size: 1.5rem;">No orders yet</h3>
                        <p style="color: #666; margin: 0 0 2rem 0; font-family: var(--font-ui);">Start shopping now!</p>
                        <a href="<?= BASE_URL ?>shop" style="display: inline-block; background: #111; color: #fff; padding: 0.8rem 2rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-family: var(--font-ui);">Shop Now</a>
                    </div>
                <?php else: ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($orders as $order): ?>
                            <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1.25rem; display: grid; gap: 0.5rem;">
                                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                                    <strong style="font-family: var(--font-ui);"><?= htmlspecialchars($order['order_code'] ?? ('#' . $order['id'])) ?></strong>
                                    <span style="text-transform: capitalize; color: #666;"><?= htmlspecialchars($order['status'] ?? '') ?></span>
                                </div>
                                <div style="color: #666; font-size: 0.95rem;">
                                    <?= htmlspecialchars($order['shipping_name'] ?? '') ?> - <?= htmlspecialchars($order['shipping_phone'] ?? '') ?>
                                </div>
                                <div style="color: #666; font-size: 0.95rem;"><?= htmlspecialchars($order['shipping_address'] ?? '') ?></div>
                                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                    <span style="color: #888;"><?= htmlspecialchars($order['created_at'] ?? '') ?></span>
                                    <strong><?= number_format((float)($order['final_amount'] ?? 0), 0, ',', '.') ?> VND</strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
