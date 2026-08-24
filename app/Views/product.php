<?php include __DIR__ . '/partials/header.php'; ?>

<?php
function productDetailAssetPath($image): string {
    $image = (string)$image;
    if ($image === '') return 'assets/images/lam-placeholder.svg';
    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) return $image;
    if (str_starts_with($image, 'public/uploads/')) return $image;
    if (str_starts_with($image, 'uploads/')) return 'public/' . $image;
    return 'assets/images/' . $image;
}

function productDetailType($product): string {
    $type = trim((string)($product['type'] ?? ''));
    if ($type === '' || $type === '0' || strpos($type, '?') !== false) {
        return trim((string)($product['category'] ?? 'Đồ Lam Đi Chùa'));
    }
    return $type;
}

function productDetailGenderLabel($gender): string {
    $gender = strtolower(trim((string)$gender));
    if ($gender === 'men' || $gender === 'nam') return 'Nam';
    if ($gender === 'women' || $gender === 'nữ') return 'Nữ';
    if ($gender === 'unisex') return 'Nam / Nữ';
    return 'Pháp phục';
}

function productDetailColorHex($colorName): string {
    $c = mb_strtolower(trim((string)$colorName), 'UTF-8');
    $colorMap = [
        'lam' => '#7f9bb8', 'màu lam' => '#7f9bb8',
        'nâu' => '#78350f', 'màu nâu' => '#78350f',
        'xám' => '#6b7280', 'màu xám' => '#6b7280',
        'kem' => '#fef3c7', 'màu kem' => '#fef3c7',
        'vàng' => '#d97706', 'màu vàng' => '#d97706',
        'đỏ' => '#dc2626', 'màu đỏ' => '#dc2626',
        'đen' => '#111111', 'màu đen' => '#111111',
        'trắng' => '#ffffff', 'màu trắng' => '#ffffff',
        'xanh' => '#2563eb', 'xanh lá' => '#16a34a',
        'hồng' => '#ec4899', 'tím' => '#9333ea',
    ];
    return $colorMap[$c] ?? '#94a3b8';
}

// Process images list
$imagesList = $product['images'] ?? [];
if (empty($imagesList) && !empty($product['image'])) {
    $imagesList = [['image_url' => $product['image'], 'is_primary' => 1]];
}

// Process variants from DB
$variantsList = $product['variants'] ?? [];
$availableColors = [];
$availableSizes = [];
$totalStock = 0;
$hasVariantsInDb = !empty($variantsList);

if ($hasVariantsInDb) {
    foreach ($variantsList as $v) {
        $color = trim($v['color'] ?? '');
        $size = trim($v['size'] ?? '');
        $stock = (int)($v['stock_quantity'] ?? 0);
        $totalStock += $stock;

        if ($color !== '' && !in_array($color, $availableColors)) {
            $availableColors[] = $color;
        }

        if ($size !== '') {
            $availableSizes[] = [
                'id' => $v['id'],
                'size' => $size,
                'color' => $color,
                'stock' => $stock,
                'price_modifier' => (float)($v['price_modifier'] ?? 0)
            ];
        }
    }
} else {
    // Default size fallback if database table product_variants has no records for this product
    $defaultSizes = ['S', 'M', 'L', 'XL', 'Freesize'];
    foreach ($defaultSizes as $ds) {
        $availableSizes[] = [
            'id' => 0,
            'size' => $ds,
            'color' => 'Tiêu chuẩn',
            'stock' => 99,
            'price_modifier' => 0
        ];
    }
    $availableColors = ['Lam', 'Nâu', 'Xám'];
    $totalStock = 99;
}

$soldCount = (int)($product['sold_count'] ?? 0);
$avgRating = $ratingStats['avg_rating'] ?? 0;
$totalReviews = $ratingStats['total_reviews'] ?? 0;
$compareAtPrice = !empty($product['compare_at_price']) ? (float)$product['compare_at_price'] : 0;
$basePrice = (float)($product['base_price'] ?? 0);
?>

<style>
.product-detail-container {
    max-width: 1240px;
    margin: 2rem auto 4rem;
    padding: 0 1.5rem;
    font-family: var(--font-body);
}

