<?php include __DIR__ . '/partials/header.php'; ?>

<style nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
.cart-page-layout {
    display: flex;
    gap: 4rem;
    margin-top: 2rem;
}
.cart-items-section {
    flex: 2;
}
.cart-summary-section {
    flex: 1;
    background: var(--primary-light);
    padding: 2rem;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    align-self: flex-start;
    position: sticky;
    top: 20px;
    box-shadow: 0 4px 15px rgba(74, 59, 50, 0.03);
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
}
.cart-table th {
    text-align: left;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
    font-family: 'Outfit', sans-serif;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 1px;
    color: var(--text-muted);
}
.cart-table td {
    padding: 1.5rem 0;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}

.cart-item-detail {
    display: flex;
    gap: 1.5rem;
    align-items: center;
}
.cart-item-detail img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}
.cart-item-info-text .item-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.2rem;
}
.cart-item-info-text .item-meta {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.cart-qty-control {
    display: flex;
    align-items: center;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    width: fit-content;
    background: #fff;
}
.cart-qty-control button {
    border: 0;
    background: transparent;
    width: 32px;
    height: 32px;
    cursor: pointer;
    font-size: 1.1rem;
    color: var(--primary-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.cart-qty-control button:hover {
    background: var(--primary-light);
}
.cart-qty-control span {
    width: 36px;
    text-align: center;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--primary-dark);
}

.cart-price-cell {
    font-family: 'Outfit', sans-serif;
    font-weight: 600;
    color: var(--primary-dark);
}
.btn-delete-cart {
    background: transparent;
    border: 0;
    cursor: pointer;
    color: #c94a4a;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    transition: color 0.2s;
}
.btn-delete-cart:hover {
    color: #e53935;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.2rem;
    font-family: 'Outfit', sans-serif;
    font-size: 0.9rem;
    color: var(--primary-dark);
}
.summary-row.total {
    border-top: 1px solid var(--border-color);
    padding-top: 1.2rem;
    margin-top: 1.2rem;
    font-size: 1.15rem;
    font-weight: 600;
}

.cart-buttons-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1.5rem;
}

.cart-empty-state {
    text-align: center;
    padding: 5rem 2rem;
}
.cart-empty-state .icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
}
.cart-empty-state h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    color: var(--primary-dark);
    margin-bottom: 1rem;
}
.cart-empty-state p {
    color: var(--text-muted);
    margin-bottom: 2rem;
}

