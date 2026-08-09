<?php
namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Models\Cart;

class CartController {
    public function index() {
        AuthMiddleware::requireLogin();
        require __DIR__ . '/../Views/cart.php';
    }

    private function getCartIdentifiers() {
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        return [$userId, $sessionId];
    }

    public function add() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $variantId = (int)($_POST['variant_id'] ?? 0);
        $quantity = max(1, (int)($_POST['qty'] ?? 1));

        if ($variantId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn đúng size và màu trước khi thêm vào giỏ.']);
            return;
        }

        list($userId, $sessionId) = $this->getCartIdentifiers();
        try {
            $cartModel = new Cart();

            $db = \App\Models\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT pv.id, pv.stock_quantity, p.name, p.status
                                  FROM product_variants pv
                                  JOIN product p ON p.id = pv.product_id
                                  WHERE pv.id = :variant_id LIMIT 1");
            $stmt->execute(['variant_id' => $variantId]);
            $variant = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$variant || (int)$variant['status'] !== 1) {
                echo json_encode(['success' => false, 'message' => 'Phân loại sản phẩm không tồn tại hoặc đã ẩn.']);
                return;
            }

            if ((int)$variant['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không đủ tồn kho. Hiện còn ' . (int)$variant['stock_quantity'] . ' sản phẩm.']);
                return;
            }

            $existing = $cartModel->checkExists($userId, $sessionId, $variantId);

            if ($existing) {
                $newQuantity = (int)$existing['quantity'] + $quantity;
                if ($newQuantity > (int)$variant['stock_quantity']) {
                    echo json_encode(['success' => false, 'message' => 'Số lượng trong giỏ vượt quá tồn kho hiện có.']);
                    return;
                }
                $cartModel->updateCartQuantity($existing['id'], $newQuantity, $userId, $sessionId);
            } else {
                $cartModel->createCartItem([
                    'user_id' => $userId,
                    'session_id' => $userId ? null : $sessionId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đã thêm vào giỏ hàng',
                'cart_count' => $cartModel->countCartItems($userId, $sessionId)
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thêm được vào giỏ: ' . $e->getMessage()
            ]);
        }
    }

    public function update() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $cartId = (int)($_POST['cart_id'] ?? 0);
        $quantity = (int)($_POST['qty'] ?? 1);

        if ($cartId <= 0 || $quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }

        list($userId, $sessionId) = $this->getCartIdentifiers();
        $cartModel = new Cart();
        if (!$cartModel->updateCartQuantity($cartId, $quantity, $userId, $sessionId)) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt tồn kho hoặc sản phẩm không thuộc giỏ hàng của bạn.']);
            return;
        }

        echo json_encode([
            'success' => true, 
            'cart_count' => $cartModel->countCartItems($userId, $sessionId)
        ]);
    }

    public function remove() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $cartId = (int)($_POST['cart_id'] ?? 0);

        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }

        list($userId, $sessionId) = $this->getCartIdentifiers();
        $cartModel = new Cart();
        if (!$cartModel->deleteCartItem($cartId, $userId, $sessionId)) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ hàng.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'cart_count' => $cartModel->countCartItems($userId, $sessionId)
        ]);
    }

    public function get() {
        header('Content-Type: application/json');
        list($userId, $sessionId) = $this->getCartIdentifiers();
        $cartModel = new Cart();

        if ($userId) {
            $items = $cartModel->getCartByUserId($userId);
        } else {
            $items = $cartModel->getCartBySessionId($sessionId);
        }
        
        $totalQuantity = $cartModel->countCartItems($userId, $sessionId);

        echo json_encode([
            'success' => true,
            'items' => $items,
            'cart_count' => $totalQuantity
        ]);
    }
}
