<?php include __DIR__ . '/partials/header.php'; ?>

<?php
$category = $_GET['category'] ?? 'all';
$sort = $_GET['sort'] ?? 'default';
$priceRange = $_GET['price'] ?? 'all';
$keyword = trim($_GET['q'] ?? '');

function shopUrl(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    $params = array_intersect_key($params, array_flip(['category', 'price', 'sort', 'q', 'page']));
    if (isset($params['page']) && (int)$params['page'] <= 1) unset($params['page']);
    $query = http_build_query($params);
    return BASE_URL . 'shop' . ($query !== '' ? '?' . $query : '');
}

function productAssetPath($image): string {
    $image = (string)$image;
    if ($image === '') return '';
    if (str_starts_with($image, 'public/uploads/')) return $image;
    if (str_starts_with($image, 'uploads/')) return 'public/' . $image;
    return 'assets/images/' . $image;
}

function shopProductImageUrl($image): string {
    $image = trim((string)$image);
    if ($image === '') return '';
    if (preg_match('/^https?:\/\//i', $image)) return $image;
    return BASE_URL . productAssetPath($image);
}

function productDisplayType($product): string {
    return trim((string)($product['category'] ?? ''));
}

function productSizeOptions($product): array {
    $category = mb_strtolower(productDisplayType($product), 'UTF-8');
    if (str_contains($category, 'quần') || str_contains($category, 'áo') || str_contains($category, 'đồ lam') || str_contains($category, 'pháp phục')) {
        return ['S', 'M', 'L', 'XL'];
    }
    if (str_contains($category, 'vòng tay') || str_contains($category, 'dây chuyền')) {
        return ['8 mm', '10 mm', '12 mm', '14 mm', '16 mm', '18 mm', '20 mm'];
    }
    return ['Free Size'];
}
?>

