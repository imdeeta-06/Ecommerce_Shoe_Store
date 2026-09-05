<?php

namespace App\Controller;

use App\Helpers\SessionHelper;
use App\Middleware\AuthMiddleware;
use App\Models\Coupons;
use App\Models\Order;
use App\Models\UserModel;
use App\Services\OrderNotificationService;
use App\Services\PayPalService;
use App\Services\ShippingService;
use App\Services\TaxService;

class CheckoutController {
    public function index() {
        AuthMiddleware::requireLogin();
        try { (new Order())->expirePendingOrders(50); } catch (\Throwable $ignored) {}
        $userModel = new UserModel();
        $checkoutUser = $userModel->findById((int)$_SESSION['user_id']);
        $checkoutAddresses = $userModel->getAddresses((int)$_SESSION['user_id']);
        $paypalCheckout = (new PayPalService())->checkoutConfig();
        $shippingTaxCategory = TaxService::shippingCategory();
        $shippingTaxRate = TaxService::rateFor($shippingTaxCategory);
        require __DIR__ . '/../Views/checkout.php';
    }

    public function success() {
        AuthMiddleware::requireLogin();
        $orderId = (int)($_GET['order_id'] ?? 0);
        $order = null;
        if ($orderId > 0) {
            $orderModel = new Order();
            $order = $orderModel->getOrder($orderId);
            if ($order && (int)$order['user_id'] !== (int)$_SESSION['user_id']) {
                $order = null;
            }
        }
        $metaTitle = 'Đặt hàng thành công - Liên Hoa';
        $metaDescription = 'Cảm ơn quý khách đã mua sắm tại cửa hàng Liên Hoa.';
        require __DIR__ . '/../Views/checkout-success.php';
    }

    public function receipt() {
        AuthMiddleware::requireLogin();
        $orderId = (int)($_GET['order_id'] ?? 0);
        $order = (new Order())->getOrder($orderId);
        $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
        if (!$order || (!$isAdmin && (int)$order['user_id'] !== (int)$_SESSION['user_id'])) {
            http_response_code(404);
            echo 'Không tìm thấy phiếu đơn hàng.';
            return;
        }
        $store = require __DIR__ . '/../../config/store.php';
        require __DIR__ . '/../Views/order-receipt.php';
    }

