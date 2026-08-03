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

$productVariants = array_values(array_filter($product['variants'] ?? [], static function ($variant) {
    return (int)($variant['stock_quantity'] ?? 0) >= 0;
}));
$productSizes = [];
$productColors = [];
foreach ($productVariants as $variant) {
    $size = trim((string)($variant['size'] ?? ''));
    $color = trim((string)($variant['color'] ?? ''));
    if ($size !== '' && !in_array($size, $productSizes, true)) $productSizes[] = $size;
    if ($color !== '' && !in_array($color, $productColors, true)) $productColors[] = $color;
}
$defaultVariant = null;
foreach ($productVariants as $variant) {
    if ((int)($variant['stock_quantity'] ?? 0) > 0) {
        $defaultVariant = $variant;
        break;
    }
}
$defaultVariant = $defaultVariant ?: ($productVariants[0] ?? null);

function productDetailColorLabel($color): string {
    return ['Black' => 'Đen', 'Red' => 'Đỏ', 'White' => 'Trắng'][$color] ?? (string)$color;
}

function productDetailColorHex($color): string {
    return ['Black' => '#111111', 'Red' => '#dc2626', 'White' => '#ffffff'][$color] ?? '#d1d5db';
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
.pd-size-btn:disabled { color: #aaa; background: #f7f7f7; border-color: #eee; cursor: not-allowed; text-decoration: line-through; }
.pd-size-guide { margin: -0.75rem 0 1.5rem; color: #555; font-size: 0.9rem; }
.pd-size-guide summary { cursor: pointer; color: #111; font-weight: 500; }
.pd-size-guide table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; font-size: 0.85rem; }
.pd-size-guide th, .pd-size-guide td { border-bottom: 1px solid #eee; padding: 0.45rem; text-align: left; }
.pd-color-header { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.95rem; font-weight: 500; }
.pd-color-grid { display: flex; gap: 0.8rem; margin-bottom: 2rem; flex-wrap: wrap; }
.pd-color-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; border: 1px solid #ddd; border-radius: 4px; background: #fff; cursor: pointer; font-size: 0.95rem; transition: all 0.2s; }
.pd-color-btn:hover { border-color: #111; }
.pd-color-btn.active { border-color: #111; box-shadow: inset 0 0 0 1px #111; }
.color-swatch { width: 16px; height: 16px; border-radius: 50%; border: 1px solid #ddd; display: inline-block; }
.pd-actions { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 3rem; }
.btn-add-bag { padding: 1.2rem; background: #111; color: #fff; border: none; border-radius: 100px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: background 0.2s; }
.btn-add-bag:hover { background: #333; }
.btn-add-bag:disabled { background: #d1d1d1; color: #777; cursor: not-allowed; }
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
            <div class="pd-price" id="productPrice"><?= number_format((float)$product['price'], 0, ',', '.') ?> ₫</div>

            <div class="pd-color-header">
                <span>Chọn màu <strong id="selectedColorLabel"></strong></span>
            </div>
            <div class="pd-color-grid">
                <?php foreach ($productColors as $color): ?>
                    <button type="button" class="pd-color-btn" data-color="<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="color-swatch" style="background-color: <?= productDetailColorHex($color) ?>;"></span>
                        <?= htmlspecialchars(productDetailColorLabel($color)) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="pd-size-header">
                <span>Chọn size <strong id="selectedSizeLabel"></strong></span>
                <span style="color:#666;">Size EU</span>
            </div>
            <div class="pd-size-grid">
                <?php foreach ($productSizes as $size): ?>
                    <button type="button" class="pd-size-btn" data-size="<?= htmlspecialchars($size, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($size) ?></button>
                <?php endforeach; ?>
                <?php if (empty($productSizes)): ?>
                    <p style="grid-column:1/-1; color:#b42318; margin:0;">Sản phẩm chưa được thiết lập size. Vui lòng liên hệ cửa hàng.</p>
                <?php endif; ?>
            </div>

            <details class="pd-size-guide">
                <summary>Hướng dẫn chọn size giày</summary>
                <p>Đo chiều dài bàn chân vào cuối ngày, chọn size lớn hơn nếu chân bè hoặc thường mang tất dày.</p>
                <table>
                    <thead><tr><th>Chiều dài chân</th><th>Size EU tham khảo</th></tr></thead>
                    <tbody>
                        <tr><td>23,0–24,0 cm</td><td>EU 36–38</td></tr>
                        <tr><td>24,5–26,0 cm</td><td>EU 39–41</td></tr>
                        <tr><td>26,5–28,0 cm</td><td>EU 42–44</td></tr>
                        <tr><td>28,5 cm trở lên</td><td>EU 45</td></tr>
                    </tbody>
                </table>
            </details>

            <div class="client-form-group" style="margin-bottom: 1.5rem;">
                <label class="client-label" for="productQuantity">Số lượng</label>
                <input id="productQuantity" class="client-input" type="number" min="1" value="1" style="max-width: 120px;">
                <small id="variantStockMessage" style="display:block; margin-top:0.4rem; color:#666;"></small>
            </div>

            <div class="pd-actions">
                <button class="btn-add-bag" onclick="addSelectedVariantToCart()" <?= empty($defaultVariant) || (int)($defaultVariant['stock_quantity'] ?? 0) <= 0 ? 'disabled' : '' ?>>Thêm vào giỏ</button>
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

    <section class="review-section" style="margin-top: 4rem; border-top: 1px solid #eee; padding-top: 2rem;">
        <h2 class="related-section h2">Đánh giá sản phẩm</h2>
        <?php if (empty($reviews)): ?>
            <p style="color:#666;">Chưa có đánh giá nào cho sản phẩm này.</p>
        <?php else: ?>
            <div style="display:grid; gap:1rem;">
                <?php foreach ($reviews as $review): ?>
                    <article style="border:1px solid #eee; border-radius:8px; padding:1rem;">
                        <strong><?= htmlspecialchars($review['display_name'] ?: ($review['full_name'] ?? 'Khách hàng')) ?></strong>
                        <span style="color:#c47b00; margin-left:0.5rem;"><?= str_repeat('★', (int)$review['rating']) ?></span>
                        <p style="margin-top:0.5rem; color:#444;"><?= nl2br(htmlspecialchars($review['comment'] ?? '')) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

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
const productVariants = <?= json_encode($productVariants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let selectedSize = <?= json_encode($defaultVariant['size'] ?? null, JSON_UNESCAPED_UNICODE) ?>;
let selectedColor = <?= json_encode($defaultVariant['color'] ?? null, JSON_UNESCAPED_UNICODE) ?>;
const colorLabels = {Black: 'Đen', Red: 'Đỏ', White: 'Trắng'};

function formatProductPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + ' ₫';
}

function getSelectedVariant() {
    return productVariants.find(variant => variant.size === selectedSize && variant.color === selectedColor) || null;
}

function refreshVariantSelection() {
    const variant = getSelectedVariant();
    const addButton = document.querySelector('.btn-add-bag');
    document.querySelectorAll('.pd-size-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.size === selectedSize));
    document.querySelectorAll('.pd-color-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.color === selectedColor));
    document.getElementById('selectedSizeLabel').textContent = selectedSize ? '(' + selectedSize + ')' : '';
    document.getElementById('selectedColorLabel').textContent = selectedColor ? '(' + (colorLabels[selectedColor] || selectedColor) + ')' : '';
    addButton.disabled = !variant || parseInt(variant.stock_quantity || 0, 10) <= 0;

    if (variant) {
        document.getElementById('productPrice').textContent = formatProductPrice(<?= (float)$product['price'] ?> + parseFloat(variant.price_modifier || 0));
        document.getElementById('variantStockMessage').textContent = variant.stock_quantity > 0 ? 'Còn ' + variant.stock_quantity + ' sản phẩm' : 'Phân loại này đang hết hàng';
        document.getElementById('productQuantity').max = Math.max(1, parseInt(variant.stock_quantity || 0));
    }
}

document.querySelectorAll('.pd-size-btn').forEach(btn => btn.addEventListener('click', () => {
    selectedSize = btn.dataset.size;
    refreshVariantSelection();
}));
document.querySelectorAll('.pd-color-btn').forEach(btn => btn.addEventListener('click', () => {
    selectedColor = btn.dataset.color;
    refreshVariantSelection();
}));
refreshVariantSelection();

// ===== CART (Database) =====
function addSelectedVariantToCart() {
    const variant = getSelectedVariant();
    const quantity = Math.max(1, parseInt(document.getElementById('productQuantity').value || '1', 10));
    if (!variant) {
        showToast('Vui lòng chọn đúng size và màu.');
        return;
    }
    if (quantity > parseInt(variant.stock_quantity || 0, 10)) {
        showToast('Số lượng vượt quá tồn kho.');
        return;
    }

    const formData = new FormData();
    formData.append('variant_id', variant.id);
    formData.append('qty', quantity);

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
