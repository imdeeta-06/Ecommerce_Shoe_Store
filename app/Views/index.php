<?php include __DIR__ . '/partials/header.php'; ?>

<main>
    <!-- Hero Slideshow -->
    <section class="hero-slideshow" id="heroSlideshow">
        <div class="hero-slide active" style="background-image: url('<?= BASE_URL ?>assets/images/hero1.jpg')"></div>
        <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>assets/images/hero2..avif')"></div>
        <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>assets/images/hero3.avif')"></div>
        <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>assets/images/hero4.avif')"></div>
        <div class="hero-slide" style="background-image: url('<?= BASE_URL ?>assets/images/hero5.avif')"></div>

        <!-- Dots -->
        <div class="hero-dots">
            <button class="hero-dot active" data-slide="0"></button>
            <button class="hero-dot" data-slide="1"></button>
            <button class="hero-dot" data-slide="2"></button>
            <button class="hero-dot" data-slide="3"></button>
            <button class="hero-dot" data-slide="4"></button>
        </div>

        <!-- Arrows -->
        <button class="hero-arrow prev" id="heroPrev">&#10094;</button>
        <button class="hero-arrow next" id="heroNext">&#10095;</button>
    </section>

    <!-- Giới thiệu -->
    <section class="intro-section">
        <h2>Chính hãng 100%</h2>
        <p>Chúng tôi chuyên phân phối giày Nike chính hãng — cam kết nguồn gốc rõ ràng, chất lượng đảm bảo và bảo hành đầy đủ. Mang đến cho bạn trải nghiệm mua sắm uy tín cùng các bộ sưu tập mới nhất.</p>
    </section>

    <!-- Sản phẩm mới -->
    <section class="products-section">
        <h2>Sản phẩm mới</h2>
        <div class="product-grid">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <a href="<?= BASE_URL ?>product?id=<?= $product['id'] ?>" class="product-img-wrapper" style="display: block;">
                    <img src="<?= BASE_URL . htmlspecialchars(productAssetPath($product['image'] ?? '')) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                </a>
                <div class="product-info">
                    <a href="<?= BASE_URL ?>product?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit;"><span class="product-title"><?= htmlspecialchars($product['name']) ?></span></a>
                    <span class="product-category"><?= htmlspecialchars($product['category']) ?></span>
                    <div class="product-price"><?= number_format($product['price'], 0, ',', '.') ?> ₫</div>
                    <button class="btn-buy" onclick="addToCart(<?= $product['id'] ?>)">Mua ngay</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Lifestyle Gallery - Horizontal Image Slider -->
    <section class="lifestyle-section">
        <h2>Khám phá thế giới Nike</h2>
        <div class="lifestyle-slider-wrapper">
            <div class="lifestyle-slider" id="lifestyleSlider">
                <div class="lifestyle-slide">
                    <img src="<?= BASE_URL ?>assets/images/running.png" alt="Running">
                    <div class="slide-overlay">
                        <h3>Running</h3>
                        <p>Mang lại tốc độ và sự thoải mái</p>
                    </div>
                </div>
                <div class="lifestyle-slide">
                    <img src="<?= BASE_URL ?>assets/images/football.png" alt="Football">
                    <div class="slide-overlay">
                        <h3>Football</h3>
                        <p>Sẵn sàng cho mọi trận đấu</p>
                    </div>
                </div>
                <div class="lifestyle-slide">
                    <img src="<?= BASE_URL ?>assets/images/training.png" alt="Training">
                    <div class="slide-overlay">
                        <h3>Training</h3>
                        <p>Đột phá giới hạn của bạn</p>
                    </div>
                </div>
                <div class="lifestyle-slide">
                    <img src="<?= BASE_URL ?>assets/images/lifestyle.jpg" alt="Lifestyle">
                    <div class="slide-overlay">
                        <h3>Lifestyle</h3>
                        <p>Phong cách vượt thời gian</p>
                    </div>
                </div>
                <div class="lifestyle-slide">
                    <img src="<?= BASE_URL ?>assets/images/skate.png" alt="Skateboarding">
                    <div class="slide-overlay">
                        <h3>Skateboarding</h3>
                        <p>Sự linh hoạt tuyệt đối</p>
                    </div>
                </div>
            </div>
            <div class="lifestyle-nav">
                <button onclick="scrollLifestyle(-1)" aria-label="Previous">&#10094;</button>
                <button onclick="scrollLifestyle(1)" aria-label="Next">&#10095;</button>
            </div>
        </div>
    </section>
</main>

<!-- Cart Sidebar -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-sidebar-header">
        <h3>Giỏ hàng (<span id="cartCount">0</span>)</h3>
        <button class="cart-close-btn" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-items" id="cartItems">
        <div class="cart-empty" id="cartEmpty">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            <p>Giỏ hàng trống</p>
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-total">
            <span class="label">Tổng cộng</span>
            <span class="amount" id="cartTotal">0 ₫</span>
        </div>
        <button class="btn-checkout" onclick="checkout()">Thanh toán</button>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ===== HERO SLIDESHOW =====
(function() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    let current = 0;
    let autoSlide;

    function goToSlide(index) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (index + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    function nextSlide() { goToSlide(current + 1); }
    function prevSlide() { goToSlide(current - 1); }

    function startAuto() {
        autoSlide = setInterval(nextSlide, 5000);
    }
    function resetAuto() {
        clearInterval(autoSlide);
        startAuto();
    }

    document.getElementById('heroNext').addEventListener('click', () => { nextSlide(); resetAuto(); });
    document.getElementById('heroPrev').addEventListener('click', () => { prevSlide(); resetAuto(); });
    dots.forEach(dot => {
        dot.addEventListener('click', () => { goToSlide(parseInt(dot.dataset.slide)); resetAuto(); });
    });

    startAuto();
})();

// ===== LIFESTYLE SLIDER =====
function scrollLifestyle(direction) {
    const slider = document.getElementById('lifestyleSlider');
    const slideWidth = slider.querySelector('.lifestyle-slide').offsetWidth + 24;
    slider.scrollBy({ left: direction * slideWidth, behavior: 'smooth' });
}

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
                    <div class="item-price">${formatPrice(item.price)}</div>
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