    public function placeOrder() {
        header('Content-Type: application/json');
        AuthMiddleware::requireLogin();
        try { (new Order())->expirePendingOrders(50); } catch (\Throwable $ignored) {}

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
        $shippingProvince = trim((string)($input['shipping_province'] ?? ''));
        $shippingCarrierCode = trim((string)($input['shipping_carrier_code'] ?? 'standard'));
        $couponCode = trim((string)($input['coupon_code'] ?? ''));

        if (!filter_var($input['terms_accepted'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $this->jsonError('Bạn cần đồng ý với Điều khoản mua hàng và Chính sách đổi trả trước khi đặt hàng.', 400);
            return;
        }

        if ($shippingName === '' || $shippingPhone === '' || $shippingAddress === '' || $shippingProvince === '') {
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
        if (!in_array($paymentMethod, ['cod', 'bank', 'paypal'], true)) {
            $this->jsonError('Phương thức thanh toán không được hỗ trợ.', 400);
            return;
        }
        $paypalService = null;
        if ($paymentMethod === 'paypal') {
            $paypalService = new PayPalService();
            if (!$paypalService->isConfigured()) {
                $this->jsonError('PayPal chưa được cấu hình đầy đủ. Vui lòng chọn phương thức khác hoặc liên hệ quản trị viên.', 503);
                return;
            }
        }

        $orderModel = new Order();
        $result = $orderModel->placeOrder([
            'order_code' => $orderModel->generateUniqueOrderCode(),
            'user_id' => (int)$_SESSION['user_id'],
            'coupon_id' => $couponId,
            'shipping_name' => $shippingName,
            'shipping_phone' => $shippingPhone,
            'shipping_address' => $shippingAddress,
            'shipping_province' => $shippingProvince,
            'shipping_carrier_code' => $shippingCarrierCode,
            'shipping_email' => $shippingEmail !== '' ? $shippingEmail : null,
            'customer_note' => trim((string)($input['customer_note'] ?? '')),
            'payment_method' => $paymentMethod,
            'terms_accepted' => true,
            'contract_version' => 'v2.0-2026-08-27',
            'terms_accepted_ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'terms_accepted_user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000)
        ], $preparedItems);

        if (!$result['success']) {
            $this->jsonError($result['message'], 400);
            return;
        }

        $orderId = (int)$result['order_id'];
        if ($paymentMethod === 'paypal' && $paypalService instanceof PayPalService) {
            try {
                $order = $orderModel->getOrder($orderId);
                if (!$order) {
                    throw new \RuntimeException('Không tìm thấy đơn hàng vừa tạo.');
                }
                $returnUrl = $this->absoluteUrl('/paypal/return') . '?' . http_build_query(['order_id' => $orderId]);
                $cancelUrl = $this->absoluteUrl('/paypal/cancel') . '?' . http_build_query(['order_id' => $orderId]);
                $paypalOrder = $paypalService->createOrder(
                    $orderId,
                    (string)$order['order_code'],
                    (float)$order['final_amount'],
                    $returnUrl,
                    $cancelUrl
                );
                $attached = $orderModel->attachPayPalOrder(
                    $orderId,
                    $paypalOrder['id'],
                    $paypalOrder['currency'],
                    (float)$paypalOrder['value'],
                    (float)$paypalOrder['exchange_rate']
                );
                if (!$attached['success']) {
                    throw new \RuntimeException($attached['message']);
                }
                echo json_encode([
                    'success' => true,
                    'order_id' => $orderId,
                    'redirect_url' => $paypalOrder['approval_url'],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                return;
            } catch (\Throwable $error) {
                $orderModel->cancelOrder($orderId, (int)$_SESSION['user_id']);
                $this->jsonError('Không thể khởi tạo PayPal: ' . $error->getMessage(), 502);
                return;
            }
        }

        $cartModel->clearCart($_SESSION['user_id']);
        echo json_encode(['success' => true, 'order_id' => $orderId]);
    }

    public function paypalReturn() {
        AuthMiddleware::requireLogin();
        $orderId = (int)($_GET['order_id'] ?? 0);
        $paypalOrderId = trim((string)($_GET['token'] ?? ''));
        $orderModel = new Order();
        $order = $orderModel->getOrder($orderId);
        if (!$order || (int)$order['user_id'] !== (int)$_SESSION['user_id']) {
            SessionHelper::setFlash('error', 'Không tìm thấy đơn PayPal của bạn.');
            SessionHelper::redirect('checkout');
        }
        $payment = $order['payment'] ?? null;
        if (!$payment || $payment['payment_method'] !== 'paypal' || $payment['provider_order_id'] !== $paypalOrderId) {
            SessionHelper::setFlash('error', 'Mã PayPal trả về không khớp với đơn hàng.');
            SessionHelper::redirect('checkout');
        }
        if ($payment['payment_state'] === 'paid') {
            (new \App\Models\Cart())->clearCart((int)$_SESSION['user_id']);
            SessionHelper::redirect('checkout-success?order_id=' . $orderId);
        }

        try {
            $captured = (new PayPalService())->captureOrder($paypalOrderId);
            $capture = $captured['purchase_units'][0]['payments']['captures'][0] ?? null;
            $purchaseUnit = $captured['purchase_units'][0] ?? [];
            if (($captured['status'] ?? '') !== 'COMPLETED'
                || !is_array($capture)
                || ($capture['status'] ?? '') !== 'COMPLETED'
                || (string)($purchaseUnit['custom_id'] ?? '') !== (string)$orderId
                || (string)($purchaseUnit['invoice_id'] ?? '') !== (string)$order['order_code']) {
                throw new \RuntimeException('PayPal chưa xác nhận giao dịch hoàn tất.');
            }
            $amount = $capture['amount'] ?? [];
            $completed = $orderModel->completePayPalPayment(
                $orderId,
                $paypalOrderId,
                (string)($capture['id'] ?? ''),
                (string)($amount['currency_code'] ?? ''),
                (float)($amount['value'] ?? 0)
            );
            if (!$completed['success']) {
                throw new \RuntimeException($completed['message']);
            }
            (new \App\Models\Cart())->clearCart((int)$_SESSION['user_id']);
            try {
                $notificationService = new OrderNotificationService();
                // Thông báo đã được xếp hàng lúc tạo đơn PayPal; chỉ gửi sau
                // khi capture thành công để không xác nhận một đơn bị bỏ dở.
                $notificationService->processForOrder($orderId, 'order_created');
            } catch (\Throwable $notificationError) {
                // Thanh toán đã hoàn tất không được biến thành lỗi chỉ vì
                // email tạm thời chưa gửi; hàng đợi vẫn giữ thư để gửi lại.
            }
            SessionHelper::setFlash('success', 'PayPal đã xác nhận thanh toán thành công.');
            SessionHelper::redirect('checkout-success?order_id=' . $orderId);
        } catch (\Throwable $error) {
            SessionHelper::setFlash('error', 'Chưa thể xác nhận PayPal: ' . $error->getMessage());
            SessionHelper::redirect('account');
        }
    }

    public function paypalCancel() {
        AuthMiddleware::requireLogin();
        $orderId = (int)($_GET['order_id'] ?? 0);
        $paypalOrderId = trim((string)($_GET['token'] ?? ''));
        $orderModel = new Order();
        $order = $orderModel->getOrder($orderId);
        if ($order
            && (int)$order['user_id'] === (int)$_SESSION['user_id']
            && ($order['payment']['payment_method'] ?? '') === 'paypal'
            && ($order['payment']['provider_order_id'] ?? '') === $paypalOrderId
            && ($order['payment']['payment_state'] ?? '') === 'pending') {
            $orderModel->cancelOrder($orderId, (int)$_SESSION['user_id']);
        }
        SessionHelper::setFlash('error', 'Bạn đã hủy thanh toán PayPal. Giỏ hàng vẫn được giữ nguyên.');
        SessionHelper::redirect('checkout');
    }

    public function paypalWebhook() {
        header('Content-Type: application/json; charset=UTF-8');
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '' || strlen($raw) > 1048576) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Payload không hợp lệ.']);
            return;
        }
        $event = json_decode($raw, true);
        if (!is_array($event)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'JSON không hợp lệ.']);
            return;
        }
        try {
            $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
            foreach ($_SERVER as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $headers[str_replace('_', '-', substr($name, 5))] = $value;
                }
            }
            if (!(new PayPalService())->verifyWebhookSignature($headers, $event)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Chữ ký webhook PayPal không hợp lệ.']);
                return;
            }
            $result = (new Order())->processPayPalWebhook($event);
            http_response_code($result['success'] ? 200 : 500);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $error) {
            error_log('PayPal webhook error: ' . $error->getMessage());
            http_response_code(str_contains($error->getMessage(), 'PAYPAL_WEBHOOK_ID') ? 503 : 500);
            echo json_encode(['success' => false, 'message' => 'Chưa thể xử lý webhook PayPal.'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function shippingQuote() {
        header('Content-Type: application/json; charset=UTF-8');
        AuthMiddleware::requireLogin();
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $province = trim((string)($input['province'] ?? ''));
        if ($province === '') {
            $this->jsonError('Vui lòng chọn tỉnh/thành phố.', 400);
            return;
        }
        $items = (new \App\Models\Cart())->getCartByUserId((int)$_SESSION['user_id']);
        if (!$items) {
            $this->jsonError('Giỏ hàng đang trống.', 400);
            return;
        }
        $subtotal = array_reduce($items, static fn($sum, $item) => $sum + (float)$item['price'] * (int)$item['quantity'], 0.0);
        echo json_encode(['success'=>true,'quotes'=>(new ShippingService())->quotes($items,$province,$subtotal)], JSON_UNESCAPED_UNICODE);
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
            ,'product_id' => !empty($coupon['product_id']) ? (int)$coupon['product_id'] : null
            ,'category_id' => !empty($coupon['category_id']) ? (int)$coupon['category_id'] : null
        ]);
    }

    private function jsonError(string $message, int $status): void {
        http_response_code($status);
        echo json_encode(['success' => false, 'message' => $message]);
    }

    private function absoluteUrl(string $path): string {
        $configured = rtrim(trim((string)(\App\Core\App::env('APP_PUBLIC_URL') ?: '')), '/');
        if (preg_match('#^https?://#i', $configured)) {
            return $configured . '/' . ltrim($path, '/');
        }
        $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0])) === 'https';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $basePath = rtrim((string)BASE_URL, '/');
        return ($https ? 'https://' : 'http://') . $host . $basePath . '/' . ltrim($path, '/');
    }
}