@media (max-width: 900px) {
    .cart-page-layout {
        flex-direction: column;
        gap: 2rem;
    }
    .cart-summary-section {
        position: static;
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

<div class="client-page">
    <h1 class="client-title">Giỏ hàng của bạn</h1>
    
    <div id="cartPageContainer" style="min-height: 350px;">
        <!-- Rendered dynamically by JavaScript -->
    </div>
</div>

<script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
let localCartItems = [];

function getProductImagePath(image) {
    if (!image) return '';
    if (image.startsWith('public/uploads/')) return image;
    if (image.startsWith('uploads/')) return 'public/' + image;
    return 'assets/images/' + image;
}

function renderCartPage() {
    const container = document.getElementById('cartPageContainer');
    if (!localCartItems || localCartItems.length === 0) {
        container.innerHTML = `
            <div class="cart-empty-state">
                <div class="icon"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg></div>
                <h2>Giỏ hàng đang trống</h2>
                <p>Quý Phật tử chưa thêm sản phẩm nào vào giỏ hàng.</p>
                <a href="${BASE_URL}shop" class="client-btn" style="display: inline-block;">Tiếp tục mua sắm</a>
            </div>
        `;
        return;
    }

    let subtotal = 0;
    const itemsHtml = localCartItems.map(item => {
        const itemPrice = parseFloat(item.price);
        const itemSubtotal = itemPrice * parseInt(item.quantity);
        subtotal += itemSubtotal;
        const imgUrl = item.image_url ? (item.image_url.startsWith('http') ? item.image_url : BASE_URL + getProductImagePath(item.image_url)) : '';
        
        return `
            <tr>
                <td>
                    <div class="cart-item-detail">
                        <img src="${imgUrl}" alt="${item.name}">
                        <div class="cart-item-info-text">
                            <div class="item-name">${item.name}</div>
                            <div class="item-meta">Size: ${item.size || 'Mặc định'} · Màu: ${item.color || 'Mặc định'}</div>
                        </div>
                    </div>
                </td>
                <td class="cart-price-cell">${new Intl.NumberFormat('vi-VN').format(itemPrice)}đ</td>
                <td>
                    <div class="cart-qty-control">
                        <button onclick="updatePageQty(${item.id}, ${parseInt(item.quantity) - 1})">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="updatePageQty(${item.id}, ${parseInt(item.quantity) + 1})">+</button>
                    </div>
                </td>
                <td class="cart-price-cell" style="text-align: right; font-weight: 600;">${new Intl.NumberFormat('vi-VN').format(itemSubtotal)}đ</td>
                <td style="text-align: right; width: 50px;">
                    <button class="btn-delete-cart" onclick="removePageItem(${item.id})" title="Xoá sản phẩm">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    container.innerHTML = `
        <div class="cart-page-layout">
            <div class="cart-items-section">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th style="text-align: right;">Tổng tạm</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>
            </div>
            
            <div class="cart-summary-section">
                <h2 class="client-section-title" style="margin-top: 0; font-size: 1.4rem;">Tóm tắt đơn hàng</h2>
                
                <div class="summary-row">
                    <span>Số lượng vật phẩm</span>
                    <span>${localCartItems.reduce((acc, curr) => acc + parseInt(curr.quantity), 0)}</span>
                </div>
                
                <div class="summary-row">
                    <span>Tổng tiền hàng</span>
                    <span>${new Intl.NumberFormat('vi-VN').format(subtotal)}đ</span>
                </div>
                
                <div class="summary-row">
                    <span>Phí vận chuyển</span>
                    <span>Tính theo địa chỉ</span>
                </div>
                
                <div class="summary-row total">
                    <span>Tổng cộng</span>
                    <span>${new Intl.NumberFormat('vi-VN').format(subtotal)}đ + phí ship</span>
                </div>
                
                <div class="cart-buttons-group">
                    <a href="${BASE_URL}checkout" class="client-btn" style="text-align: center; display: block; text-transform: uppercase;">Tiến hành thanh toán</a>
                    <a href="${BASE_URL}shop" class="client-btn btn-secondary" style="text-align: center; display: block; background: transparent; border: 1px solid var(--primary-color); color: var(--primary-color); text-transform: uppercase; margin-top: 0.5rem;">Tiếp tục mua sắm</a>
                </div>
            </div>
        </div>
    `;
}

function fetchPageCart() {
    fetch(BASE_URL + 'cart/get')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                localCartItems = data.items;
                renderCartPage();
                if (typeof window.updateBadgeGlobal === 'function') {
                    window.updateBadgeGlobal(data.cart_count);
                }
            }
        });
}

function updatePageQty(cartId, qty) {
    if (qty < 1) {
        removePageItem(cartId);
        return;
    }
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('qty', qty);
    fetch(BASE_URL + 'cart/update', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(r => r.json()).then(data => {
        if (data.success) {
            fetchPageCart();
        } else {
            alert(data.message || 'Lỗi cập nhật số lượng');
        }
    });
}

function removePageItem(cartId) {
    if (!confirm('Quý Phật tử muốn xoá sản phẩm này khỏi giỏ hàng?')) return;
    const formData = new FormData();
    formData.append('cart_id', cartId);
    fetch(BASE_URL + 'cart/remove', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(r => r.json()).then(data => {
        if (data.success) {
            fetchPageCart();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    fetchPageCart();
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
