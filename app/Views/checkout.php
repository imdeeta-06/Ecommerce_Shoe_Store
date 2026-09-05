<?php
$checkoutFlashMessages = \App\Helpers\SessionHelper::getAllFlash();
$checkoutStore = require __DIR__ . '/../../config/store.php';
$paypalEnabled = !empty($paypalCheckout['enabled']);
$paypalMode = (string)($paypalCheckout['mode'] ?? 'sandbox');
$paypalRate = (float)($paypalCheckout['vnd_per_usd'] ?? 0);
$defaultCheckoutAddress = null;
foreach (($checkoutAddresses ?? []) as $candidateAddress) {
    if (!empty($candidateAddress['is_default'])) { $defaultCheckoutAddress = $candidateAddress; break; }
}
$defaultCheckoutAddress = $defaultCheckoutAddress ?: (($checkoutAddresses ?? [])[0] ?? null);
include __DIR__ . '/partials/header.php';
?>

<style nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
.checkout-layout { display: flex; gap: 4rem; }
.checkout-form-section { flex: 1.5; }
.checkout-summary-section { flex: 1; background: var(--primary-light); padding: 2rem; border: 1px solid var(--border-color); border-radius: 16px; align-self: flex-start; position: sticky; top: 20px; box-shadow: 0 4px 15px rgba(74, 59, 50, 0.03); }

.form-row { display: flex; gap: 1.5rem; }
.form-row > .client-form-group { flex: 1; }

