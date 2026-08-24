<?php include __DIR__ . '/partials/header.php'; ?>

<main class="client-page wishlist-page">
    <h1 class="client-title wishlist-title">Danh sách yêu thích</h1>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="wishlist-empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="empty-icon"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            <h2>Bạn cần đăng nhập</h2>
            <p>Vui lòng đăng nhập để xem và quản lý danh sách yêu thích của bạn.</p>
            <a href="<?= BASE_URL ?>login" class="client-btn wishlist-btn-action">Đăng nhập ngay</a>
        </div>
    <?php else: ?>
        <div id="wishlist-container" class="wishlist-grid <?= empty($wishlistItems) ? 'is-hidden' : '' ?>">
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
                <div class="wishlist-card" id="wishlist-item-<?= $item['product_id'] ?>">
                    <div class="wishlist-media">
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                        <button onclick="removeFromWishlist(<?= $item['product_id'] ?>)" class="wishlist-remove-btn" title="Xóa khỏi yêu thích" aria-label="Xóa">
                            &times;
                        </button>
                    </div>
                    <div class="wishlist-info">
                        <h3 class="wishlist-name"><a href="<?= BASE_URL ?>product?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a></h3>
                        <div class="wishlist-price"><?= number_format($item['price'], 0, ',', '.') ?> ₫</div>
                        <button onclick="addToCartFromWishlist(<?= $item['product_id'] ?>)" class="btn-buy wishlist-btn-buy">
                            Xem &amp; Mua
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="wishlist-empty" class="wishlist-empty-state <?= empty($wishlistItems) ? '' : 'is-hidden' ?>">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="empty-icon"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            <h2>Danh sách yêu thích trống</h2>
            <p>Bạn chưa lưu sản phẩm nào vào danh sách yêu thích.</p>
            <a href="<?= BASE_URL ?>shop" class="client-btn wishlist-btn-action">Tiếp tục mua sắm</a>
        </div>
    <?php endif; ?>
</main>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
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
