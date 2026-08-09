<?php include __DIR__ . '/partials/header.php'; ?>

<style>
.checkout-layout { display: flex; gap: 4rem; }
.checkout-form-section { flex: 1.5; }
.checkout-summary-section { flex: 1; background: #fff; padding: 2rem; border: 1px solid #ddd; align-self: flex-start; position: sticky; top: 20px; }

.form-row { display: flex; gap: 1.5rem; }
.form-row > .client-form-group { flex: 1; }

.payment-methods { display: flex; flex-direction: column; gap: 1rem; }
.payment-method { border: 1px solid #ddd; padding: 1rem; cursor: pointer; display: flex; align-items: center; gap: 1rem; transition: border-color 0.2s; }
.payment-method:hover { border-color: #111; }
.payment-method input[type="radio"] { margin: 0; width: 1.2rem; height: 1.2rem; cursor: pointer; accent-color: #111; }
.payment-method label { margin: 0; cursor: pointer; font-weight: 500; font-size: 0.9rem; flex: 1; text-transform: uppercase; letter-spacing: 1px; }
.payment-method.active { border-color: #111; }

.summary-item { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
.summary-item img { width: 70px; height: 70px; object-fit: cover; border: 1px solid #ddd; }
.summary-item-info { flex: 1; }
.summary-item-name { font-weight: 500; font-size: 0.9rem; margin-bottom: 0.2rem; text-transform: uppercase; letter-spacing: 1px; }
.summary-item-qty { color: #888; font-size: 0.85rem; }
.summary-item-price { font-weight: 600; font-size: 0.95rem; }

.summary-row { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #666; }
.summary-total { display: flex; justify-content: space-between; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #ddd; font-weight: 600; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; color: #111; }

@media (max-width: 900px) {
    .checkout-layout { flex-direction: column; }
    .checkout-summary-section { position: static; }
    .form-row { flex-direction: column; gap: 0; }
}
</style>

<div class="client-page">
    <h1 class="client-title">Thanh toán</h1>
    
    <div class="checkout-layout">
        <form class="checkout-form-section" id="checkoutForm" onsubmit="handleCheckout(event)">
            
            <h2 class="client-section-title">Thông tin giao hàng</h2>
            
            <div class="client-form-group">
                <label for="fullName" class="client-label">Họ và tên *</label>
                <input type="text" id="fullName" class="client-input" required placeholder="Nhập họ và tên">
            </div>
            
            <div class="form-row">
                <div class="client-form-group">
                    <label for="phone" class="client-label">Số điện thoại *</label>
                    <input type="tel" id="phone" class="client-input" required placeholder="Nhập số điện thoại">
                </div>
                <div class="client-form-group">
                    <label for="email" class="client-label">Email</label>
                    <input type="email" id="email" class="client-input" placeholder="Nhập địa chỉ email (tuỳ chọn)">
                </div>
            </div>
            
            <div class="client-form-group">
                <label for="address" class="client-label">Địa chỉ chi tiết *</label>
                <input type="text" id="address" class="client-input" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố">
            </div>
            
            <div class="client-form-group">
                <label for="note" class="client-label">Ghi chú đơn hàng</label>
                <input type="text" id="note" class="client-input" placeholder="Ghi chú thêm về đơn hàng (Tuỳ chọn)">
            </div>

            <h2 class="client-section-title" style="margin-top: 3rem;">Phương thức thanh toán</h2>
            <div class="payment-methods">
                <div class="payment-method active" onclick="selectPayment(this)">
                    <input type="radio" name="payment" id="pay_cod" value="cod" checked>
                    <label for="pay_cod">Thanh toán khi nhận hàng (COD)</label>
                </div>
                <div class="payment-method" onclick="selectPayment(this)">
                    <input type="radio" name="payment" id="pay_bank" value="bank" disabled>
                    <label for="pay_bank" style="color:#999;">Chuyển khoản ngân hàng (sẽ bổ sung)</label>
                </div>
                <div class="payment-method" onclick="selectPayment(this)">
                    <input type="radio" name="payment" id="pay_momo" value="momo" disabled>
                    <label for="pay_momo" style="color:#999;">Thanh toán qua ví điện tử (sẽ bổ sung)</label>
                </div>
            </div>

            <label style="display:flex;gap:.7rem;align-items:flex-start;margin-top:1.5rem;color:#444;line-height:1.6;">
                <input type="checkbox" id="termsAccepted" required style="margin-top:.35rem;width:1.1rem;height:1.1rem;accent-color:#111;">
                <span>Tôi đồng ý với <a href="<?= BASE_URL ?>terms" target="_blank" rel="noopener">Điều khoản mua hàng</a> và <a href="<?= BASE_URL ?>privacy" target="_blank" rel="noopener">Chính sách bảo mật</a> của PaceUp.</span>
            </label>

            <button type="submit" class="client-btn" style="width: 100%; margin-top: 2rem;">Hoàn tất đặt hàng</button>
        </form>

        <div class="checkout-summary-section">
            <h2 class="client-section-title">Tóm tắt đơn hàng</h2>
            <div id="checkoutItems">
                <!-- Items will be injected here -->
            </div>
            
            <!-- Voucher Section -->
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #ddd;">
                <label class="client-label">Mã giảm giá</label>
                <div style="display: flex; gap: 1rem; align-items: flex-end;" id="couponInputWrap">
                    <div class="client-form-group" style="flex: 1; margin: 0;">
                        <input type="text" id="couponCode" class="client-input" placeholder="Nhập mã voucher">
                    </div>
                    <button type="button" onclick="applyCoupon()" class="client-btn client-btn-sm" style="white-space: nowrap;">Áp dụng</button>
                </div>
                <div id="couponMsg" style="margin-top: 0.5rem; font-size: 0.85rem;"></div>
                <div id="couponApplied" style="margin-top: 1rem; border: 1px solid #111; padding: 1rem; display: none; justify-content: space-between; align-items: center; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                    <span id="couponAppliedText"></span>
                    <button type="button" onclick="removeCoupon()" style="background: none; border: none; color: #111; font-weight: 700; cursor: pointer; text-decoration: underline; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Hủy</button>
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
                    <span id="checkoutShippingFee">0 ₫</span>
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
                    variant_id: item.variant_id,
                    size: item.size,
                    color: item.color,
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
                    <div style="font-size:0.8rem;color:#666;">Size: ${item.size || 'Mặc định'} · Màu: ${item.color || 'Mặc định'}</div>
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
    updateTotals();
}

function selectPayment(element) {
    if (element.querySelector('input')?.disabled) return;
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
let appliedCouponCode = '';

function getSubtotal() {
    return checkoutCart.reduce((sum, item) => sum + item.price * item.qty, 0);
}

function updateTotals() {
    const subtotal = getSubtotal();
    const shippingFee = subtotal >= 1000000 ? 0 : 30000;
    const total = subtotal + shippingFee - appliedDiscount;
    document.getElementById('checkoutSubtotal').textContent = formatPrice(subtotal);
    document.getElementById('checkoutShippingFee').textContent = shippingFee > 0 ? formatPrice(shippingFee) : 'Miễn phí';
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
        body: JSON.stringify({ code: code })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            appliedDiscount = data.discount;
            appliedCouponId = data.coupon_id;
            appliedCouponCode = data.code;
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
    appliedCouponCode = '';
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
            customer_note: document.getElementById('note').value.trim(),
            coupon_code: appliedCouponCode,
            terms_accepted: document.getElementById('termsAccepted').checked,
            payment_method: document.querySelector('input[name="payment"]:checked')?.value || 'cod'
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
