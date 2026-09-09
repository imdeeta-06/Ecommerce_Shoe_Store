<?php include __DIR__ . '/partials/header.php'; ?>

<main class="wishlist-page">
    <h1 style="font-family: var(--font-ui); font-size: 2rem; margin-bottom: 2rem;">Danh sách yêu thích</h1>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div style="text-align: center; padding: 5rem 0;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 1rem; color: #ccc;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            <h2 style="font-family: var(--font-ui); margin-bottom: 1rem;">Bạn cần đăng nhập</h2>
            <p style="color: #666; margin-bottom: 2rem;">Vui lòng đăng nhập để xem và quản lý danh sách yêu thích của bạn.</p>
            <a href="<?= BASE_URL ?>login" style="display: inline-block; padding: 1rem 2rem; background: #111; color: #fff; text-decoration: none; border-radius: 100px; font-weight: 500;">Đăng nhập ngay</a>
        </div>
    <?php else: ?>
        <div id="wishlist-container" class="wishlist-grid" style="display: <?= empty($wishlistItems) ? 'none' : 'grid' ?>;">
            <?php foreach ($wishlistItems as $item): ?>
                <?php 
                    $imgUrl = $item['image_url'];
                    $cartImgUrl = $imgUrl; // For cart, we need a path that doesn't have BASE_URL prepended (except for http)
                    
                    if ($imgUrl && !str_starts_with($imgUrl, 'http')) {
                        if (str_starts_with($imgUrl, 'public/uploads/')) {
                            $imgUrl = BASE_URL . $imgUrl;
                            $cartImgUrl = $item['image_url'];
                        } elseif (str_starts_with($imgUrl, 'uploads/')) {
                            $imgUrl = BASE_URL . 'public/' . $imgUrl;
                            $cartImgUrl = 'public/' . $item['image_url'];
                        } else {
                            $imgUrl = BASE_URL . 'assets/images/' . $imgUrl;
                            $cartImgUrl = 'assets/images/' . $item['image_url'];
                        }
                    }
                ?>
                <div class="wishlist-item" id="wishlist-item-<?= $item['product_id'] ?>">
                    <div class="wishlist-image-wrap">
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <button class="wishlist-remove" onclick="removeFromWishlist(<?= $item['product_id'] ?>)" aria-label="Xóa <?= htmlspecialchars($item['name']) ?> khỏi danh sách yêu thích">
                            ✕
                        </button>
                    </div>
                    <div class="wishlist-item-body">
                        <h3><a href="<?= BASE_URL ?>product?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a></h3>
                        <div class="wishlist-price">
                            <?php if (!empty($item['old_price']) && (float)$item['old_price'] > (float)$item['price']): ?><del class="product-price-old"><?= number_format($item['old_price'], 0, ',', '.') ?> ₫</del><?php endif; ?>
                            <?= number_format($item['price'], 0, ',', '.') ?> ₫
                        </div>
                        <button class="wishlist-add-cart" onclick="addToCartFromWishlist(<?= $item['product_id'] ?>)">
                            Thêm vào giỏ
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="wishlist-empty" style="display: <?= empty($wishlistItems) ? 'block' : 'none' ?>; text-align: center; padding: 5rem 0;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 1rem; color: #ccc;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            <h2 style="font-family: var(--font-ui); margin-bottom: 1rem;">Danh sách yêu thích trống</h2>
            <p style="color: #666; margin-bottom: 2rem;">Bạn chưa lưu sản phẩm nào vào danh sách yêu thích.</p>
            <a href="<?= BASE_URL ?>shop" style="display: inline-block; padding: 1rem 2rem; background: #111; color: #fff; text-decoration: none; border-radius: 100px; font-weight: 500;">Tiếp tục mua sắm</a>
        </div>
    <?php endif; ?>
</main>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
function removeFromWishlist(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);

    fetch(BASE_URL + 'wishlist/remove', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const itemElement = document.getElementById('wishlist-item-' + productId);
            if (itemElement) {
                itemElement.remove();
            }
            showToast('Đã xóa khỏi danh sách yêu thích');
            
            // Check if wishlist is empty now
            const container = document.getElementById('wishlist-container');
            if (container && container.querySelectorAll('.wishlist-item').length === 0) {
                container.style.display = 'none';
                document.getElementById('wishlist-empty').style.display = 'block';
            }
        } else {
            showToast(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra, vui lòng thử lại!');
    });
}

function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
}

function addToCartFromWishlist(productId) {
    window.location.href = BASE_URL + 'product?id=' + productId;
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