.pd-breadcrumb {
    font-size: 0.88rem;
    color: #666;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.pd-breadcrumb a {
    color: #444;
    text-decoration: none;
    transition: color 0.2s;
}
.pd-breadcrumb a:hover {
    color: #000;
}
.pd-breadcrumb span.sep {
    color: #aaa;
}

/* Product Main Grid */
.pd-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3.5rem;
    align-items: start;
}

/* Left: Gallery */
.pd-gallery {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    position: sticky;
    top: 100px;
}
.pd-main-view {
    background: #f8f9fa;
    border-radius: 16px;
    position: relative;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #edf2f7;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}
.pd-main-view img {
    max-width: 92%;
    max-height: 92%;
    object-fit: contain;
    transition: transform 0.3s ease, opacity 0.2s ease;
}
.pd-main-view:hover img {
    transform: scale(1.04);
}
.pd-badge-list {
    position: absolute;
    top: 1rem;
    left: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    z-index: 2;
}
.pd-badge {
    padding: 0.35rem 0.8rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.pd-badge-sold {
    background: #111;
    color: #fff;
}
.pd-badge-gender {
    background: #e2e8f0;
    color: #334155;
}

.pd-thumbs {
    display: flex;
    gap: 0.75rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    scrollbar-width: thin;
}
.pd-thumb {
    width: 76px;
    height: 76px;
    flex-shrink: 0;
    background: #f8f9fa;
    border-radius: 10px;
    border: 2px solid transparent;
    cursor: pointer;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.4rem;
    transition: all 0.2s ease;
}
.pd-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.pd-thumb.active, .pd-thumb:hover {
    border-color: #111;
    background: #fff;
}

/* Right: Product Info */
.pd-info-box {
    display: flex;
    flex-direction: column;
}
.pd-type-tag {
    font-size: 0.95rem;
    font-weight: 600;
    color: #d97706;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.4rem;
}
.pd-title-text {
    font-size: 2.2rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.25;
    margin-bottom: 0.6rem;
    font-family: var(--font-heading);
}
.pd-sub-meta {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    margin-bottom: 1.2rem;
    font-size: 0.9rem;
    color: #64748b;
    flex-wrap: wrap;
}
.pd-stars-summary {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    color: #f59e0b;
    font-weight: 600;
}
.pd-price-row {
    display: flex;
    align-items: baseline;
    gap: 1rem;
    margin-bottom: 1.8rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
}
.pd-price-current {
    font-size: 2rem;
    font-weight: 700;
    color: #0f172a;
}
.pd-price-old {
    font-size: 1.2rem;
    color: #94a3b8;
    text-decoration: line-through;
}
.pd-stock-badge {
    font-size: 0.85rem;
    padding: 0.3rem 0.7rem;
    border-radius: 6px;
    font-weight: 600;
}
.stock-in { background: #dcfce7; color: #15803d; }
.stock-out { background: #fee2e2; color: #b91c1c; }

/* Selector Sections */
.pd-section-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.pd-color-options {
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
    margin-bottom: 1.8rem;
}
.pd-color-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}
.pd-color-item:hover {
    border-color: #94a3b8;
}
.pd-color-item.active {
    border-color: #0f172a;
    background: #f8fafc;
    box-shadow: inset 0 0 0 1px #0f172a;
}
.color-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,0.15);
}

.pd-size-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(85px, 1fr));
    gap: 0.6rem;
    margin-bottom: 2rem;
}
.pd-size-item {
    padding: 0.75rem 0.5rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 600;
    text-align: center;
    color: #1e293b;
    transition: all 0.2s;
}
.pd-size-item:hover:not(.disabled) {
    border-color: #0f172a;
}
.pd-size-item.active {
    border-color: #0f172a;
    background: #0f172a;
    color: #fff;
}
.pd-size-item.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #f1f5f9;
    text-decoration: line-through;
}

