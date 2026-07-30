<?php include __DIR__ . '/partials/header.php'; ?>

<?php
$gender = isset($_GET['gender']) ? $_GET['gender'] : 'all';
$genderLabel = 'Tất cả sản phẩm';
if ($gender === 'men') $genderLabel = 'Sản phẩm Nam';
if ($gender === 'women') $genderLabel = 'Sản phẩm Nữ';

$category = $_GET['category'] ?? 'all';
$sort = $_GET['sort'] ?? 'default';
$priceRange = $_GET['price'] ?? 'all';
$keyword = trim($_GET['q'] ?? '');

function shopUrl(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    return BASE_URL . 'shop?' . http_build_query($params);
}

function productAssetPath($image): string {
    $image = (string)$image;
    if ($image === '') return '';
    if (str_starts_with($image, 'public/uploads/')) return $image;
    if (str_starts_with($image, 'uploads/')) return 'public/' . $image;
    return 'assets/images/' . $image;
}

function productDisplayType($product): string {
    $type = trim((string)($product['type'] ?? ''));
    if ($type === '' || $type === '0' || strpos($type, '?') !== false) {
        return trim((string)($product['category'] ?? ''));
    }

    return $type;
}
?>

<main>
    <section class="shop-page">
        <div class="shop-topbar">
            <h1><?= htmlspecialchars($genderLabel) ?> (<?= count($products) ?>)</h1>
            <div class="shop-sort">
                <form method="get" action="<?= BASE_URL ?>shop" id="sortForm">
                    <input type="hidden" name="gender" value="<?= htmlspecialchars($gender) ?>">
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
                    <li><a href="<?= htmlspecialchars(shopUrl(['category' => 'all'])) ?>" class="<?= $category === 'all' ? 'active' : '' ?>">Tất cả</a></li>
                    <?php foreach ($categories as $c): ?>
                        <li><a href="<?= htmlspecialchars(shopUrl(['category' => $c['name']])) ?>" class="<?= $category === $c['name'] ? 'active' : '' ?>"><?= htmlspecialchars($c['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>

                <details class="filter-group" <?= $gender !== 'all' ? 'open' : '' ?>>
                    <summary>Giới tính</summary>
                    <ul>
                        <li><a href="<?= htmlspecialchars(shopUrl(['gender' => 'all'])) ?>" class="<?= $gender === 'all' ? 'active' : '' ?>">Tất cả</a></li>
                        <li><a href="<?= htmlspecialchars(shopUrl(['gender' => 'men'])) ?>" class="<?= $gender === 'men' ? 'active' : '' ?>">Nam</a></li>
                        <li><a href="<?= htmlspecialchars(shopUrl(['gender' => 'women'])) ?>" class="<?= $gender === 'women' ? 'active' : '' ?>">Nữ</a></li>
                    </ul>
                </details>

                <details class="filter-group" <?= $priceRange !== 'all' ? 'open' : '' ?>>
                    <summary>Giá</summary>
                    <ul>
                        <li><a href="<?= htmlspecialchars(shopUrl(['price' => 'all'])) ?>" class="<?= $priceRange === 'all' ? 'active' : '' ?>">Tất cả</a></li>
                        <li><a href="<?= htmlspecialchars(shopUrl(['price' => 'lt3'])) ?>" class="<?= $priceRange === 'lt3' ? 'active' : '' ?>">Dưới 3.000.000 VNĐ</a></li>
                        <li><a href="<?= htmlspecialchars(shopUrl(['price' => '3to5'])) ?>" class="<?= $priceRange === '3to5' ? 'active' : '' ?>">3.000.000 - 5.000.000 VNĐ</a></li>
                        <li><a href="<?= htmlspecialchars(shopUrl(['price' => 'gt5'])) ?>" class="<?= $priceRange === 'gt5' ? 'active' : '' ?>">Trên 5.000.000 VNĐ</a></li>
                    </ul>
                </details>
            </aside>

            <div class="shop-grid">
                <?php foreach ($products as $index => $product): ?>
                    <?php $imagePath = productAssetPath($product['image'] ?? ''); ?>
                    <div class="shop-product-card" data-index="<?= $index ?>">
                        <a href="<?= BASE_URL ?>product?id=<?= (int)$product['id'] ?>" class="product-img-wrapper">
                            <?php if ($imagePath !== ''): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php endif; ?>
                            <div class="product-actions" onclick="event.preventDefault(); event.stopPropagation()">
                                <button class="btn-add-cart" onclick="addToCart(<?= (int)$product['id'] ?>)">
                                    Thêm vào giỏ
                                </button>
                                <button class="btn-quick-view" onclick="openQuickView(<?= $index ?>)">Xem nhanh</button>
                            </div>
                        </a>
                        <a href="<?= BASE_URL ?>product?id=<?= (int)$product['id'] ?>" class="product-info">
                            <span class="product-name"><?= htmlspecialchars($product['name']) ?></span>
                            <span class="product-type"><?= htmlspecialchars(productDisplayType($product)) ?></span>
                            <span class="product-price"><?= number_format((float)$product['price'], 0, ',', '.') ?> VNĐ</span>
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <p class="shop-empty">
                        <?= $keyword !== '' ? 'Không tìm thấy kết quả cho "' . htmlspecialchars($keyword) . '".' : 'Không có sản phẩm phù hợp bộ lọc.' ?>
                    </p>
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
            <img id="modalImg" src="" alt="">
        </div>
        <div class="modal-details">
            <h2 id="modalName"></h2>
            <p class="modal-category" id="modalCategory"></p>
            <p class="modal-price" id="modalPrice"></p>
            <p class="modal-desc">Sản phẩm Nike chính hãng. Cam kết chất lượng và bảo hành đầy đủ.</p>
            <div class="modal-size-select">
                <label>Chọn size</label>
                <div class="size-options">
                    <?php foreach (['38','39','40','41','42','43','44'] as $size): ?>
                        <button class="size-btn" onclick="selectSize(this)"><?= $size ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="btn-add-cart-modal" id="modalAddBtn">Thêm vào giỏ hàng</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const productsData = <?= json_encode(array_values($products), JSON_UNESCAPED_UNICODE) ?>;
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

    document.getElementById('modalImg').src = BASE_URL + productImagePath(product.image || '');
    document.getElementById('modalImg').alt = product.name;
    document.getElementById('modalName').textContent = product.name;
    document.getElementById('modalCategory').textContent = productDisplayType(product);
    document.getElementById('modalPrice').textContent = formatPrice(product.price);

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
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('qty', 1);

    fetch(BASE_URL + 'cart/add', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(r => r.json()).then(data => {
        if (data.success) {
            showToast('Đã thêm vào giỏ hàng!');
            loadCart();
            toggleCart(true);
            if (typeof window.updateBadgeGlobal === 'function') window.updateBadgeGlobal(data.cart_count);
        } else {
            showToast(data.message || 'Lỗi!');
        }
    });
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
                <div class="item-price">${formatPrice(item.price)}</div>
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
