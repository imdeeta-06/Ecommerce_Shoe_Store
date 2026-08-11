<?php include __DIR__ . '/partials/header.php'; ?>

<?php
function homeAssetUrl($image): string {
    $image = trim((string)$image);
    if ($image === '') return BASE_URL . 'assets/images/placeholder.jpg';
    if (preg_match('/^https?:\/\//i', $image)) return $image;
    if (str_starts_with($image, 'public/')) return BASE_URL . $image;
    if (str_starts_with($image, 'uploads/')) return BASE_URL . 'public/' . $image;
    if (str_starts_with($image, 'assets/')) return BASE_URL . $image;
    return BASE_URL . 'assets/images/' . $image;
}
?>

<main>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-content">
                <span class="hero-subtitle">🌸 NƠI HỘI TỤ TÂM LÀNH</span>
                <h1 class="hero-title">Pháp Phục<br>Phật Giáo<br>Cao Cấp</h1>
                <p class="hero-desc">Chuyên cung cấp áo lam, tràng hạt, tượng thờ và vật phẩm tâm linh Phật giáo chất lượng cao. Phục vụ quý Phật tử với tâm từ bi và sự tận tâm.</p>
                <div class="hero-buttons">
                    <a href="<?= BASE_URL ?>shop" class="btn-primary">Khám phá ngay</a>
                    <a href="<?= BASE_URL ?>about" class="btn-secondary">Tìm hiểu thêm</a>
                </div>
            </div>
            <div class="hero-image-wrapper">
                <img src="<?= BASE_URL ?>assets/images/lam-hero-banner.webp" alt="Pháp phục Phật giáo Liên Hoa" class="hero-img">
            </div>
        </div>
    </section>

    <!-- Features Bar -->
    <section class="features-bar">
        <div class="feature-item">
            <span class="feature-icon">🚚</span>
            <div class="feature-text">
                <h4>Miễn phí vận chuyển</h4>
                <p>Đơn từ 500.000đ</p>
            </div>
        </div>
        <div class="feature-item">
            <span class="feature-icon">⭐</span>
            <div class="feature-text">
                <h4>Hàng chính hãng 100%</h4>
                <p>Cam kết chất lượng</p>
            </div>
        </div>
        <div class="feature-item">
            <span class="feature-icon">🔄</span>
            <div class="feature-text">
                <h4>Đổi trả 7 ngày</h4>
                <p>Không câu nệ</p>
            </div>
        </div>
        <div class="feature-item">
            <span class="feature-icon">📞</span>
            <div class="feature-text">
                <h4>Hỗ trợ 7:00 - 21:00</h4>
                <p>Tận tâm phục vụ</p>
            </div>
        </div>
    </section>

    <!-- Category Section -->
    <section class="category-section">
        <h2 class="section-title">Danh Mục Sản Phẩm</h2>
        <div class="category-grid">
            <a href="<?= BASE_URL ?>shop?category=Đồ+lam+đi+chùa" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1025.jpg" alt="Pháp Phục - Đồ Lam">
                <div class="category-overlay">
                    <h3>Pháp Phục - Đồ Lam</h3>
                    <p>48 sản phẩm</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop?category=Vòng+tay+-+chuỗi+hạt" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1100.jpg" alt="Tràng Hạt">
                <div class="category-overlay">
                    <h3>Tràng Hạt</h3>
                    <p>32 sản phẩm</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop?category=Phụ+kiện+đi+chùa" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1125.jpg" alt="Tượng Thờ">
                <div class="category-overlay">
                    <h3>Tượng Thờ</h3>
                    <p>24 sản phẩm</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop" class="category-card">
                <img src="<?= BASE_URL ?>assets/images/lam-hero-pagoda.jpg" alt="Kinh Sách">
                <div class="category-overlay">
                    <h3>Kinh Sách</h3>
                    <p>15 sản phẩm</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop" class="category-card">
                <img src="<?= BASE_URL ?>assets/images/lam-hero-lanterns.jpg" alt="Hương & Nến">
                <div class="category-overlay">
                    <h3>Hương & Nến</h3>
                    <p>12 sản phẩm</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop?category=Túi+đeo+đi+chùa" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1075.webp" alt="Vật Phẩm Khác">
                <div class="category-overlay">
                    <h3>Vật Phẩm Thờ</h3>
                    <p>19 sản phẩm</p>
                </div>
            </a>
        </div>
    </section>

    <!-- Sản phẩm Mới -->
    <section class="products-section">
        <div class="section-header-flex">
            <h2 class="section-title-elegant">Sản Phẩm Mới</h2>
            <a href="<?= BASE_URL ?>shop" class="btn-text-link">Xem tất cả →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card" onclick="goToProduct(<?= (int)$product['id'] ?>)">
                <div class="product-img-wrapper" style="border-radius:16px;">
                    <span class="badge-tag tag-new">MỚI</span>
                    <img src="<?= BASE_URL . htmlspecialchars(productAssetPath($product['image'] ?? '')) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                </div>
                <div class="product-info-new">
                    <span class="product-category-new"><?= htmlspecialchars($product['category']) ?></span>
                    <h3 class="product-title-new"><?= htmlspecialchars($product['name']) ?></h3>
                    <div class="product-stars">★★★★★ <span class="stars-count">(28)</span></div>
                    <div class="product-price-new"><?= number_format($product['price'], 0, ',', '.') ?> ₫</div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($featuredProducts)): ?><p style="grid-column:1/-1;text-align:center;color:#666;">Chưa có sản phẩm nổi bật.</p><?php endif; ?>
        </div>
    </section>

    <!-- Special Collection Full-Width Banner -->
    <section class="promo-banner-section" style="background-image: linear-gradient(rgba(62, 39, 35, 0.65), rgba(62, 39, 35, 0.65)), url('<?= BASE_URL ?>assets/images/lam-hero-courtyard.jpg');">
        <div class="promo-banner-content">
            <span class="promo-subtitle">BỘ SƯU TẬP ĐẶC BIỆT</span>
            <h2>Tràng Hạt & Pháp Cụ<br>Chính Hãng Cao Cấp</h2>
            <p>Được chọn lọc kỹ lưỡng từ những nghệ nhân uy tín, mang đến nguồn năng lượng tích cực và thanh tịnh cho người sử dụng.</p>
            <button class="btn-gold" onclick="window.location.href='<?= BASE_URL ?>shop?category=Vòng+tay+-+chuỗi+hạt'">Khám phá bộ sưu tập</button>
        </div>
    </section>

    <!-- Sản phẩm Bán Chạy -->
    <section class="products-section bg-beige-light">
        <div class="section-header-flex">
            <h2 class="section-title-elegant">Sản Phẩm Bán Chạy</h2>
            <a href="<?= BASE_URL ?>shop" class="btn-text-link">Xem tất cả →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($bestSellingProducts as $product): ?>
            <div class="product-card" onclick="goToProduct(<?= (int)$product['id'] ?>)">
                <div class="product-img-wrapper" style="border-radius:16px;">
                    <span class="badge-tag tag-hot">BÁN CHẠY</span>
                    <img src="<?= htmlspecialchars(homeAssetUrl($product['image'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                </div>
                <div class="product-info-new">
                    <span class="product-category-new"><?= htmlspecialchars($product['category'] ?? '') ?></span>
                    <h3 class="product-title-new"><?= htmlspecialchars($product['name']) ?></h3>
                    <div class="product-stars">★★★★★ <span class="stars-count">(36)</span></div>
                    <div class="product-price-new"><?= number_format((float)$product['price'], 0, ',', '.') ?> ₫</div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($bestSellingProducts)): ?><p style="grid-column:1/-1;text-align:center;color:#666;">Chưa có dữ liệu sản phẩm bán chạy.</p><?php endif; ?>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <span class="section-subtitle-center">PHẬT TỬ NÓI GÌ</span>
        <h2 class="section-title-center">Cảm Nhận Khách Hàng</h2>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-user">
                    <div class="user-avatar-text">NH</div>
                    <div>
                        <h4>Nguyễn Thị Hoa</h4>
                        <span class="testimonial-date">15/07/2026</span>
                    </div>
                </div>
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-text">"Áo lam chất lượng rất tốt, may đẹp, vải mềm mịn. Mặc đi chùa rất trang nghiêm. Shop giao hàng nhanh, đóng gói cẩn thận. Sẽ ủng hộ shop dài dài!"</p>
                <span class="testimonial-product">Sản phẩm: Áo lam nữ vải lanh cao cấp</span>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-user">
                    <div class="user-avatar-text">TM</div>
                    <div>
                        <h4>Trần Văn Minh</h4>
                        <span class="testimonial-date">28/07/2026</span>
                    </div>
                </div>
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-text">"Tràng hạt gỗ trầm hương tự nhiên rất dễ chịu. Hạt đều, dây bền. Đây là lần thứ 3 mình mua tại đây, lần nào cũng hài lòng với chất lượng và dịch vụ."</p>
                <span class="testimonial-product">Sản phẩm: Tràng hạt gỗ trầm hương 108 hạt</span>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-user">
                    <div class="user-avatar-text">PL</div>
                    <div>
                        <h4>Phạm Thị Lan</h4>
                        <span class="testimonial-date">01/08/2026</span>
                    </div>
                </div>
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-text">"Website rất đẹp, sản phẩm phong phú. Tượng Quan Âm mình mua được đúc rất tinh xảo, đẹp hơn hình nhiều. Rất hài lòng với dịch vụ tại Liên Hoa!"</p>
                <span class="testimonial-product">Sản phẩm: Tượng Phật Quan Âm Bồ Tát</span>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section class="blog-section bg-beige-light">
        <span class="section-subtitle-center">KIẾN THỨC PHẬT PHÁP</span>
        <h2 class="section-title-center" style="margin-bottom:3rem;">Tin Tức & Blog</h2>
        <div class="blog-grid">
            <article class="blog-card">
                <div class="blog-img-wrapper">
                    <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1025.jpg" alt="Hướng dẫn chọn áo lam">
                    <span class="blog-tag">Hướng dẫn</span>
                </div>
                <div class="blog-info">
                    <span class="blog-date">12/07/2026 · 3 phút đọc</span>
                    <h3><a href="#">Hướng dẫn chọn áo lam đúng kích thước và phong cách</a></h3>
                    <p>Áo lam là trang phục truyền thống của Phật tử tại gia Việt Nam. Bài viết này sẽ hướng dẫn bạn cách chọn áo lam phù hợp nhất với vóc dáng v...</p>
                </div>
            </article>
            <article class="blog-card">
                <div class="blog-img-wrapper">
                    <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1100.jpg" alt="Ý nghĩa tràng hạt">
                    <span class="blog-tag">Kiến thức</span>
                </div>
                <div class="blog-info">
                    <span class="blog-date">25/07/2026 · 5 phút đọc</span>
                    <h3><a href="#">Ý nghĩa và công dụng của tràng hạt trong Phật giáo</a></h3>
                    <p>Tràng hạt không chỉ là vật phẩm tâm linh mà còn là công cụ quan trọng trong thiền định và tụng kinh. Tìm hiểu ý nghĩa sâu sắc của tràng hạt...</p>
                </div>
            </article>
            <article class="blog-card">
                <div class="blog-img-wrapper">
                    <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1125.jpg" alt="Chăm sóc tượng Phật">
                    <span class="blog-tag">Bảo quản</span>
                </div>
                <div class="blog-info">
                    <span class="blog-date">02/08/2026 · 4 phút đọc</span>
                    <h3><a href="#">Cách chăm sóc và bảo quản tượng Phật đúng cách</a></h3>
                    <p>Tượng Phật cần được thờ phụng và chăm sóc đúng cách để giữ được vẻ đẹp và sự linh ứng. Những lưu ý quan trọng bạn cần biết khi thờ tượng t...</p>
                </div>
            </article>
        </div>
    </section>