/* Quantity & Action Buttons */
.pd-actions-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 2.5rem;
}
.pd-qty-picker {
    display: flex;
    align-items: center;
    border: 1.5px solid #e2e8f0;
    border-radius: 50px;
    overflow: hidden;
    background: #fff;
}
.pd-qty-btn {
    width: 44px;
    height: 48px;
    border: none;
    background: transparent;
    font-size: 1.2rem;
    font-weight: 600;
    cursor: pointer;
    color: #334155;
    transition: background 0.2s;
}
.pd-qty-btn:hover { background: #f1f5f9; }
.pd-qty-input {
    width: 44px;
    text-align: center;
    border: none;
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
    background: transparent;
    -moz-appearance: textfield;
}
.pd-qty-input::-webkit-outer-spin-button,
.pd-qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.btn-add-cart-main {
    flex: 1;
    padding: 0.9rem 1.8rem;
    background: #0f172a;
    color: #fff;
    border: none;
    border-radius: 50px;
    font-size: 1.05rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    transition: all 0.2s ease;
    box-shadow: 0 4px 14px rgba(15,23,42,0.15);
}
.btn-add-cart-main:hover {
    background: #1e293b;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(15,23,42,0.25);
}

.btn-fav-round {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    transition: all 0.2s;
}
.btn-fav-round:hover {
    border-color: #0f172a;
    color: #0f172a;
}
.btn-fav-round.active {
    background: #fee2e2;
    border-color: #ef4444;
    color: #ef4444;
}
.btn-fav-round.active svg { fill: #ef4444; }

/* Service Features Bar */
.pd-services {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    padding: 1.2rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 2.5rem;
    border: 1px solid #f1f5f9;
}
.pd-service-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.85rem;
    color: #475569;
    font-weight: 500;
}
.pd-service-item svg { color: #0f172a; flex-shrink: 0; }

/* Description & Specs Tabs Section */
.pd-tabs-container {
    margin-top: 4rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 3rem;
}
.pd-tabs-header {
    display: flex;
    gap: 2rem;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 2rem;
}
.pd-tab-btn {
    padding: 0.8rem 0;
    border: none;
    background: transparent;
    font-size: 1.15rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    position: relative;
    transition: color 0.2s;
}
.pd-tab-btn.active {
    color: #0f172a;
}
.pd-tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 3px;
    background: #0f172a;
    border-radius: 3px;
}

.pd-tab-content { display: none; }
.pd-tab-content.active { display: block; }

.pd-desc-body {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #334155;
    max-width: 900px;
}

.pd-specs-table {
    width: 100%;
    max-width: 600px;
    border-collapse: collapse;
}
.pd-specs-table tr:nth-child(even) { background: #f8fafc; }
.pd-specs-table td {
    padding: 0.8rem 1.2rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.95rem;
}
.pd-specs-table td.spec-label {
    font-weight: 600;
    color: #475569;
    width: 40%;
}
.pd-specs-table td.spec-val {
    color: #0f172a;
}

/* Reviews Section */
.pd-reviews-summary {
    display: flex;
    gap: 3rem;
    align-items: center;
    padding: 2rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 2.5rem;
    flex-wrap: wrap;
}
.pd-rating-big {
    text-align: center;
}
.pd-rating-num {
    font-size: 3.5rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}
.pd-rating-stars {
    color: #f59e0b;
    font-size: 1.2rem;
    margin: 0.4rem 0;
}
.pd-rating-count {
    font-size: 0.85rem;
    color: #64748b;
}

.pd-reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    margin-bottom: 3rem;
}
.pd-review-card {
    padding: 1.5rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}
.pd-review-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.6rem;
}
.pd-reviewer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.pd-reviewer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    background: #e2e8f0;
}
.pd-reviewer-name {
    font-weight: 600;
    color: #0f172a;
    font-size: 0.95rem;
}
.pd-review-stars {
    color: #f59e0b;
    font-size: 0.9rem;
}
.pd-review-text {
    font-size: 0.95rem;
    color: #334155;
    line-height: 1.6;
}

.pd-add-review-box {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 2rem;
    max-width: 700px;
}
.pd-add-review-box h4 {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #0f172a;
}
.star-rating-select {
    display: flex;
    gap: 0.4rem;
    font-size: 1.6rem;
    color: #cbd5e1;
    cursor: pointer;
    margin-bottom: 1.2rem;
}
.star-rating-select span:hover,
.star-rating-select span.selected {
    color: #f59e0b;
}