.payment-methods { display: flex; flex-direction: column; gap: 1rem; }
.payment-method { border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; cursor: pointer; display: flex; align-items: center; gap: 1rem; transition: all 0.3s; background: #fff; }
.payment-method:hover { border-color: var(--primary-color); }
.payment-method input[type="radio"] { margin: 0; width: 1.2rem; height: 1.2rem; cursor: pointer; accent-color: var(--primary-dark); }
.payment-method label { margin: 0; cursor: pointer; font-weight: 500; font-size: 0.9rem; flex: 1; text-transform: uppercase; letter-spacing: 1px; color: var(--primary-dark); }
.payment-method.active { border-color: var(--primary-color); background: var(--primary-light); }
.payment-method.is-disabled { cursor: not-allowed; opacity: .62; background: #f7f7f7; }
.payment-method-copy { display: flex; flex: 1; flex-direction: column; gap: .25rem; }
.payment-method-copy label { flex: initial; }
.payment-method-copy small { color: var(--text-muted); line-height: 1.45; }

.summary-item { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
.summary-item img { width: 70px; height: 70px; object-fit: cover; border: 1px solid var(--border-color); border-radius: 8px; }
.summary-item-info { flex: 1; }
.summary-item-name { font-weight: 500; font-size: 0.9rem; margin-bottom: 0.2rem; text-transform: uppercase; letter-spacing: 1px; color: var(--primary-dark); }
.summary-item-qty { color: var(--text-muted); font-size: 0.85rem; }
.summary-item-price { font-weight: 600; font-size: 0.95rem; color: var(--primary-dark); }

.summary-row { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); }
.summary-total { display: flex; justify-content: space-between; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); font-weight: 600; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; color: var(--primary-dark); }

@media (max-width: 900px) {
    .checkout-layout { flex-direction: column; }
    .checkout-summary-section { position: static; }
    .form-row { flex-direction: column; gap: 0; }
}
</style>

<div class="client-page">
    <h1 class="client-title">Thanh toán</h1>

    <?php foreach ($checkoutFlashMessages as $type => $message): ?>
        <div class="client-flash <?= $type === 'error' ? 'error' : 'success' ?>" role="status">
            <?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endforeach; ?>
    
    <div class="checkout-layout">
        <form class="checkout-form-section" id="checkoutForm" onsubmit="handleCheckout(event)">
            
            <h2 class="client-section-title">Thông tin giao hàng</h2>

            <?php if (!empty($checkoutAddresses)): ?>
            <div class="client-form-group">
                <label for="savedAddress" class="client-label">Chọn địa chỉ đã lưu</label>
                <select id="savedAddress" class="client-input" onchange="applySavedAddress(this)">
                    <?php foreach ($checkoutAddresses as $address): ?>
                        <option value="<?= (int)$address['id'] ?>" data-name="<?= htmlspecialchars($address['recipient_name'], ENT_QUOTES, 'UTF-8') ?>" data-phone="<?= htmlspecialchars($address['recipient_phone'], ENT_QUOTES, 'UTF-8') ?>" data-address="<?= htmlspecialchars($address['address_line'], ENT_QUOTES, 'UTF-8') ?>" data-province="<?= htmlspecialchars($address['ward_district_city'], ENT_QUOTES, 'UTF-8') ?>" <?= !empty($address['is_default']) ? 'selected' : '' ?>><?= htmlspecialchars($address['recipient_name'] . ' — ' . $address['address_line'] . ', ' . $address['ward_district_city']) ?><?= !empty($address['is_default']) ? ' (mặc định)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="client-form-group">
                <label for="fullName" class="client-label">Họ và tên *</label>
                <input type="text" id="fullName" class="client-input" required placeholder="Nhập họ và tên" value="<?= htmlspecialchars($defaultCheckoutAddress['recipient_name'] ?? $checkoutUser['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="form-row">
                <div class="client-form-group">
                    <label for="phone" class="client-label">Số điện thoại *</label>
                    <input type="tel" id="phone" class="client-input" required placeholder="Nhập số điện thoại" value="<?= htmlspecialchars($defaultCheckoutAddress['recipient_phone'] ?? $checkoutUser['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="client-form-group">
                    <label for="email" class="client-label">Email</label>
                    <input type="email" id="email" class="client-input" placeholder="Nhập địa chỉ email (tuỳ chọn)" value="<?= htmlspecialchars($checkoutUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            
            <div class="client-form-group">
                <label for="address" class="client-label">Địa chỉ chi tiết *</label>
                <input type="text" id="address" class="client-input" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện" value="<?= htmlspecialchars($defaultCheckoutAddress['address_line'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-row">
                <div class="client-form-group"><label for="province" class="client-label">Tỉnh/Thành phố *</label><input type="text" id="province" class="client-input" list="provinceSuggestions" required value="<?= htmlspecialchars($defaultCheckoutAddress['ward_district_city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" oninput="scheduleShippingQuote()" placeholder="Ví dụ: Thành phố Hồ Chí Minh"><datalist id="provinceSuggestions"><option value="Thành phố Hồ Chí Minh"><option value="Hà Nội"><option value="Đà Nẵng"><option value="Hải Phòng"><option value="Cần Thơ"><option value="Đồng Nai"><option value="Bình Dương"></datalist></div>
                <div class="client-form-group"><label for="shippingCarrier" class="client-label">Đơn vị vận chuyển *</label><select id="shippingCarrier" class="client-input" required onchange="selectShippingQuote()"><option value="">Đang tính cước...</option></select><small id="shippingQuoteNote" style="color:#666;display:block;margin-top:.4rem;">Cước được tính ở server theo địa chỉ, trọng lượng thực và trọng lượng quy đổi.</small></div>
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
                    <input type="radio" name="payment" id="pay_bank" value="bank">
                    <label for="pay_bank">Chuyển khoản ngân hàng (VietQR)</label>
                </div>
                <div class="payment-method <?= $paypalEnabled ? '' : 'is-disabled' ?>" onclick="selectPayment(this)">
                    <input type="radio" name="payment" id="pay_paypal" value="paypal" <?= $paypalEnabled ? '' : 'disabled' ?>>
                    <div class="payment-method-copy">
                        <label for="pay_paypal" style="display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 1rem; color: #003087;">
                            PayPal
                            <img src="https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg" alt="Thẻ tín dụng" style="height: 32px; border: 1px solid #e0e0e0; border-radius: 4px; padding: 3px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        </label>
                        <?php if ($paypalEnabled): ?>
                            <!-- PayPal is enabled -->
                        <?php else: ?>
                            <small>Chưa khả dụng vì quản trị viên chưa cấu hình PayPal trong môi trường chạy.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <label style="display:flex;gap:.7rem;align-items:flex-start;margin-top:1.5rem;color:#444;line-height:1.6;">
                <input type="checkbox" id="termsAccepted" required style="margin-top:.35rem;width:1.1rem;height:1.1rem;accent-color:var(--primary-dark);">
                <span>Tôi đồng ý với <a href="<?= BASE_URL ?>terms" target="_blank" rel="noopener">Điều khoản mua hàng</a> và <a href="<?= BASE_URL ?>privacy" target="_blank" rel="noopener">Chính sách bảo mật</a> của Liên Hoa.</span>
            </label>

            <button type="submit" id="checkoutSubmitButton" class="client-btn" style="width: 100%; margin-top: 2rem;">Hoàn tất đặt hàng</button>
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
                <?php if ($paypalEnabled && $paypalRate > 0): ?>
                    <div id="paypalEstimateRow" style="display:none;margin-top:.9rem;padding:.85rem;border-radius:8px;background:#fff;border:1px solid #d8d8d8;color:#555;font-size:.82rem;line-height:1.55;">
                        PayPal sẽ thu khoảng <strong id="paypalEstimatedAmount">0.00 USD</strong>.<br>
                        Tỷ giá cửa hàng: 1 USD = <?= number_format($paypalRate, 0, ',', '.') ?> ₫.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= htmlspecialchars(\App\Core\App::cspNonce(), ENT_QUOTES, 'UTF-8') ?>">
let checkoutCart = [];
const PAYPAL_ENABLED = <?= json_encode($paypalEnabled) ?>;
const PAYPAL_RATE = <?= json_encode($paypalRate) ?>;
let selectedShippingFee = 0;
let shippingQuotes = [];
let shippingQuoteTimer = null;
let checkoutSubmitting = false;

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
                scheduleShippingQuote();
            }
        });
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {maximumFractionDigits: 0}).format(Math.round(price)) + ' ₫';
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
    updatePaymentUi();
}