<main>
    <section class="shop-page">
        <div class="shop-topbar">
            <h1><?= $category === 'all' ? 'Tất cả sản phẩm' : htmlspecialchars($category) ?> (<?= (int)$totalFilteredProducts ?>)</h1>
            <div class="shop-sort">
                <form method="get" action="<?= BASE_URL ?>shop" id="sortForm">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <input type="hidden" name="price" value="<?= htmlspecialchars($priceRange) ?>">
                    <?php if ($keyword !== ''): ?>
                        <input type="hidden" name="q" value="<?= htmlspecialchars($keyword) ?>">
                    <?php endif; ?>
                    <label for="sortSel">Sắp xếp</label>
                    <select name="sort" id="sortSel" onchange="this.form.submit()">
                        <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Mặc định</option>
                        <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Giá: Thấp đến cao</option>
                        <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Giá: Cao đến thấp</option>
                        <option value="name-asc" <?= $sort === 'name-asc' ? 'selected' : '' ?>>Tên: A đến Z</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="shop-layout">
            <aside class="shop-sidebar">
                <ul class="filter-cat-list">
                    <li><a href="<?= htmlspecialchars(shopUrl(['category' => 'all', 'page' => 1])) ?>" class="<?= $category === 'all' ? 'active' : '' ?>">Tất cả (<?= $totalActiveProducts ?>)</a></li>
                    <?php foreach ($categories as $c): ?>
                        <li><a href="<?= htmlspecialchars(shopUrl(['category' => $c['name'], 'page' => 1])) ?>" class="<?= $category === $c['name'] ? 'active' : '' ?>"><?= htmlspecialchars($c['name']) ?> (<?= (int)$c['product_count'] ?>)</a></li>
                    <?php endforeach; ?>
                </ul>


                <details class="filter-group" <?= $priceRange !== 'all' ? 'open' : '' ?>>
                    <summary>Giá</summary>
                    <ul>
                        <li><a href="<?= htmlspecialchars(shopUrl(['price' => 'all', 'page' => 1])) ?>" class="<?= $priceRange === 'all' ? 'active' : '' ?>">Tất cả</a></li>
                        <li><a href="<?= htmlspecialchars(shopUrl(['price' => 'lt500k', 'page' => 1])) ?>" class="<?= $priceRange === 'lt500k' ? 'active' : '' ?>">Dưới 500.000 ₫</a></li>
                        <li><a href="<?= htmlspecialchars(shopUrl(['price' => '500kto1m5', 'page' => 1])) ?>" class="<?= $priceRange === '500kto1m5' ? 'active' : '' ?>">500.000 ₫ - 1.500.000 ₫</a></li>
                        <li><a href="<?= htmlspecialchars(shopUrl(['price' => 'gt1m5', 'page' => 1])) ?>" class="<?= $priceRange === 'gt1m5' ? 'active' : '' ?>">Trên 1.500.000 ₫</a></li>
                    </ul>
                </details>
            </aside>

            <div class="shop-results">
            <div class="shop-grid">
                <?php foreach ($products as $index => $product): ?>
                    <?php $imagePath = shopProductImageUrl($product['image'] ?? ''); ?>
                    <div class="shop-product-card" data-index="<?= $index ?>">
                        <a href="<?= BASE_URL ?>product?id=<?= (int)$product['id'] ?>" class="product-img-wrapper" style="position:relative;">
                            <?php $cPrice = (float)($product['compare_at_price'] ?? 0); $price = (float)$product['price']; if ($cPrice > $price): ?>
                                <span class="badge-tag tag-sale" style="position:absolute; top:10px; left:10px; background:#e11d48; color:white; font-size:0.75rem; padding:3px 8px; border-radius:4px; font-weight:bold; z-index:2;">-<?= round((($cPrice - $price) / $cPrice) * 100) ?>%</span>
                            <?php endif; ?>
                            <?php if ($imagePath !== ''): ?>
                                <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php endif; ?>
                            <div class="product-actions" onclick="event.preventDefault(); event.stopPropagation()">
                                <button class="btn-add-cart" onclick="goToProduct(<?= (int)$product['id'] ?>)">
                                    Chọn size & màu
                                </button>
                                <button class="btn-quick-view" onclick="openQuickView(<?= $index ?>)">Xem nhanh</button>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>product?id=<?= (int)$product['id'] ?>" class="product-info">
                            <span class="product-name"><?= htmlspecialchars($product['name']) ?></span>
                            <span class="product-type"><?= htmlspecialchars(productDisplayType($product)) ?></span>
                            <div class="product-price" style="margin-top:0.4rem;">
                                <?php $cPrice = (float)($product['compare_at_price'] ?? 0); $price = (float)$product['price']; if ($cPrice > $price): ?>
                                    <span style="color: #e11d48; font-weight: 700;"><?= number_format($price, 0, ',', '.') ?> VNĐ</span>
                                    <del style="color: #94a3b8; font-size: 0.85em; margin-left: 6px;"><?= number_format($cPrice, 0, ',', '.') ?> VNĐ</del>
                                <?php else: ?>
                                    <span style="font-weight: 600; color: #0f172a;"><?= number_format($price, 0, ',', '.') ?> VNĐ</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <p class="shop-empty">
                        <?= $keyword !== '' ? 'Không tìm thấy kết quả cho "' . htmlspecialchars($keyword) . '".' : 'Không có sản phẩm phù hợp bộ lọc.' ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <?php
                $visiblePages = array_unique(array_filter([1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $totalPages], static fn($number) => $number >= 1 && $number <= $totalPages));
                sort($visiblePages);
                $previousVisiblePage = null;
                ?>
                <nav class="shop-pagination" aria-label="Phân trang sản phẩm">
                    <?php if ($page > 1): ?>
                        <a class="pagination-direction" href="<?= htmlspecialchars(shopUrl(['page' => $page - 1])) ?>" rel="prev">← Trước</a>
                    <?php else: ?>
                        <span class="pagination-direction disabled">← Trước</span>
                    <?php endif; ?>
                    <?php foreach ($visiblePages as $pageNumber): ?>
                        <?php if ($previousVisiblePage !== null && $pageNumber > $previousVisiblePage + 1): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                        <a href="<?= htmlspecialchars(shopUrl(['page' => $pageNumber])) ?>" class="<?= $pageNumber === $page ? 'active' : '' ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                        <?php $previousVisiblePage = $pageNumber; ?>
                    <?php endforeach; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="pagination-direction" href="<?= htmlspecialchars(shopUrl(['page' => $page + 1])) ?>" rel="next">Sau →</a>
                    <?php else: ?>
                        <span class="pagination-direction disabled">Sau →</span>
                    <?php endif; ?>
                </nav>
                <p class="shop-pagination-summary">Hiển thị <?= (($page - 1) * $perPage) + 1 ?>–<?= min($page * $perPage, $totalFilteredProducts) ?> trong <?= (int)$totalFilteredProducts ?> sản phẩm</p>
            <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-sidebar-header">
        <h3>Giỏ hàng (<span id="cartCount">0</span>)</h3>
        <button class="cart-close-btn" onclick="toggleCart()">x</button>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-footer">
        <div class="cart-total">
            <span class="label">Tổng cộng</span>
            <span class="amount" id="cartTotal">0 VNĐ</span>
        </div>
        <button class="btn-checkout" onclick="checkout()">Thanh toán</button>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay" onclick="closeQuickView()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeQuickView()">x</button>
        <div class="modal-img">
            <img id="modalImg" src="<?= BASE_URL ?>assets/images/lam-placeholder.svg" alt="Ảnh xem nhanh sản phẩm">
        </div>
        <div class="modal-details">
            <h2 id="modalName"></h2>
            <p class="modal-category" id="modalCategory"></p>
            <p class="modal-price" id="modalPrice"></p>
            <p class="modal-desc">Sản phẩm đồ lam và vật dụng đi chùa. Vui lòng xem tên, màu sắc và phân loại trước khi đặt hàng.</p>
            <div class="modal-size-select">
                <label id="modalSizeLabel">Chọn size</label>
                <div class="size-options" id="modalSizeOptions"></div>
            </div>
            <button class="btn-add-cart-modal" id="modalAddBtn">Thêm vào giỏ hàng</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
