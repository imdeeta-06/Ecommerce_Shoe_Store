<?php
namespace App\Controller;

use App\Models\Coupons;
use App\Models\Database;
use App\Models\Order;

class CheckoutController {
    public function index() {
        require __DIR__ . '/../Views/checkout.php';
    }

    public function success() {
        require __DIR__ . '/../Views/checkout-success.php';
    }

    public function placeOrder() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đặt hàng.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $cartModel = new \App\Models\Cart();
        $items = $cartModel->getCartByUserId($_SESSION['user_id']);

        if (empty($items)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Giỏ hàng đang trống.']);
            return;
        }

        $shippingName = trim($input['shipping_name'] ?? '');
        $shippingPhone = trim($input['shipping_phone'] ?? '');
        $shippingAddress = trim($input['shipping_address'] ?? '');
        $shippingEmail = trim($input['shipping_email'] ?? '');
        $couponId = isset($input['coupon_id']) && $input['coupon_id'] !== '' ? (int) $input['coupon_id'] : null;
        $discount = max(0, (float) ($input['discount'] ?? 0));

        if ($shippingName === '' || $shippingPhone === '' || $shippingAddress === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin giao hàng.']);
            return;
        }

        $totalAmount = 0;
        $preparedItems = [];
        $db = Database::getInstance()->getConnection();

        foreach ($items as $item) {
            $quantity = max(0, (int) ($item['quantity'] ?? 0));
            $price = max(0, (float) ($item['price'] ?? 0));
            $totalAmount += $quantity * $price;

            if ($quantity <= 0 || $price < 0) {
                continue;
            }

            $variantId = $this->resolveVariantId($db, $item, $quantity);
            if (!$variantId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Sản phẩm "' . ($item['name'] ?? 'trong giỏ hàng') . '" chưa có biến thể hoặc không đủ tồn kho.'
                ]);
                return;
            }

            $preparedItems[] = [
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $price
            ];
        }

        if ($totalAmount <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tổng đơn hàng không hợp lệ.']);
            return;
        }

        $finalAmount = max(0, $totalAmount - $discount);
        $orderModel = new Order();
        $result = $orderModel->placeOrder([
            'order_code' => $orderModel->generateUniqueOrderCode(),
            'user_id' => $_SESSION['user_id'],
            'total_amount' => $totalAmount,
            'coupon_id' => $couponId,
            'final_amount' => $finalAmount,
            'shipping_name' => $shippingName,
            'shipping_phone' => $shippingPhone,
            'shipping_address' => $shippingAddress,
            'shipping_email' => $shippingEmail !== '' ? $shippingEmail : null,
            'status' => 'pending'
        ], $preparedItems);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode($result);
            return;
        }
        
        $cartModel->clearCart($_SESSION['user_id']);

        echo json_encode(['success' => true, 'order_id' => $result['order_id']]);
    }

    private function resolveVariantId(\PDO $db, array $item, int $quantity): ?int {
        $variantId = (int) ($item['variant_id'] ?? 0);
        if ($variantId > 0) {
            return $variantId;
        }

        $productId = (int) ($item['product_id'] ?? 0);
        if ($productId <= 0) {
            return null;
        }

        $stmt = $db->prepare("
            SELECT id
            FROM product_variants
            WHERE product_id = :product_id
              AND stock_quantity >= :quantity
            ORDER BY stock_quantity DESC, id ASC
            LIMIT 1
        ");
        $stmt->execute([
            'product_id' => $productId,
            'quantity' => $quantity
        ]);

        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    public function applyCoupon() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $code = trim($input['code'] ?? '');
        $orderTotal = floatval($input['order_total'] ?? 0);

        if (!$code) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá.']);
            return;
        }

        $model = new Coupons();
        $result = $model->validateCoupon($code, $orderTotal);

        if (!$result['is_valid']) {
            echo json_encode(['success' => false, 'message' => $result['message']]);
            return;
        }

        $coupon = $result['data'];
        $discount = 0;
        $discountPercent = floatval($coupon['discount_percent'] ?? 0);
        $maxDiscount = floatval($coupon['max_discount'] ?? 0);

        if ($discountPercent > 0) {
            $discount = $orderTotal * ($discountPercent / 100);
            if ($maxDiscount > 0 && $discount > $maxDiscount) {
                $discount = $maxDiscount;
            }
        } elseif ($maxDiscount > 0) {
            $discount = $maxDiscount;
        }

        echo json_encode([
            'success' => true,
            'discount' => $discount,
            'discount_percent' => floatval($coupon['discount_percent']),
            'code' => $coupon['code'],
            'coupon_id' => $coupon['id']
        ]);
    }
}