</main>

<script>
// ===== NO LIFESTYLE SLIDER OR HERO SLIDESHOW SCRIPTS NEEDED =====

// ===== CART SYSTEM (Database) =====
let cart = [];

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
    return new Intl.NumberFormat('vi-VN').format(price) + ' ₫';
}

function updateCartUI(totalItems = 0) {
    const cartItems = document.getElementById('cartItems');
    const cartCount = document.getElementById('cartCount');
    const cartTotal = document.getElementById('cartTotal');
    const badges = document.querySelectorAll('.cart-badge');

    const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

    cartCount.textContent = totalItems;
    cartTotal.textContent = formatPrice(totalPrice);

    // Build cart items HTML
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="cart-empty">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                <p>Giỏ hàng trống</p>
            </div>`;
    } else {
        cartItems.innerHTML = cart.map(item => {
            const imgUrl = item.image ? (item.image.startsWith('http') ? item.image : (item.image.startsWith('public/uploads/') ? BASE_URL + item.image : BASE_URL + (item.image.startsWith('uploads/') ? 'public/' : 'assets/images/') + item.image)) : '';
            return `
            <div class="cart-item">
                <img src="${imgUrl}" alt="${item.name}">
                <div class="cart-item-info">
                    <div class="item-name">${item.name}</div>
                    <div class="item-price">${formatPrice(item.price)}<br><small>Size: ${item.size || 'Mặc định'} · Màu: ${item.color || 'Mặc định'}</small></div>
                    <div class="cart-item-qty">
                        <button onclick="updateQty(${item.cart_id}, ${item.qty - 1})">−</button>
                        <span>${item.qty}</span>
                        <button onclick="updateQty(${item.cart_id}, ${item.qty + 1})">+</button>
                    </div>
                </div>
                <button class="cart-item-remove" onclick="removeFromCart(${item.cart_id})">✕</button>
            </div>
        `}).join('');
    }
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

// Init cart UI on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();

    // Attach cart toggle to cart icon in header
    const cartIcon = document.querySelector('a[href="<?= BASE_URL ?>cart"]');
    if (cartIcon) {
        cartIcon.addEventListener('click', (e) => {
            e.preventDefault();
            toggleCart();
        });
        // Add badge if not exists
        if (!cartIcon.querySelector('.cart-badge')) {
            const badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.style.display = 'none';
            badge.textContent = '0';
            cartIcon.appendChild(badge);
        }
        updateCartUI();
    }
});
document.addEventListener('DOMContentLoaded', () => {
    // Only load if on homepage
    if (document.getElementById('cartItems')) {
        loadCart();
    }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