const productsData = <?= json_encode(array_map(function ($product) {
    $product['size_options'] = productSizeOptions($product);
    return $product;
}, array_values($products)), JSON_UNESCAPED_UNICODE) ?>;
let cart = [];

function productImagePath(image) {
    if (!image) return '';
    if (image.startsWith('public/uploads/')) return image;
    if (image.startsWith('uploads/')) return 'public/' + image;
    return 'assets/images/' + image;
}

function assetUrl(image) {
    if (!image) return '';
    return image.startsWith('http') ? image : BASE_URL + productImagePath(image);
}

function productDisplayType(product) {
    const type = String(product.type || '').trim();
    if (!type || type === '0' || type.includes('?')) {
        return String(product.category || '').trim();
    }

    return type;
}

function openQuickView(index) {
    const product = productsData[index];
    if (!product) return;

    document.getElementById('modalImg').src = assetUrl(product.image || '');
    document.getElementById('modalImg').alt = product.name;
    document.getElementById('modalName').textContent = product.name;
    document.getElementById('modalCategory').textContent = productDisplayType(product);
    document.getElementById('modalPrice').textContent = formatPrice(product.price);
    const sizeOptions = product.size_options || ['Free Size'];
    const isMillimeterSize = sizeOptions.some(size => size.endsWith(' mm'));
    document.getElementById('modalSizeLabel').textContent = isMillimeterSize ? 'Chọn kích thước (mm)' : 'Chọn size';
    document.getElementById('modalSizeOptions').innerHTML = sizeOptions
        .map(size => `<button class="size-btn" onclick="selectSize(this)">${size}</button>`).join('');

    document.getElementById('modalAddBtn').onclick = () => {
        addToCart(product.id);
        closeQuickView();
    };

    document.getElementById('modalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeQuickView() {
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

function selectSize(btn) {
    document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
}

function loadCart() {
    fetch(BASE_URL + 'cart/get')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                cart = data.items.map(item => ({
                    cart_id: item.id,
                    product_id: item.product_id,
                    variant_id: item.variant_id,
                    size: item.size,
                    color: item.color,
                    name: item.name,
                    price: parseFloat(item.price),
                    qty: parseInt(item.quantity),
                    image: item.image_url
                }));
                updateCartUI(data.cart_count);
            }
        });
}

