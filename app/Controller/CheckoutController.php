<?php

namespace App\Controller;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\Coupons;
use App\Models\Order;

class CheckoutController {
    public function index() {
        AuthMiddleware::requireLogin();
        require __DIR__ . '/../Views/checkout.php';
    }

    public function success() {
        AuthMiddleware::requireLogin();
        require __DIR__ . '/../Views/checkout-success.php';
    }

    public function placeOrder() {
        header('Content-Type: application/json');
        AuthMiddleware::requireLogin();

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $cartModel = new \App\Models\Cart();
        $items = $cartModel->getCartByUserId($_SESSION['user_id']);

        if (empty($items)) {
            $this->jsonError('Giỏ hàng đang trống.', 400);
            return;
        }

        $shippingName = trim((string)($input['shipping_name'] ?? ''));
        $shippingPhone = trim((string)($input['shipping_phone'] ?? ''));
        $shippingAddress = trim((string)($input['shipping_address'] ?? ''));
        $shippingEmail = trim((string)($input['shipping_email'] ?? ''));
        $couponCode = trim((string)($input['coupon_code'] ?? ''));

        if (!filter_var($input['terms_accepted'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $this->jsonError('Bạn cần đồng ý với Điều khoản mua hàng và Chính sách đổi trả trước khi đặt hàng.', 400);
            return;
        }

        if ($shippingName === '' || $shippingPhone === '' || $shippingAddress === '') {
            $this->jsonError('Vui lòng nhập đầy đủ thông tin giao hàng.', 400);
            return;
        }
        if (!preg_match('/^[0-9+\-\s().]{7,20}$/', $shippingPhone)) {
            $this->jsonError('Số điện thoại giao hàng không hợp lệ.', 400);
            return;
        }

        $preparedItems = [];
        foreach ($items as $item) {
            $variantId = (int)($item['variant_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            if ($variantId <= 0 || $quantity <= 0) {
                $this->jsonError('Giỏ hàng có sản phẩm chưa gắn đúng size/màu. Vui lòng chọn lại.', 400);
                return;
            }
            $preparedItems[] = ['variant_id' => $variantId, 'quantity' => $quantity];
        }

        $couponId = null;
        if ($couponCode !== '') {
            $couponModel = new Coupons();
            $subtotal = array_reduce($items, static function ($total, $item) {
                return $total + (float)$item['price'] * (int)$item['quantity'];
            }, 0.0);
            $couponResult = $couponModel->validateCoupon($couponCode, $subtotal, $_SESSION['user_id'], $items);
            if (!$couponResult['is_valid']) {
                $this->jsonError($couponResult['message'], 400);
                return;
            }
            $couponId = (int)$couponResult['data']['id'];
        }

        $paymentMethod = (string)($input['payment_method'] ?? 'cod');
        if ($paymentMethod !== 'cod') {
            $this->jsonError('Hiện tại PaceUp chỉ hỗ trợ thanh toán khi nhận hàng (COD). Chuyển khoản và ví điện tử sẽ được bổ sung sau.', 400);
            return;
        }

        $orderModel = new Order();
        $result = $orderModel->placeOrder([
            'order_code' => $orderModel->generateUniqueOrderCode(),
            'user_id' => (int)$_SESSION['user_id'],
            'coupon_id' => $couponId,
            'shipping_name' => $shippingName,
            'shipping_phone' => $shippingPhone,
            'shipping_address' => $shippingAddress,
            'shipping_email' => $shippingEmail !== '' ? $shippingEmail : null,
            'customer_note' => trim((string)($input['customer_note'] ?? '')),
            'payment_method' => $paymentMethod,
            'terms_accepted' => true,
            'contract_version' => 'v1.0',
            'terms_accepted_ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'terms_accepted_user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000)
        ], $preparedItems);

        if (!$result['success']) {
            $this->jsonError($result['message'], 400);
            return;
        }

        $cartModel->clearCart($_SESSION['user_id']);
        echo json_encode(['success' => true, 'order_id' => $result['order_id']]);
    }

    public function applyCoupon() {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng mã giảm giá.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $code = trim((string)($input['code'] ?? ''));
        if ($code === '') {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá.']);
            return;
        }

        $cartModel = new \App\Models\Cart();
        $items = $cartModel->getCartByUserId($_SESSION['user_id']);
        $orderTotal = array_reduce($items, static function ($total, $item) {
            return $total + (float)$item['price'] * (int)$item['quantity'];
        }, 0.0);

        $result = (new Coupons())->validateCoupon($code, $orderTotal, $_SESSION['user_id'], $items);
        if (!$result['is_valid']) {
            echo json_encode(['success' => false, 'message' => $result['message']]);
            return;
        }

        $coupon = $result['data'];
        echo json_encode([
            'success' => true,
            'discount' => (float)$result['discount'],
            'discount_percent' => (float)($coupon['discount_percent'] ?? 0),
            'code' => $coupon['code'],
            'coupon_id' => (int)$coupon['id']
        ]);
    }

    private function jsonError(string $message, int $status): void {
        http_response_code($status);
        echo json_encode(['success' => false, 'message' => $message]);
    }
}
