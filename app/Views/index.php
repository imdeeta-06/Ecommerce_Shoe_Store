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

$hasRecordedSales = false;
foreach (($bestSellingProducts ?? []) as $salesProduct) {
    if ((int)($salesProduct['sold_count'] ?? 0) > 0) {
        $hasRecordedSales = true;
        break;
    }
}
?>

<main>
    <!-- Hero Section -->
    <section class="hero-section hero-fullwidth">
        <div class="hero-container">
            <div class="hero-content">
                <span class="hero-subtitle">PHÁP PHỤC · ĐỒ LAM · TÚI XÁCH ĐI CHÙA</span>
                <h1 class="hero-title">Đồ Lam Đi Chùa &amp; Pháp Phục<br>Cao Cấp &amp; Thanh Tịnh</h1>
                <p class="hero-desc">Chuyên cung cấp đồ lam đi chùa, pháp phục Tăng – Ni, túi xách đi chùa, tràng hạt và vật phẩm tâm linh cao cấp. Chất liệu vải đũi &amp; lanh tự nhiên, may đo chuẩn mực từng đường kim.</p>
                <div class="hero-buttons">
                    <a href="<?= BASE_URL ?>shop" class="btn-primary">Khám phá bộ sưu tập →</a>
                    <a href="<?= BASE_URL ?>shop?category=Túi+đeo+đi+chùa" class="btn-secondary">Túi xách đi chùa</a>
                </div>
            </div>
        </div>
        <div class="hero-stats-bar">
            <div class="hero-stat-item">
                <span class="stat-number"><?= number_format($heroStats['product_count'] ?? 0) ?>+</span>
                <span class="stat-label">Sản phẩm</span>
            </div>
            <div class="stat-divider"></div>
            <div class="hero-stat-item">
                <span class="stat-number"><?= $heroStats['category_count'] ?? 0 ?></span>
                <span class="stat-label">Danh mục</span>
            </div>
            <div class="stat-divider"></div>
            <div class="hero-stat-item">
                <span class="stat-number">7</span>
                <span class="stat-label">Ngày đổi trả</span>
            </div>
        </div>
    </section>

    <section class="commerce-trust-strip" aria-label="Cam kết mua hàng tại Liên Hoa">
        <div><strong>Giao hàng linh hoạt</strong><span>Tính phí minh bạch theo khu vực</span></div>
        <div><strong>Đổi trả trong 7 ngày</strong><span>Hỗ trợ đổi size đúng điều kiện</span></div>
        <div><strong>Thanh toán rõ ràng</strong><span>COD, VietQR hoặc PayPal</span></div>
        <div><strong>Tư vấn tận tâm</strong><span>07:00–21:00 hằng ngày</span></div>
    </section>

    <!-- Category Section -->
    <section class="category-section">
        <span class="section-subtitle-center">MUA SẮM THEO NHU CẦU</span>
        <h2 class="section-title">Danh Mục Nổi Bật</h2>
        <div class="category-grid">
            <a href="<?= BASE_URL ?>shop?category=Đồ+lam+đi+chùa" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1025.jpg" alt="Đồ Lam Đi Chùa">
                <div class="category-overlay">
                    <h3>Đồ Lam Đi Chùa</h3>
                    <p>Trang nghiêm · Kín đáo</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop?category=Quần+áo+Tăng+-+Ni" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1000.jpg" alt="Pháp Phục Tăng - Ni">
                <div class="category-overlay">
                    <h3>Pháp Phục Tăng - Ni</h3>
                    <p>Áo tràng · Hải thanh</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop?category=Túi+đeo+đi+chùa" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1075.webp" alt="Túi Xách & Túi Đeo Đi Chùa">
                <div class="category-overlay">
                    <h3>Túi Xách Đi Chùa</h3>
                    <p>Thêu hoa sen · Tiện dụng</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop?category=Quần+áo+ngồi+thiền" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1050.jpg" alt="Quần Áo Ngồi Thiền">
                <div class="category-overlay">
                    <h3>Quần Áo Ngồi Thiền</h3>
                    <p>Vải đũi mộc · Thoáng khí</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop?category=Vòng+tay+-+chuỗi+hạt" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1100.jpg" alt="Vòng Tay & Chuỗi Hạt">
                <div class="category-overlay">
                    <h3>Tràng Hạt &amp; Chuỗi Niệm</h3>
                    <p>Trầm hương · Gỗ bách xanh</p>
                </div>
            </a>
            <a href="<?= BASE_URL ?>shop?category=Phụ+kiện+đi+chùa" class="category-card">
                <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1125.jpg" alt="Phụ Kiện Đi Chùa">
                <div class="category-overlay">
                    <h3>Phụ Kiện &amp; Pháp Cụ</h3>
                    <p>Tượng thờ · Khánh treo</p>
                </div>
            </a>
        </div>
    </section>

    <!-- Sản phẩm bán chạy: ưu tiên bằng chứng nhu cầu trước sản phẩm mới -->
    <section class="products-section">
        <div class="section-header-flex">
            <div>
                <span class="section-eyebrow"><?= $hasRecordedSales ? 'ĐƯỢC NHIỀU KHÁCH HÀNG LỰA CHỌN' : 'GỢI Ý MUA SẮM' ?></span>
                <h2 class="section-title-elegant"><?= $hasRecordedSales ? 'Sản Phẩm Bán Chạy' : 'Sản Phẩm Được Đề Xuất' ?></h2>
            </div>
            <a href="<?= BASE_URL ?>shop" class="btn-text-link">Xem tất cả →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($bestSellingProducts as $product): ?>
            <div class="product-card" onclick="goToProduct(<?= (int)$product['id'] ?>)">
                <div class="product-img-wrapper" style="border-radius:16px;">
                    <?php $cPrice = (float)($product['compare_at_price'] ?? 0); $price = (float)$product['price']; if ($cPrice > $price): ?>
                        <span class="badge-tag tag-sale" style="background:#e11d48; color:white; font-weight:bold;">-<?= round((($cPrice - $price) / $cPrice) * 100) ?>%</span>
                    <?php else: ?>
                        <span class="badge-tag tag-hot"><?= $hasRecordedSales ? 'BÁN CHẠY' : 'GỢI Ý' ?></span>
                    <?php endif; ?>
                    <img src="<?= BASE_URL . htmlspecialchars(productAssetPath($product['image'] ?? '')) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                </div>
                <div class="product-info-new">
                    <span class="product-category-new"><?= htmlspecialchars($product['category']) ?></span>
                    <h3 class="product-title-new"><?= htmlspecialchars($product['name']) ?></h3>
                    <div class="product-commerce-meta">
                        <?= $hasRecordedSales
                            ? 'Đã bán ' . number_format((int)($product['sold_count'] ?? 0))
                            : 'Sản phẩm phù hợp để bắt đầu khám phá' ?>
                    </div>
                    <div class="product-price-new">
                          <?php $cPrice = (float)($product['compare_at_price'] ?? 0); $price = (float)$product['price']; if ($cPrice > $price): ?>
                              <span style="color: #e11d48; font-weight: 700;"><?= number_format($price, 0, ',', '.') ?> ₫</span>
                              <del style="color: #94a3b8; font-size: 0.9em; margin-left: 8px;"><?= number_format($cPrice, 0, ',', '.') ?> ₫</del>
                          <?php else: ?>
                              <?= number_format($price, 0, ',', '.') ?> ₫
                          <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($bestSellingProducts)): ?><p style="grid-column:1/-1;text-align:center;color:#666;">Chưa có dữ liệu sản phẩm bán chạy.</p><?php endif; ?>
        </div>
    </section>

    <!-- Special Collection Full-Width Banner -->
    <section class="promo-banner-section" style="background-image: linear-gradient(rgba(45, 27, 20, 0.72), rgba(45, 27, 20, 0.72)), url('<?= BASE_URL ?>assets/images/lam-hero-courtyard.jpg');">
        <div class="promo-banner-content">
            <span class="promo-subtitle">BỘ SƯU TẬP ĐẶC BIỆT</span>
            <h2>Đồ Lam, Pháp Phục &amp; Túi Xách<br>Trang Nghiêm Thanh Tịnh</h2>
            <p>Tuyển chọn những mẫu pháp phục lanh lụa cao cấp, kết hợp cùng túi xách đi chùa thêu sen tao nhã và tràng hạt tự nhiên, mang lại sự an lạc trọn vẹn cho quý Phật tử.</p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                <a href="<?= BASE_URL ?>shop" class="btn-gold" style="text-decoration:none; display:inline-block;">Khám phá bộ sưu tập</a>
                <a href="<?= BASE_URL ?>shop?category=Túi+đeo+đi+chùa" class="btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; background: rgba(255,255,255,0.18); border-color: rgba(255,255,255,0.45); color: #fff;">Xem mẫu túi xách</a>
            </div>
        </div>
    </section>

    <!-- Sản phẩm mới -->
    <section class="products-section bg-beige-light">
        <div class="section-header-flex">
            <div>
                <span class="section-eyebrow">VỪA CẬP NHẬT</span>
                <h2 class="section-title-elegant">Sản Phẩm Mới</h2>
            </div>
            <a href="<?= BASE_URL ?>shop" class="btn-text-link">Xem tất cả →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card" onclick="goToProduct(<?= (int)$product['id'] ?>)">
                <div class="product-img-wrapper" style="border-radius:16px;">
                    <?php $cPrice = (float)($product['compare_at_price'] ?? 0); $price = (float)$product['price']; if ($cPrice > $price): ?>
                        <span class="badge-tag tag-sale" style="background:#e11d48; color:white; font-weight:bold;">-<?= round((($cPrice - $price) / $cPrice) * 100) ?>%</span>
                    <?php else: ?>
                        <span class="badge-tag tag-new">MỚI</span>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars(homeAssetUrl($product['image'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                </div>
                <div class="product-info-new">
                    <span class="product-category-new"><?= htmlspecialchars($product['category'] ?? '') ?></span>
                    <h3 class="product-title-new"><?= htmlspecialchars($product['name']) ?></h3>
                    <div class="product-commerce-meta">Mẫu mới tại Liên Hoa</div>
                    <div class="product-price-new">
                          <?php $cPrice = (float)($product['compare_at_price'] ?? 0); $price = (float)$product['price']; if ($cPrice > $price): ?>
                              <span style="color: #e11d48; font-weight: 700;"><?= number_format($price, 0, ',', '.') ?> ₫</span>
                              <del style="color: #94a3b8; font-size: 0.9em; margin-left: 8px;"><?= number_format($cPrice, 0, ',', '.') ?> ₫</del>
                          <?php else: ?>
                              <?= number_format($price, 0, ',', '.') ?> ₫
                          <?php endif; ?>
                      </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($featuredProducts)): ?><p style="grid-column:1/-1;text-align:center;color:#666;">Chưa có sản phẩm mới.</p><?php endif; ?>
        </div>
    </section>

    <!-- Giá trị thương hiệu đặt sau các khối mua sắm để củng cố quyết định -->
    <section class="intro-section">
        <div class="intro-container">
            <div class="intro-header">
                <span class="section-subtitle-center">VÌ SAO CHỌN LIÊN HOA</span>
                <h2 class="section-title-center" style="margin-bottom:1rem;">Gieo Duyên Lành Trong Từng Tà Áo</h2>
                <p class="intro-lead">Liên Hoa hướng đến những thiết kế kín đáo, trang nghiêm nhưng vẫn nhẹ nhàng và thoáng mát khi lễ bái, kinh hành. Mỗi nhóm sản phẩm được trình bày rõ chất liệu, phân loại và tồn kho để khách dễ chọn đúng nhu cầu.</p>
            </div>
            <div class="intro-grid">
                <div class="intro-card">
                    <div class="intro-icon-wrapper"><img src="<?= BASE_URL ?>assets/images/icon-phap-phuc.jpg" alt="Pháp phục" class="intro-icon-img"></div>
                    <h3>Pháp Phục Chuẩn Mực</h3>
                    <p>Thiết kế kín đáo, phom dáng thanh thoát và phù hợp không gian lễ bái, sinh hoạt đạo tràng.</p>
                </div>
                <div class="intro-card">
                    <div class="intro-icon-wrapper"><img src="<?= BASE_URL ?>assets/images/icon-tui-xach.jpg" alt="Túi xách" class="intro-icon-img"></div>
                    <h3>Tiện Dụng Khi Đi Chùa</h3>
                    <p>Túi đeo và phụ kiện có phân loại rõ ràng, thuận tiện mang kinh sách và vật dụng cá nhân.</p>
                </div>
                <div class="intro-card">
                    <div class="intro-icon-wrapper"><img src="<?= BASE_URL ?>assets/images/icon-vai-tu-nhien.jpg" alt="Chất liệu tự nhiên" class="intro-icon-img"></div>
                    <h3>Chất Liệu Dễ Chịu</h3>
                    <p>Ưu tiên vải đũi và lanh thoáng mát; thông tin sản phẩm giúp khách cân nhắc trước khi đặt.</p>
                </div>
                <div class="intro-card">
                    <div class="intro-icon-wrapper"><img src="<?= BASE_URL ?>assets/images/icon-phung-su.jpg" alt="Tận tâm tư vấn" class="intro-icon-img"></div>
                    <h3>Hỗ Trợ Trước &amp; Sau Mua</h3>
                    <p>Tư vấn chọn size, theo dõi đơn, hỗ trợ ticket và đổi trả theo điều kiện công bố.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <span class="section-subtitle-center">TRẢI NGHIỆM SAU MUA</span>
        <h2 class="section-title-center">Cảm Nhận Khách Hàng</h2>
        <p class="section-disclosure">Nội dung phản hồi mô phỏng phục vụ trình bày đồ án.</p>
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
        <span class="section-subtitle-center">HƯỚNG DẪN TRƯỚC &amp; SAU MUA</span>
        <h2 class="section-title-center" style="margin-bottom:1rem;">Kiến Thức &amp; Cẩm Nang</h2>
        <p class="section-disclosure" style="margin-bottom:3rem;">Giúp khách chọn đúng sản phẩm, hiểu ý nghĩa và biết cách bảo quản lâu dài.</p>
        <div class="blog-grid">
            <article class="blog-card">
                <div class="blog-img-wrapper">
                    <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1025.jpg" alt="Hướng dẫn chọn áo lam">
                    <span class="blog-tag">Hướng dẫn</span>
                </div>
                <div class="blog-info">
                    <span class="blog-date">12/07/2026 · 3 phút đọc</span>
                    <h3>Hướng dẫn chọn áo lam đúng kích thước và phong cách</h3>
                    <p>Gợi ý đo số đo, chọn phom dáng và chất liệu phù hợp để mặc thoải mái khi lễ bái, kinh hành.</p>
                </div>
            </article>
            <article class="blog-card">
                <div class="blog-img-wrapper">
                    <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1100.jpg" alt="Ý nghĩa tràng hạt">
                    <span class="blog-tag">Kiến thức</span>
                </div>
                <div class="blog-info">
                    <span class="blog-date">25/07/2026 · 5 phút đọc</span>
                    <h3>Ý nghĩa và cách chọn kích thước tràng hạt</h3>
                    <p>Phân biệt kích thước hạt, chất liệu và cách sử dụng để khách chọn đúng sản phẩm theo nhu cầu.</p>
                </div>
            </article>
            <article class="blog-card">
                <div class="blog-img-wrapper">
                    <img src="<?= BASE_URL ?>public/uploads/products/lam/lam-1125.jpg" alt="Chăm sóc tượng Phật">
                    <span class="blog-tag">Bảo quản</span>
                </div>
                <div class="blog-info">
                    <span class="blog-date">02/08/2026 · 4 phút đọc</span>
                    <h3>Cách chăm sóc và bảo quản vật phẩm đúng cách</h3>
                    <p>Những lưu ý về vệ sinh, độ ẩm và vị trí lưu giữ để sản phẩm bền đẹp trong quá trình sử dụng.</p>
                </div>
            </article>
        </div>
    </section>
</main>

<script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
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

    if (typeof window.updateBadgeGlobal === 'function') {
        window.updateBadgeGlobal(totalItems);
    }
    // Header mới chỉ dùng badge toàn cục và không còn sidebar giỏ hàng cũ.
    if (!cartItems || !cartCount || !cartTotal) return;

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
