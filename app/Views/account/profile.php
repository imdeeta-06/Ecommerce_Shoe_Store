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

<main style="max-width: 980px; margin: 3rem auto; padding: 0 1rem;">
    <h1 style="font-size: 2rem; margin-bottom: 1.5rem;">Tài khoản của tôi</h1>

    <?php foreach ($flashMessages as $type => $message): ?>
        <div style="padding: 12px; border-radius: 8px; margin-bottom: 1rem; background: <?= $type === 'error' ? '#fee' : '#efe' ?>; color: <?= $type === 'error' ? '#c00' : '#070' ?>;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endforeach; ?>

    <section style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Thông tin cá nhân</h2>

        <div style="display: flex; gap: 1.5rem; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <?php if ($avatar): ?>
                <img src="<?= BASE_URL . htmlspecialchars($avatar) ?>" alt="Avatar" style="width: 96px; height: 96px; border-radius: 50%; object-fit: cover; background: #f5f5f5;">
            <?php else: ?>
                <div style="width: 96px; height: 96px; border-radius: 50%; background: #f5f5f5; display: grid; place-items: center; font-weight: 700;">
                    <?= htmlspecialchars(strtoupper(substr($user['full_name'] ?? 'U', 0, 1))) ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>account/avatar" method="POST" enctype="multipart/form-data">
                <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" required>
                <button type="submit" class="btn-login" style="margin-top: 0.75rem;">Cập nhật ảnh</button>
            </form>
        </div>

        <form action="<?= BASE_URL ?>account/update" method="POST">
            <div class="form-group">
                <input type="text" name="full_name" placeholder="Họ và tên" required value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" readonly value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <input type="tel" name="phone" placeholder="Số điện thoại" maxlength="20" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <button type="submit" class="btn-login">Lưu thông tin</button>
        </form>
    </section>

    <section>
        <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Địa chỉ</h2>

        <?php if (empty($addresses)): ?>
            <p style="color: #777;">Bạn chưa có địa chỉ nào.</p>
        <?php else: ?>
            <div style="display: grid; gap: 1rem; margin-bottom: 2rem;">
                <?php foreach ($addresses as $address): ?>
                    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1rem;">
                        <p style="margin: 0 0 0.35rem; font-weight: 600;"><?= htmlspecialchars($address['address_line'] ?? '') ?></p>
                        <p style="margin: 0 0 0.75rem; color: #666;"><?= htmlspecialchars($address['ward_district_city'] ?? '') ?></p>
                        <?php if (!empty($address['is_default'])): ?>
                            <span style="display: inline-block; margin-bottom: 0.75rem; font-size: 0.85rem; font-weight: 600;">Mặc định</span>
                        <?php endif; ?>

                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <form action="<?= BASE_URL ?>account/addresses/default" method="POST">
                                <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                                <button type="submit">Đặt mặc định</button>
                            </form>
                            <form action="<?= BASE_URL ?>account/addresses/delete" method="POST">
                                <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                                <button type="submit">Xóa</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h3 style="font-size: 1rem; margin-bottom: 1rem;">Thêm địa chỉ mới</h3>
        <form action="<?= BASE_URL ?>account/addresses/add" method="POST">
            <div class="form-group">
                <input type="text" name="recipient_name" placeholder="Tên người nhận" required>
            </div>
            <div class="form-group">
                <input type="text" name="phone" placeholder="Số điện thoại" required>
            </div>
            <div class="form-group">
                <input type="text" name="address" placeholder="Địa chỉ" required>
            </div>
            <div class="form-group">
                <input type="text" name="city" placeholder="Phường/Xã, Quận/Huyện, Tỉnh/Thành phố" required>
            </div>
            <label style="display: block; margin-bottom: 1rem;">
                <input type="checkbox" name="is_default" value="1"> Đặt làm địa chỉ mặc định
            </label>
            <button type="submit" class="btn-login">Thêm địa chỉ</button>
        </form>
    </section>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>