function updatePaymentUi() {
    const method = document.querySelector('input[name="payment"]:checked')?.value || 'cod';
    const submitButton = document.getElementById('checkoutSubmitButton');
    if (submitButton && !checkoutSubmitting) {
        submitButton.textContent = method === 'paypal' ? 'Tiếp tục với PayPal' : 'Hoàn tất đặt hàng';
    }
    const paypalEstimate = document.getElementById('paypalEstimateRow');
    if (paypalEstimate) paypalEstimate.style.display = method === 'paypal' ? 'block' : 'none';
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
    const shippingFee = selectedShippingFee;
    const total = subtotal + shippingFee - appliedDiscount;
    const payableTotal = total > 0 ? total : 0;
    document.getElementById('checkoutSubtotal').textContent = formatPrice(subtotal);
    document.getElementById('checkoutShippingFee').textContent = shippingFee > 0 ? formatPrice(shippingFee) : 'Miễn phí';
    document.getElementById('checkoutTotal').textContent = formatPrice(payableTotal);
    const paypalAmount = document.getElementById('paypalEstimatedAmount');
    if (paypalAmount && PAYPAL_RATE > 0) {
        paypalAmount.textContent = (payableTotal / PAYPAL_RATE).toFixed(2) + ' USD';
    }

    const discountRow = document.getElementById('discountRow');
    if (appliedDiscount > 0) {
        discountRow.style.display = 'flex';
        document.getElementById('discountAmount').textContent = '-' + formatPrice(appliedDiscount);
    } else {
        discountRow.style.display = 'none';
    }
}

