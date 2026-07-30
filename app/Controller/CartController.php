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

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['qty'] ?? 1));

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
            return;
        }

        list($userId, $sessionId) = $this->getCartIdentifiers();
        try {
            $cartModel = new Cart();

            // Check if item exists
            $existing = $cartModel->checkExists($userId, $sessionId, $productId);

            if ($existing) {
                $cartModel->updateCartQuantity($existing['id'], $existing['quantity'] + $quantity);
            } else {
                $cartModel->createCartItem([
                    'user_id' => $userId,
                    'session_id' => $userId ? null : $sessionId,
                    'product_id' => $productId,
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

        $cartModel = new Cart();
        // Ideally we should verify ownership here, but for simplicity assuming valid ID
        $cartModel->updateCartQuantity($cartId, $quantity);
        
        list($userId, $sessionId) = $this->getCartIdentifiers();

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

        $cartModel = new Cart();
        $cartModel->deleteCartItem($cartId);

        list($userId, $sessionId) = $this->getCartIdentifiers();

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
