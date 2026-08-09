<?php
namespace App\Controller;

use App\Models\Wishlist;

class WishlistController {
    public function index() {
        $wishlistModel = new Wishlist();
        $wishlistItems = [];
        
        if (isset($_SESSION['user_id'])) {
            $wishlistItems = $wishlistModel->getWishlistByUserId($_SESSION['user_id']);
        }
        
        require __DIR__ . '/../Views/wishlist.php';
    }

    public function add() {
        if (!isset($_SESSION['user_id'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thêm vào danh sách yêu thích.']);
                return;
            }
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : (isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0);

        if ($productId > 0) {
            $wishlistModel = new Wishlist();
            $db = \App\Models\Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT p.id FROM product p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = :id AND p.status = 1 AND (p.category_id IS NULL OR c.status = 1) LIMIT 1');
            $stmt->execute(['id' => $productId]);
            if (!$stmt->fetchColumn()) {
                $message = 'Sản phẩm không còn được bán hoặc danh mục đã ẩn.';
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode(['success' => false, 'message' => $message]);
                    return;
                }
                header('Location: ' . BASE_URL . 'wishlist');
                exit;
            }
            $exists = $wishlistModel->checkExists($userId, $productId);

            if (!$exists) {
                $wishlistModel->insert('wishlist', [
                    'user_id' => $userId,
                    'product_id' => $productId
                ]);
            }
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'message' => 'Đã thêm vào danh sách yêu thích.']);
                return;
            } else {
                $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'wishlist';
                header('Location: ' . $referer);
                exit;
            }
        }
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ.']);
        } else {
            header('Location: ' . BASE_URL . 'wishlist');
        }
    }

    public function remove() {
        if (!isset($_SESSION['user_id'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập.']);
                return;
            }
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : (isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0);

        if ($productId > 0) {
            $wishlistModel = new Wishlist();
            $item = $wishlistModel->checkExists($userId, $productId);
            
            if ($item) {
                $wishlistModel->delete('wishlist', $item['id']);
            }
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'message' => 'Đã xóa khỏi danh sách yêu thích.']);
                return;
            } else {
                $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'wishlist';
                header('Location: ' . $referer);
                exit;
            }
        }
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ.']);
        } else {
            header('Location: ' . BASE_URL . 'wishlist');
        }
    }
}