function addToCart(productId) {
    goToProduct(productId);
}

function goToProduct(productId) {
    window.location.href = BASE_URL + 'product?id=' + productId;
}

function removeFromCart(cartId) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    fetch(BASE_URL + 'cart/remove', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(r => r.json()).then(data => {
        if (data.success) {
            loadCart();
            if (typeof window.updateBadgeGlobal === 'function') window.updateBadgeGlobal(data.cart_count);
        }
    });
}

function updateQty(cartId, newQty) {
    if (newQty < 1) {
        removeFromCart(cartId);
        return;
    }
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('qty', newQty);
    fetch(BASE_URL + 'cart/update', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(r => r.json()).then(data => {
        if (data.success) {
            loadCart();
            if (typeof window.updateBadgeGlobal === 'function') window.updateBadgeGlobal(data.cart_count);
        }
    });
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
}

function updateCartUI(totalItems = 0) {
    const cartItems = document.getElementById('cartItems');
    const cartCount = document.getElementById('cartCount');
    const cartTotal = document.getElementById('cartTotal');
    const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

    cartCount.textContent = totalItems;
    cartTotal.textContent = formatPrice(totalPrice);

    if (cart.length === 0) {
        cartItems.innerHTML = '<div class="cart-empty"><p>Giỏ hàng trống</p></div>';
        return;
    }

    cartItems.innerHTML = cart.map(item => {
        const imgUrl = item.image ? (item.image.startsWith('http') ? item.image : (item.image.startsWith('public/uploads/') ? BASE_URL + item.image : BASE_URL + (item.image.startsWith('uploads/') ? 'public/' : 'assets/images/') + item.image)) : '';
        return `
        <div class="cart-item">
            <img src="${imgUrl}" alt="${item.name}">
            <div class="cart-item-info">
                <div class="item-name">${item.name}</div>
                    <div class="item-price">${formatPrice(item.price)}<br><small>Size: ${item.size || 'Mặc định'} · Màu: ${item.color || 'Mặc định'}</small></div>
                <div class="cart-item-qty">
                    <button onclick="updateQty(${item.cart_id}, ${item.qty - 1})">-</button>
                    <span>${item.qty}</span>
                    <button onclick="updateQty(${item.cart_id}, ${item.qty + 1})">+</button>
                </div>
            </div>
            <button class="cart-item-remove" onclick="removeFromCart(${item.cart_id})">x</button>
        </div>
    `}).join('');
}

function toggleCart(forceOpen) {
    const sidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('cartOverlay');
    const isOpen = sidebar.classList.contains('active');
    const nextOpen = forceOpen === true ? true : !isOpen;

    document.body.style.overflow = nextOpen ? 'hidden' : '';

    if (forceOpen === true) {
        sidebar.classList.add('active');
        overlay.classList.add('active');
    } else {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
}

function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
}

function checkout() {
    if (cart.length === 0) {
        showToast('Giỏ hàng trống!');
        return;
    }
    window.location.href = BASE_URL + 'checkout';
}

document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
    const cartIcon = document.querySelector('a[href="<?= BASE_URL ?>cart"]');
    if (cartIcon) {
        cartIcon.addEventListener('click', (e) => {
            e.preventDefault();
            toggleCart();
        });
    }
});
document.addEventListener('DOMContentLoaded', () => {
    loadCart();
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