.review-textarea {
    width: 100%;
    padding: 0.9rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    resize: vertical;
    min-height: 100px;
    margin-bottom: 1rem;
}
.btn-submit-review {
    padding: 0.8rem 1.6rem;
    background: #0f172a;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-submit-review:hover { background: #1e293b; }

/* Related Products Grid */
.related-section {
    margin-top: 5rem;
    padding-top: 3rem;
    border-top: 1px solid #e2e8f0;
}
.related-section h3 {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 2rem;
    color: #0f172a;
    font-family: var(--font-heading);
}
.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}
.related-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #f1f5f9;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
}
.related-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
}
.related-img {
    background: #f8fafc;
    aspect-ratio: 1/1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.related-img img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.related-info {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}
.r-title { font-weight: 600; font-size: 0.98rem; color: #0f172a; }
.r-cat { font-size: 0.85rem; color: #64748b; }
.r-price { font-weight: 700; color: #0f172a; font-size: 1rem; margin-top: 0.3rem; }

/* Responsive adjustments */
@media(max-width: 992px) {
    .pd-grid { grid-template-columns: 1fr; gap: 2.5rem; }
    .pd-gallery { position: static; }
}
@media(max-width: 576px) {
    .pd-actions-row { flex-direction: column; }
    .pd-services { grid-template-columns: 1fr; }
    .pd-price-current { font-size: 1.6rem; }
    .pd-title-text { font-size: 1.7rem; }
}
</style>

<div class="product-detail-container">
    <!-- Breadcrumb -->
    <nav class="pd-breadcrumb">
        <a href="<?= BASE_URL ?>">Trang chủ</a>
        <span class="sep">/</span>
        <a href="<?= BASE_URL ?>shop">Cửa hàng</a>
        <?php if (!empty($product['category'])): ?>
            <span class="sep">/</span>
            <a href="<?= BASE_URL ?>shop?category=<?= urlencode($product['category']) ?>"><?= htmlspecialchars($product['category']) ?></a>
        <?php endif; ?>
        <span class="sep">/</span>
        <span style="color: #0f172a; font-weight: 600;"><?= htmlspecialchars($product['name']) ?></span>
    </nav>

    <!-- Main Grid -->
    <div class="pd-grid">
        <!-- Gallery -->
        <div class="pd-gallery">
            <div class="pd-main-view">
                <div class="pd-badge-list">
                    <?php if ($soldCount > 0): ?>
                        <span class="pd-badge pd-badge-sold">Đã bán <?= $soldCount ?></span>
                    <?php endif; ?>
                    <span class="pd-badge pd-badge-gender"><?= productDetailGenderLabel($product['gender'] ?? '') ?></span>
                </div>
                <?php $primaryImgUrl = !empty($imagesList[0]['image_url']) ? productDetailAssetPath($imagesList[0]['image_url']) : ''; ?>
                <img id="mainProductImage" src="<?= BASE_URL . htmlspecialchars($primaryImgUrl) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>

            <?php if (count($imagesList) > 1): ?>
                <div class="pd-thumbs">
                    <?php foreach ($imagesList as $idx => $imgObj): ?>
                        <?php $thumbUrl = productDetailAssetPath($imgObj['image_url']); ?>
                        <div class="pd-thumb <?= $idx === 0 ? 'active' : '' ?>" onclick="switchProductImage('<?= BASE_URL . htmlspecialchars($thumbUrl) ?>', this)">
                            <img src="<?= BASE_URL . htmlspecialchars($thumbUrl) ?>" alt="Thumbnail <?= $idx + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="pd-info-box">
            <div class="pd-type-tag"><?= htmlspecialchars(productDetailType($product)) ?></div>
            <h1 class="pd-title-text"><?= htmlspecialchars($product['name']) ?></h1>
            
            <div class="pd-sub-meta">
                <?php if ($totalReviews > 0): ?>
                    <div class="pd-stars-summary">
                        ★ <?= $avgRating ?>
                        <span style="color: #64748b; font-weight: normal;">(<?= $totalReviews ?> đánh giá)</span>
                    </div>
                    <span>•</span>
                <?php endif; ?>
                <span>Mã SP: #<?= $product['id'] ?></span>
                <span>•</span>
                <span>Đã bán: <?= $soldCount ?></span>
            </div>

            <div class="pd-price-row">
                <div class="pd-price-current" id="displayedPrice" data-base-price="<?= $basePrice ?>">
                    <?= number_format($basePrice, 0, ',', '.') ?> ₫
                </div>
                <?php if ($compareAtPrice > $basePrice): ?>
                    <del class="pd-price-old"><?= number_format($compareAtPrice, 0, ',', '.') ?> ₫</del>
                <?php endif; ?>
                <div>
                    <?php if ($totalStock > 0): ?>
                        <span class="pd-stock-badge stock-in">Còn hàng (<?= $totalStock ?>)</span>
                    <?php else: ?>
                        <span class="pd-stock-badge stock-out">Hết hàng</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Color Options -->
            <?php if (!empty($availableColors)): ?>
                <div class="pd-section-label">
                    <span>Chọn Màu Sắc</span>
                    <span id="selectedColorName" style="color: #64748b; font-weight: normal; font-size: 0.9rem;"><?= htmlspecialchars($availableColors[0]) ?></span>
                </div>
                <div class="pd-color-options">
                    <?php foreach ($availableColors as $idx => $colorName): ?>
                        <div class="pd-color-item <?= $idx === 0 ? 'active' : '' ?>" onclick="selectColor('<?= htmlspecialchars($colorName) ?>', this)">
                            <span class="color-dot" style="background-color: <?= productDetailColorHex($colorName) ?>;"></span>
                            <span><?= htmlspecialchars($colorName) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Size Options -->
            <div class="pd-section-label">
                <span>Chọn Kích Thước (Size)</span>
                <span style="color: #2563eb; cursor: pointer; font-size: 0.85rem; font-weight: 500;" onclick="showSizeGuideModal()">Hướng dẫn chọn size</span>
            </div>
            <div class="pd-size-grid">
                <?php foreach ($availableSizes as $idx => $sz): ?>
                    <?php $isDisabled = ($sz['stock'] <= 0 && $hasVariantsInDb); ?>
                    <button class="pd-size-item <?= ($idx === 0 && !$isDisabled) ? 'active' : '' ?> <?= $isDisabled ? 'disabled' : '' ?>" 
                            data-variant-id="<?= $sz['id'] ?>"
                            data-size="<?= htmlspecialchars($sz['size']) ?>"
                            data-price-modifier="<?= $sz['price_modifier'] ?>"
                            data-stock="<?= $sz['stock'] ?>"
                            <?= $isDisabled ? 'disabled' : '' ?>
                            onclick="selectSize(this)">
                        <?= htmlspecialchars($sz['size']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Quantity & Actions -->
            <div class="pd-actions-row">
                <div class="pd-qty-picker">
                    <button class="pd-qty-btn" onclick="changeQty(-1)">-</button>
                    <input type="number" id="pdQty" class="pd-qty-input" value="1" min="1" readonly>
                    <button class="pd-qty-btn" onclick="changeQty(1)">+</button>
                </div>

                <button class="btn-add-cart-main" onclick="submitAddToCart(<?= $product['id'] ?>)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    Thêm Vào Giỏ Hàng
                </button>

                <?php
                $isFav = false;
                if (isset($_SESSION['user_id'])) {
                    $wishlistModel = new \App\Models\Wishlist();
                    $isFav = $wishlistModel->checkExists($_SESSION['user_id'], $product['id']);
                }
                ?>
                <button class="btn-fav-round <?= $isFav ? 'active' : '' ?>" title="Thêm vào yêu thích" onclick="toggleFavourite(this, <?= $product['id'] ?>)">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>
            </div>

            <!-- Service Guarantees -->
            <div class="pd-services">
                <div class="pd-service-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    <span>Freeship đơn từ 500.000đ</span>
                </div>
                <div class="pd-service-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                    <span>Đổi trả dễ dàng trong 30 ngày</span>
                </div>
                <div class="pd-service-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span>Cam kết vải chuẩn, thoáng mát</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Tabs: Description, Specs, Reviews -->
    <div class="pd-tabs-container">
        <div class="pd-tabs-header">
            <button class="pd-tab-btn active" onclick="openTab('tabDesc', this)">Mô Tả Sản Phẩm</button>
            <button class="pd-tab-btn" onclick="openTab('tabSpecs', this)">Thông Số Chi Tiết</button>
            <button class="pd-tab-btn" onclick="openTab('tabReviews', this)">Đánh Giá (<?= $totalReviews ?>)</button>
        </div>

        <!-- Description Tab -->
        <div id="tabDesc" class="pd-tab-content active">
            <div class="pd-desc-body">
                <p><?= nl2br(htmlspecialchars($product['description'] ?? 'Sản phẩm pháp phục, đồ lam đi chùa chất lượng cao.')) ?></p>
            </div>
        </div>

        <!-- Specs Tab -->
        <div id="tabSpecs" class="pd-tab-content">
            <table class="pd-specs-table">
                <tr>
                    <td class="spec-label">Thương Hiệu / Nguồn gốc</td>
                    <td class="spec-val">Pháp Phục Liên Hoa</td>
                </tr>
                <tr>
                    <td class="spec-label">Danh Mục</td>
                    <td class="spec-val"><?= htmlspecialchars($product['category'] ?? 'Đồ Lam Đi Chùa') ?></td>
                </tr>
                <tr>
                    <td class="spec-label">Loại Sản Phẩm</td>
                    <td class="spec-val"><?= htmlspecialchars($product['type'] ?? 'Pháp phục') ?></td>
                </tr>
                <tr>
                    <td class="spec-label">Dành Cho</td>
                    <td class="spec-val"><?= productDetailGenderLabel($product['gender'] ?? '') ?></td>
                </tr>
                <tr>
                    <td class="spec-label">Đã Bán</td>
                    <td class="spec-val"><?= $soldCount ?> sản phẩm</td>
                </tr>
                <tr>
                    <td class="spec-label">Tình Trạng Kho</td>
                    <td class="spec-val"><?= $totalStock > 0 ? "Còn hàng ($totalStock sản phẩm)" : 'Hết hàng' ?></td>
                </tr>
                <tr>
                    <td class="spec-label">Chất Liệu & Cam Kết</td>
                    <td class="spec-val">Vải Kate / Silk / Linen cao cấp, đường may tỉ mỉ, cam kết chất lượng 100%.</td>
                </tr>
            </table>
        </div>

        <!-- Reviews Tab -->
        <div id="tabReviews" class="pd-tab-content">
            <div class="pd-reviews-summary">
                <div class="pd-rating-big">
                    <div class="pd-rating-num"><?= number_format($avgRating, 1) ?></div>
                    <div class="pd-rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= $i <= round($avgRating) ? '★' : '☆' ?>
                        <?php endfor; ?>
                    </div>
                    <div class="pd-rating-count"><?= $totalReviews ?> nhận xét</div>
                </div>

                <div style="flex: 1;">
                    <p style="font-size: 0.95rem; color: #475569; margin: 0;">
                        Tất cả đánh giá đến từ phật tử và khách hàng mua sản phẩm đồ lam, vật phẩm đi chùa tại Liên Hoa.
                    </p>
                </div>
            </div>

            <!-- List Reviews -->
            <div class="pd-reviews-list">
                <?php if (empty($reviews)): ?>
                    <p style="color: #64748b; font-style: italic;">Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên gửi cảm nhận!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="pd-review-card">
                            <div class="pd-review-head">
                                <div class="pd-reviewer-info">
                                    <img class="pd-reviewer-avatar" src="<?= !empty($rev['avatar']) ? BASE_URL . htmlspecialchars($rev['avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($rev['user_name'] ?? 'User').'&background=0F172A&color=fff' ?>" alt="Avatar">
                                    <div>
                                        <div class="pd-reviewer-name"><?= htmlspecialchars($rev['user_name'] ?? 'Phật tử Liên Hoa') ?></div>
                                    </div>
                                </div>
                                <div class="pd-review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= (int)$rev['rating'] ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="pd-review-text"><?= nl2br(htmlspecialchars($rev['comment'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Add Review Form -->
            <div class="pd-add-review-box">
                <h4>Gửi Đánh Giá Của Bạn</h4>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form id="reviewForm" onsubmit="submitReview(event, <?= $product['id'] ?>)">
                        <div class="pd-section-label">Đánh giá số sao:</div>
                        <div class="star-rating-select" id="starRatingSelect">
                            <span data-val="1" onclick="setRating(1)">★</span>
                            <span data-val="2" onclick="setRating(2)">★</span>
                            <span data-val="3" onclick="setRating(3)">★</span>
                            <span data-val="4" onclick="setRating(4)">★</span>
                            <span data-val="5" class="selected" onclick="setRating(5)">★</span>
                        </div>
                        <input type="hidden" id="reviewRatingInput" value="5">

                        <textarea id="reviewCommentInput" class="review-textarea" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm này..." required></textarea>
                        <button type="submit" class="btn-submit-review">Gửi Đánh Giá</button>
                    </form>
                <?php else: ?>
                    <p style="color: #64748b;">
                        Vui lòng <a href="<?= BASE_URL ?>login" style="color: #2563eb; font-weight: 600;">đăng nhập</a> để viết đánh giá cho sản phẩm này.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related)): ?>
        <div class="related-section">
            <h3>Sản Phẩm Bạn Có Thể Thích</h3>
            <div class="related-grid">
                <?php foreach ($related as $r): ?>
                    <?php $rImg = productDetailAssetPath($r['image'] ?? ''); ?>
                    <a href="<?= BASE_URL ?>product?id=<?= $r['id'] ?>" class="related-card">
                        <div class="related-img" style="position:relative;">
                            <?php $rCPrice = (float)($r['compare_at_price'] ?? 0); $rPrice = (float)$r['price']; if ($rCPrice > $rPrice): ?>
                                <span class="badge-tag tag-sale" style="position:absolute; top:10px; left:10px; background:#e11d48; color:white; font-size:0.75rem; padding:3px 8px; border-radius:4px; font-weight:bold; z-index:2;">-<?= round((($rCPrice - $rPrice) / $rCPrice) * 100) ?>%</span>
                            <?php endif; ?>
                            <img src="<?= BASE_URL . htmlspecialchars($rImg) ?>" alt="<?= htmlspecialchars($r['name']) ?>">
                        </div>
                        <div class="related-info">
                            <span class="r-title"><?= htmlspecialchars($r['name']) ?></span>
                            <span class="r-cat"><?= htmlspecialchars(productDetailType($r)) ?></span>
                            <div style="margin-top:0.3rem;">
                                <?php if ($rCPrice > $rPrice): ?>
                                    <span style="color: #e11d48; font-weight: 700;"><?= number_format($rPrice, 0, ',', '.') ?> ₫</span>
                                    <del style="color: #94a3b8; font-size: 0.85em; margin-left: 6px;"><?= number_format($rCPrice, 0, ',', '.') ?> ₫</del>
                                <?php else: ?>
                                    <span class="r-price"><?= number_format($rPrice, 0, ',', '.') ?> ₫</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Toast Feedback -->
<div class="toast" id="toast"></div>

<script>
let selectedSize = '';
let selectedVariantId = 0;
let selectedColor = '<?= !empty($availableColors) ? htmlspecialchars($availableColors[0]) : '' ?>';
let currentRating = 5;

// Dynamic Image Gallery Switcher
function switchProductImage(url, thumbElement) {
    const mainImg = document.getElementById('mainProductImage');
    mainImg.style.opacity = '0.3';
    setTimeout(() => {
        mainImg.src = url;
        mainImg.style.opacity = '1';
    }, 150);

    document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
    if (thumbElement) thumbElement.classList.add('active');
}

// Color Selection
function selectColor(colorName, el) {
    selectedColor = colorName;
    document.querySelectorAll('.pd-color-item').forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');
    const colorDisplay = document.getElementById('selectedColorName');
    if (colorDisplay) colorDisplay.textContent = colorName;
}

// Size Selection & Dynamic Price Update
function selectSize(el) {
    if (el.classList.contains('disabled')) return;

    document.querySelectorAll('.pd-size-item').forEach(s => s.classList.remove('active'));
    el.classList.add('active');

    selectedSize = el.dataset.size || '';
    selectedVariantId = parseInt(el.dataset.variantId || '0');
    const priceModifier = parseFloat(el.dataset.priceModifier || '0');

    // Recalculate price display
    const priceEl = document.getElementById('displayedPrice');
    if (priceEl) {
        const basePrice = parseFloat(priceEl.dataset.basePrice || '0');
        const finalPrice = basePrice + priceModifier;
        priceEl.textContent = new Intl.NumberFormat('vi-VN').format(finalPrice) + ' ₫';
    }
}

// Show Size Guide Modal / Alert
function showSizeGuideModal() {
    alert("BẢNG HƯỚNG DẪN CHỌN SIZE QUẦN ÁO ĐỒ LAM & PHÁP PHỤC:\n\n- Size S: 45kg - 52kg (Chiều cao 1m50 - 1m58)\n- Size M: 53kg - 60kg (Chiều cao 1m59 - 1m65)\n- Size L: 61kg - 68kg (Chiều cao 1m66 - 1m72)\n- Size XL: 69kg - 76kg (Chiều cao 1m73 - 1m78)\n- Size XXL: Trên 76kg\n\nĐối với túi đeo & chuỗi hạt: Kích thước tiêu chuẩn.");
}

// Initialize default active size selection
document.addEventListener('DOMContentLoaded', () => {
    const activeSizeEl = document.querySelector('.pd-size-item.active');
    if (activeSizeEl) {
        selectSize(activeSizeEl);
    }
});

// Quantity controls
function changeQty(delta) {
    const qtyInput = document.getElementById('pdQty');
    let current = parseInt(qtyInput.value || '1');
    current += delta;
    if (current < 1) current = 1;
    qtyInput.value = current;
}

// Submit Add to Cart
function submitAddToCart(productId) {
    const activeSizeEl = document.querySelector('.pd-size-item.active');
    if (!activeSizeEl && document.querySelectorAll('.pd-size-item').length > 0) {
        showToast('Vui lòng chọn Size sản phẩm!');
        return;
    }

    const qty = parseInt(document.getElementById('pdQty').value || '1');
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('qty', qty);
    if (selectedVariantId > 0) {
        formData.append('variant_id', selectedVariantId);
    }
    if (selectedSize) {
        formData.append('size', selectedSize);
    }
    if (selectedColor) {
        formData.append('color', selectedColor);
    }

    fetch(BASE_URL + 'cart/add', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
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
    .catch(err => {
        console.error(err);
        showToast('Không thể kết nối đến máy chủ.');
    });
}

// Toggle Wishlist
function toggleFavourite(btn, productId) {
    const isAdding = !btn.classList.contains('active');
    const url = isAdding ? BASE_URL + 'wishlist/add' : BASE_URL + 'wishlist/remove';

    const formData = new FormData();
    formData.append('product_id', productId);

    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            btn.classList.toggle('active', isAdding);
            showToast(data.message);
        } else {
            showToast(data.message);
            if (data.message && data.message.includes('đăng nhập')) {
                setTimeout(() => window.location.href = BASE_URL + 'login', 1500);
            }
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Có lỗi xảy ra, vui lòng thử lại.');
    });
}

// Tab navigation
function openTab(tabId, btn) {
    document.querySelectorAll('.pd-tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.pd-tab-btn').forEach(b => b.classList.remove('active'));

    const targetTab = document.getElementById(tabId);
    if (targetTab) targetTab.classList.add('active');
    if (btn) btn.classList.add('active');
}

// Interactive Review Star Rating
function setRating(val) {
    currentRating = val;
    document.getElementById('reviewRatingInput').value = val;
    const stars = document.querySelectorAll('#starRatingSelect span');
    stars.forEach((s, idx) => {
        s.classList.toggle('selected', idx < val);
    });
}

// Submit Product Review
function submitReview(e, productId) {
    e.preventDefault();
    const comment = document.getElementById('reviewCommentInput').value.trim();
    if (!comment) {
        showToast('Vui lòng nhập nội dung đánh giá!');
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('rating', currentRating);
    formData.append('comment', comment);

    fetch(BASE_URL + 'product/review', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        showToast(data.message);
        if (data.success) {
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Không thể gửi đánh giá.');
    });
}

// Toast notification helper
function showToast(msg) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