function applySavedAddress(select) {
    const option = select.options[select.selectedIndex];
    if (!option) return;
    document.getElementById('fullName').value = option.dataset.name || '';
    document.getElementById('phone').value = option.dataset.phone || '';
    document.getElementById('address').value = option.dataset.address || '';
    document.getElementById('province').value = option.dataset.province || '';
    scheduleShippingQuote();
}

function scheduleShippingQuote() {
    clearTimeout(shippingQuoteTimer);
    shippingQuoteTimer = setTimeout(loadShippingQuotes, 250);
}

function loadShippingQuotes() {
    const province = document.getElementById('province').value.trim();
    if (!province || checkoutCart.length === 0) return;
    fetch(BASE_URL + 'shipping/quote', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({province})})
        .then(r => r.json()).then(data => {
            if (!data.success) throw new Error(data.message || 'Không tính được cước');
            shippingQuotes = data.quotes || [];
            const select = document.getElementById('shippingCarrier');
            select.innerHTML = shippingQuotes.map(q => `<option value="${q.carrier_code}">${q.carrier_name} — ${formatPrice(q.fee)} (${q.estimated_days})</option>`).join('');
            selectShippingQuote();
        }).catch(error => {
            document.getElementById('shippingCarrier').innerHTML = '<option value="">Không tính được cước</option>';
            document.getElementById('shippingQuoteNote').textContent = error.message;
        });
}

function selectShippingQuote() {
    const code = document.getElementById('shippingCarrier').value;
    const quote = shippingQuotes.find(q => q.carrier_code === code);
    selectedShippingFee = quote ? parseFloat(quote.fee) : 0;
    if (quote) document.getElementById('shippingQuoteNote').textContent = `Khối lượng tính cước ${new Intl.NumberFormat('vi-VN').format(quote.chargeable_weight_grams)} g · dự kiến ${quote.estimated_days}.`;
    updateTotals();
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
                '✓ ' + data.code + ' (-' + data.discount_percent + '%, tiết kiệm ' + formatPrice(data.discount) + ')';
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
    if (checkoutSubmitting) return;

    const paymentMethod = document.querySelector('input[name="payment"]:checked')?.value || 'cod';
    if (paymentMethod === 'paypal' && !PAYPAL_ENABLED) {
        alert('PayPal chưa được cấu hình. Vui lòng chọn phương thức thanh toán khác.');
        return;
    }

    checkoutSubmitting = true;
    const submitButton = document.getElementById('checkoutSubmitButton');
    submitButton.disabled = true;
    submitButton.textContent = paymentMethod === 'paypal' ? 'Đang kết nối PayPal...' : 'Đang tạo đơn...';

    fetch(BASE_URL + 'checkout/place-order', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            shipping_name: document.getElementById('fullName').value.trim(),
            shipping_phone: document.getElementById('phone').value.trim(),
            shipping_email: document.getElementById('email').value.trim(),
            shipping_address: document.getElementById('address').value.trim(),
            shipping_province: document.getElementById('province').value.trim(),
            shipping_carrier_code: document.getElementById('shippingCarrier').value,
            customer_note: document.getElementById('note').value.trim(),
            coupon_code: appliedCouponCode,
            terms_accepted: document.getElementById('termsAccepted').checked,
            payment_method: paymentMethod
        })
    })
    .then(async response => ({ok: response.ok, data: await response.json()}))
    .then(({ok, data}) => {
        if (!ok || !data.success) {
            throw new Error(data.message || 'Không thể đặt hàng. Vui lòng thử lại.');
        }

        if (data.redirect_url) {
            window.location.assign(data.redirect_url);
            return;
        }
        localStorage.removeItem('lienhoa_cart');
        window.location.href = BASE_URL + 'checkout-success?order_id=' + data.order_id;
    })
    .catch(error => {
        alert(error.message || 'Không thể đặt hàng. Vui lòng thử lại.');
        checkoutSubmitting = false;
        submitButton.disabled = false;
        updatePaymentUi();
    });
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
