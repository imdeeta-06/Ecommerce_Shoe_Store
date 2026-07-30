<?php include __DIR__ . '/partials/header.php'; ?>

<style>
.checkout-page { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; font-family: var(--font-body); }
.checkout-header { font-size: 2.5rem; font-family: var(--font-heading); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2rem; }
.checkout-layout { display: flex; gap: 4rem; }
.checkout-form-section { flex: 1.5; }
.checkout-summary-section { flex: 1; background: #f9f9f9; padding: 2rem; border-radius: 8px; align-self: flex-start; position: sticky; top: 20px; }

.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; font-size: 0.95rem; }
.form-control { width: 100%; padding: 0.8rem 1rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; font-family: var(--font-body); }
.form-control:focus { outline: none; border-color: #111; }
.form-row { display: flex; gap: 1rem; }
.form-row > .form-group { flex: 1; }

.section-title { font-family: var(--font-heading); font-size: 1.5rem; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; }

.payment-methods { display: flex; flex-direction: column; gap: 1rem; }
.payment-method { border: 1px solid #ddd; border-radius: 4px; padding: 1rem; cursor: pointer; display: flex; align-items: center; gap: 1rem; transition: border-color 0.2s; }
.payment-method:hover { border-color: #111; }
.payment-method input[type="radio"] { margin: 0; width: 1.2rem; height: 1.2rem; cursor: pointer; }
.payment-method label { margin: 0; cursor: pointer; font-weight: 500; flex: 1; }
.payment-method.active { border-color: #111; background: #fafafa; }

.summary-item { display: flex; gap: 1rem; margin-bottom: 1rem; }
.summary-item img { width: 60px; height: 60px; object-fit: contain; background: #fff; border-radius: 4px; border: 1px solid #eee; }
.summary-item-info { flex: 1; }
.summary-item-name { font-weight: 500; font-size: 0.95rem; margin-bottom: 0.2rem; }
.summary-item-qty { color: #666; font-size: 0.85rem; }
.summary-item-price { font-weight: 600; }

.summary-row { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.95rem; }
.summary-total { display: flex; justify-content: space-between; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #ddd; font-weight: 700; font-size: 1.2rem; }

.btn-place-order { width: 100%; padding: 1.2rem; background: #111; color: #fff; border: none; border-radius: 100px; font-size: 1.1rem; font-weight: 500; cursor: pointer; margin-top: 2rem; transition: background 0.2s; }
.btn-place-order:hover { background: #333; }

@media (max-width: 900px) {
    .checkout-layout { flex-direction: column; }
    .checkout-summary-section { position: static; }
    .form-row { flex-direction: column; gap: 0; }
}
</style>

<div class="checkout-page">
    <h1 class="checkout-header">Thanh toán</h1>
    
    <div class="checkout-layout">
        <form class="checkout-form-section" id="checkoutForm" onsubmit="handleCheckout(event)">
            
            <h2 class="section-title">Thông tin giao hàng</h2>
            
            <div class="form-group">
                <label for="fullName">Họ và tên *</label>
                <input type="text" id="fullName" class="form-control" required placeholder="Nhập họ và tên">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Số điện thoại *</label>
                    <input type="tel" id="phone" class="form-control" required placeholder="Nhập số điện thoại">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" class="form-control" placeholder="Nhập địa chỉ email (tuỳ chọn)">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Địa chỉ chi tiết *</label>
                <input type="text" id="address" class="form-control" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố">
            </div>
            
            <div class="form-group">
                <label for="note">Ghi chú đơn hàng (Tuỳ chọn)</label>
                <textarea id="note" class="form-control" rows="3" placeholder="Ghi chú thêm về đơn hàng, thời gian giao hàng..."></textarea>
            </div>

            <h2 class="section-title" style="margin-top: 3rem;">Phương thức thanh toán</h2>
            <div class="payment-methods">
                <div class="payment-method active" onclick="selectPayment(this)">
                    <input type="radio" name="payment" id="pay_cod" value="cod" checked>
                    <label for="pay_cod">Thanh toán khi nhận hàng (COD)</label>
                </div>
                <div class="payment-method" onclick="selectPayment(this)">
                    <input type="radio" name="payment" id="pay_bank" value="bank">
                    <label for="pay_bank">Chuyển khoản ngân hàng</label>
                </div>
                <div class="payment-method" onclick="selectPayment(this)">
                    <input type="radio" name="payment" id="pay_momo" value="momo">
                    <label for="pay_momo">Thanh toán qua ví MoMo</label>
                </div>
            </div>

            <button type="submit" class="btn-place-order">Hoàn tất đặt hàng</button>
        </form>

        <div class="checkout-summary-section">
            <h2 class="section-title">Tóm tắt đơn hàng</h2>
            <div id="checkoutItems">
                <!-- Items will be injected here -->
            </div>
            
            <!-- Voucher Section -->
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #ddd;">
                <label style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; display: block;">Mã giảm giá</label>
                <div style="display: flex; gap: 0.5rem;" id="couponInputWrap">
                    <input type="text" id="couponCode" placeholder="Nhập mã voucher" style="flex:1; padding: 0.7rem 1rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95rem; font-family: var(--font-body);">
                    <button type="button" onclick="applyCoupon()" style="padding: 0.7rem 1.2rem; background: #111; color: #fff; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; white-space: nowrap;">Áp dụng</button>
                </div>
                <div id="couponMsg" style="margin-top: 0.5rem; font-size: 0.85rem;"></div>
                <div id="couponApplied" style="display:none; margin-top: 0.5rem; background: #E8F5E9; color: #388E3C; padding: 0.6rem 1rem; border-radius: 4px; font-size: 0.9rem; display: none; justify-content: space-between; align-items: center;">
                    <span id="couponAppliedText"></span>
                    <button type="button" onclick="removeCoupon()" style="background: none; border: none; color: #D32F2F; font-weight: 700; cursor: pointer; font-size: 0.9rem;">✕ Hủy</button>
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #ddd;">
                <div class="summary-row">
                    <span>Tạm tính</span>
                    <span id="checkoutSubtotal">0 ₫</span>
                </div>
                <div class="summary-row" id="discountRow" style="display: none; color: #388E3C;">
                    <span>Giảm giá</span>
                    <span id="discountAmount">-0 ₫</span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển</span>
                    <span>Miễn phí</span>
                </div>
                <div class="summary-total">
                    <span>Tổng cộng</span>
                    <span id="checkoutTotal">0 ₫</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let checkoutCart = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchCart();
});

function fetchCart() {
    fetch(BASE_URL + 'cart/get')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                checkoutCart = data.items.map(item => ({
                    cart_id: item.id,
                    product_id: item.product_id,
                    name: item.name,
                    price: parseFloat(item.price),
                    qty: parseInt(item.quantity),
                    image: item.image_url
                }));
                
                if (checkoutCart.length === 0) {
                    alert('Giỏ hàng của bạn đang trống!');
                    window.location.href = BASE_URL + 'shop';
                    return;
                }
                renderCheckoutSummary();
            }
        });
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + ' ₫';
}

function checkoutImageUrl(image) {
    if (!image) return BASE_URL + 'assets/images/placeholder.jpg';
    if (image.startsWith('http')) return image;
    if (image.startsWith(BASE_URL)) return image;
    if (image.startsWith('/')) return image;
    if (image.startsWith('public/uploads/')) return BASE_URL + image;
    if (image.startsWith('uploads/')) return BASE_URL + 'public/' + image;
    if (image.startsWith('assets/')) return BASE_URL + image;
    return BASE_URL + 'assets/images/' + image;
}

function renderCheckoutSummary() {
    const itemsContainer = document.getElementById('checkoutItems');
    let total = 0;
    
    itemsContainer.innerHTML = checkoutCart.map(item => {
        const itemTotal = item.price * item.qty;
        total += itemTotal;
        const imgUrl = checkoutImageUrl(item.image);
        return `
            <div class="summary-item" style="position: relative;">
                <img src="${imgUrl}" alt="${item.name}" onerror="this.onerror=null; this.src='${BASE_URL}assets/images/placeholder.jpg'">
                <div class="summary-item-info">
                    <div class="summary-item-name">${item.name}</div>
                    <div style="display:flex; align-items:center; gap:10px; margin-top:5px;">
                        <button type="button" onclick="updateCartItem(${item.cart_id}, ${item.qty - 1})" style="width:24px; height:24px; border:1px solid #ddd; background:#fff; cursor:pointer;">-</button>
                        <span>${item.qty}</span>
                        <button type="button" onclick="updateCartItem(${item.cart_id}, ${item.qty + 1})" style="width:24px; height:24px; border:1px solid #ddd; background:#fff; cursor:pointer;">+</button>
                    </div>
                </div>
                <div class="summary-item-price">
                    ${formatPrice(itemTotal)}
                    <button type="button" onclick="removeCartItem(${item.cart_id})" style="display:block; margin-top:5px; margin-left:auto; background:none; border:none; color:red; cursor:pointer; font-size:12px;">Xóa</button>
                </div>
            </div>
        `;
    }).join('');
    
    document.getElementById('checkoutSubtotal').textContent = formatPrice(total);
    document.getElementById('checkoutTotal').textContent = formatPrice(total);
}

function selectPayment(element) {
    document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    element.querySelector('input').checked = true;
}

function updateCartItem(cartId, newQty) {
    if (newQty < 1) {
        removeCartItem(cartId);
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
            if (typeof window.updateBadgeGlobal === 'function') window.updateBadgeGlobal(data.cart_count);
            fetchCart(); // reload cart
        }
    });
}

function removeCartItem(cartId) {
    if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) return;
    
    const formData = new FormData();
    formData.append('cart_id', cartId);

    fetch(BASE_URL + 'cart/remove', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(r => r.json()).then(data => {
        if (data.success) {
            if (typeof window.updateBadgeGlobal === 'function') window.updateBadgeGlobal(data.cart_count);
            fetchCart(); // reload cart
        }
    });
}

let appliedDiscount = 0;
let appliedCouponId = null;

function getSubtotal() {
    return checkoutCart.reduce((sum, item) => sum + item.price * item.qty, 0);
}

function updateTotals() {
    const subtotal = getSubtotal();
    const total = subtotal - appliedDiscount;
    document.getElementById('checkoutSubtotal').textContent = formatPrice(subtotal);
    document.getElementById('checkoutTotal').textContent = formatPrice(total > 0 ? total : 0);

    const discountRow = document.getElementById('discountRow');
    if (appliedDiscount > 0) {
        discountRow.style.display = 'flex';
        document.getElementById('discountAmount').textContent = '-' + formatPrice(appliedDiscount);
    } else {
        discountRow.style.display = 'none';
    }
}

function applyCoupon() {
    const code = document.getElementById('couponCode').value.trim();
    const msg = document.getElementById('couponMsg');
    if (!code) { msg.innerHTML = '<span style="color:#D32F2F">Vui lòng nhập mã.</span>'; return; }

    msg.innerHTML = '<span style="color:#888">Đang kiểm tra...</span>';

    fetch(BASE_URL + 'apply-coupon', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ code: code, order_total: getSubtotal() })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            appliedDiscount = data.discount;
            appliedCouponId = data.coupon_id;
            msg.innerHTML = '';
            document.getElementById('couponInputWrap').style.display = 'none';
            const applied = document.getElementById('couponApplied');
            applied.style.display = 'flex';
            document.getElementById('couponAppliedText').textContent =
                '🎉 ' + data.code + ' (-' + data.discount_percent + '%, tiết kiệm ' + formatPrice(data.discount) + ')';
            updateTotals();
        } else {
            msg.innerHTML = '<span style="color:#D32F2F">' + data.message + '</span>';
        }
    })
    .catch(() => {
        msg.innerHTML = '<span style="color:#D32F2F">Lỗi kết nối. Thử lại sau.</span>';
    });
}

function removeCoupon() {
    appliedDiscount = 0;
    appliedCouponId = null;
    document.getElementById('couponApplied').style.display = 'none';
    document.getElementById('couponInputWrap').style.display = 'flex';
    document.getElementById('couponCode').value = '';
    document.getElementById('couponMsg').innerHTML = '';
    updateTotals();
}

function handleCheckout(e) {
    e.preventDefault();

    fetch(BASE_URL + 'checkout/place-order', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            shipping_name: document.getElementById('fullName').value.trim(),
            shipping_phone: document.getElementById('phone').value.trim(),
            shipping_email: document.getElementById('email').value.trim(),
            shipping_address: document.getElementById('address').value.trim(),
            coupon_id: appliedCouponId,
            discount: appliedDiscount,
            items: checkoutCart
        })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Khong the dat hang. Vui long thu lai.');
            return;
        }

        localStorage.removeItem('paceup_cart');
        window.location.href = BASE_URL + 'checkout-success';
    })
    .catch(() => {
        alert('Khong the dat hang. Vui long thu lai.');
    });
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
