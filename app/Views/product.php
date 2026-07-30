<?php include __DIR__ . '/partials/header.php'; ?>

<?php
function productDetailAssetPath($image): string {
    $image = (string)$image;
    if ($image === '') return '';
    if (str_starts_with($image, 'public/uploads/')) return $image;
    if (str_starts_with($image, 'uploads/')) return 'public/' . $image;
    return 'assets/images/' . $image;
}

function productDetailType($product): string {
    $type = trim((string)($product['type'] ?? ''));
    if ($type === '' || $type === '0' || strpos($type, '?') !== false) {
        return trim((string)($product['category'] ?? ''));
    }

    return $type;
}

function productDetailHasBrokenText($text): bool {
    $text = (string)$text;
    return strpos($text, '??') !== false || strpos($text, '�') !== false;
}

function productDetailDescription($product): string {
    $description = trim((string)($product['description'] ?? ''));
    $category = trim((string)($product['category'] ?? ''));

    if ($description === '' || productDetailHasBrokenText($description)) {
        return trim($product['name'] . ' chính hãng Nike. Sản phẩm thuộc dòng ' . $category . ', cam kết chất lượng 100% và bảo hành đầy đủ.');
    }

    return $description;
}
?>

<style>
.product-detail-page { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; font-family: var(--font-body); }
.pd-layout { display: flex; gap: 4rem; }
.pd-main-img { flex: 1.5; background: #f5f5f5; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.pd-main-img img { width: 100%; object-fit: contain; padding: 2rem; }
.pd-info { flex: 1; display: flex; flex-direction: column; }
.pd-title { font-size: 1.8rem; font-weight: 500; margin-bottom: 0.2rem; font-family: var(--font-ui); }
.pd-category { font-size: 1rem; color: #111; margin-bottom: 1rem; }
.pd-price { font-size: 1.2rem; font-weight: 500; margin-bottom: 2rem; }
.pd-size-header { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.95rem; font-weight: 500; }
.pd-size-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 2rem; }
.pd-size-btn { padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; background: #fff; cursor: pointer; font-size: 1rem; transition: all 0.2s; }
.pd-size-btn:hover { border-color: #111; }
.pd-size-btn.active { border-color: #111; box-shadow: inset 0 0 0 1px #111; }
.pd-color-header { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.95rem; font-weight: 500; }
.pd-color-grid { display: flex; gap: 0.8rem; margin-bottom: 2rem; flex-wrap: wrap; }
.pd-color-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; border: 1px solid #ddd; border-radius: 4px; background: #fff; cursor: pointer; font-size: 0.95rem; transition: all 0.2s; }
.pd-color-btn:hover { border-color: #111; }
.pd-color-btn.active { border-color: #111; box-shadow: inset 0 0 0 1px #111; }
.color-swatch { width: 16px; height: 16px; border-radius: 50%; border: 1px solid #ddd; display: inline-block; }
.pd-actions { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 3rem; }
.btn-add-bag { padding: 1.2rem; background: #111; color: #fff; border: none; border-radius: 100px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: background 0.2s; }
.btn-add-bag:hover { background: #333; }
.btn-favourite { padding: 1.2rem; background: #fff; color: #111; border: 1px solid #ccc; border-radius: 100px; font-size: 1rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: border-color 0.2s; }
.btn-favourite:hover { border-color: #111; }
.btn-favourite.active svg { fill: #111; }
.pd-desc { font-size: 1rem; line-height: 1.6; margin-bottom: 2rem; }
.pd-details { list-style: disc; padding-left: 1.5rem; font-size: 1rem; line-height: 1.8; }
.related-section { margin-top: 5rem; }
.related-section h2 { font-size: 1.5rem; margin-bottom: 2rem; text-transform: none; letter-spacing: normal; font-family: var(--font-ui); }
.related-grid { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 2rem; scrollbar-width: none; }
.related-grid::-webkit-scrollbar { display: none; }
.related-card { flex: 0 0 280px; }
.related-img { background: #f5f5f5; margin-bottom: 1rem; border-radius: 8px; }
.related-img img { width: 100%; height: 280px; object-fit: contain; }
.related-info .r-title { font-weight: 500; margin-bottom: 0.2rem; display: block; }
.related-info .r-cat { color: #666; font-size: 0.9rem; margin-bottom: 0.5rem; display: block; }
.related-info .r-price { font-weight: 500; }
@media(max-width: 900px) { .pd-layout { flex-direction: column; } }
</style>

<div class="product-detail-page">
    <div class="pd-layout">
        <div class="pd-main-img">
            <img src="<?= BASE_URL . htmlspecialchars(productDetailAssetPath($product['image'])) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <div class="pd-info">
            <h1 class="pd-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="pd-category"><?= htmlspecialchars(productDetailType($product)) ?></div>
            <div class="pd-price"><?= number_format($product['price'], 0, ',', '.') ?> ₫</div>

            <div class="pd-color-header">
                <span>Chọn Màu</span>
            </div>
            <div class="pd-color-grid">
                <button class="pd-color-btn">
                    <span class="color-swatch" style="background-color: #dc2626;"></span>
                    Đỏ
                </button>
                <button class="pd-color-btn">
                    <span class="color-swatch" style="background-color: #ffffff;"></span>
                    Trắng
                </button>
                <button class="pd-color-btn">
                    <span class="color-swatch" style="background-color: #111111;"></span>
                    Đen
                </button>
            </div>

            <div class="pd-size-header">
                <span>Chọn Size</span>
                <span style="color:#666; cursor:pointer;">Hướng dẫn chọn size</span>
            </div>
            <div class="pd-size-grid">
                <?php foreach (['EU 40','EU 40.5','EU 41','EU 42','EU 42.5','EU 43','EU 44','EU 44.5','EU 45'] as $size): ?>
                    <button class="pd-size-btn"><?= $size ?></button>
                <?php endforeach; ?>
            </div>

            <div class="pd-actions">
                <button class="btn-add-bag" onclick="addToCart(<?= $product['id'] ?>)">Thêm vào giỏ</button>
                <?php
                $isFav = false;
                if (isset($_SESSION['user_id'])) {
                    $wishlistModel = new \App\Models\Wishlist();
                    $isFav = $wishlistModel->checkExists($_SESSION['user_id'], $product['id']);
                }
                ?>
                <button class="btn-favourite <?= $isFav ? 'active' : '' ?>" onclick="toggleFavourite(this, <?= $product['id'] ?>)">
                    Yêu thích
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>
            </div>

            <div class="pd-desc">
                <?= nl2br(htmlspecialchars(productDetailDescription($product))) ?>
            </div>

            <ul class="pd-details">
                <li>Danh mục: <?= htmlspecialchars($product['category']) ?></li>
                <li>Xuất xứ: Vietnam</li>
                <li>Bảo hành chính hãng</li>
            </ul>
        </div>
    </div>

    <div class="related-section">
        <h2>Sản phẩm bạn có thể thích</h2>
        <div class="related-grid">
            <?php foreach ($related as $r): ?>
            <a href="<?= BASE_URL ?>product?id=<?= $r['id'] ?>" class="related-card">
                <div class="related-img"><img src="<?= BASE_URL . htmlspecialchars(productDetailAssetPath($r['image'])) ?>" alt="<?= htmlspecialchars($r['name']) ?>"></div>
                <div class="related-info">
                    <span class="r-title"><?= htmlspecialchars($r['name']) ?></span>
                    <span class="r-cat"><?= htmlspecialchars(productDetailType($r)) ?></span>
                    <span class="r-price"><?= number_format($r['price'], 0, ',', '.') ?> ₫</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// Size selection
document.querySelectorAll('.pd-size-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.pd-size-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});

// Color selection
document.querySelectorAll('.pd-color-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.pd-color-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});

// ===== CART (Database) =====
function addToCart(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('qty', 1);

    fetch(BASE_URL + 'cart/add', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Đã thêm vào giỏ hàng!');
            if (typeof window.updateBadgeGlobal === 'function') {
                window.updateBadgeGlobal(data.cart_count);
            }
        } else {
            showToast(data.message || 'Có lỗi xảy ra!');
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

// ===== FAVOURITE =====
function toggleFavourite(btn, productId) {
    const isAdding = !btn.classList.contains('active');
    const url = isAdding ? BASE_URL + 'wishlist/add' : BASE_URL + 'wishlist/remove';

    const formData = new FormData();
    formData.append('product_id', productId);

    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isAdding) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
            showToast(data.message);
        } else {
            showToast(data.message);
            if (data.message.includes('đăng nhập')) {
                setTimeout(() => {
                    window.location.href = BASE_URL + 'login';
                }, 1500);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra, vui lòng thử lại!');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Cart badge + open on cart icon
    const cartIcon = document.querySelector('a[href="<?= BASE_URL ?>cart"]');
    if (cartIcon && !cartIcon.querySelector('.cart-badge')) {
        const badge = document.createElement('span');
        badge.className = 'cart-badge';
        badge.style.display = 'none';
        badge.textContent = '0';
        cartIcon.appendChild(badge);
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
