<?php

namespace App\Models;

use PDO;

class Review extends BaseModel {
    public function createVerifiedReview(int $userId, int $orderItemId, int $rating, string $comment): array {
        $rating = max(1, min(5, $rating));
        $stmt = $this->db->prepare("SELECT oi.id, oi.order_id, oi.variant_id, pv.product_id, o.status, o.user_id
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            LEFT JOIN product_variants pv ON pv.id = oi.variant_id
            WHERE oi.id = :order_item_id AND o.user_id = :user_id
            LIMIT 1");
        $stmt->execute(['order_item_id' => $orderItemId, 'user_id' => $userId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item || !in_array($item['status'], ['delivered', 'completed'], true) || empty($item['product_id'])) {
            return ['success' => false, 'message' => 'Chỉ khách đã mua và nhận hàng thành công mới được đánh giá.'];
        }

        $exists = $this->db->prepare('SELECT id FROM reviews WHERE user_id = :user_id AND order_item_id = :order_item_id LIMIT 1');
        $exists->execute(['user_id' => $userId, 'order_item_id' => $orderItemId]);
        if ($exists->fetchColumn()) {
            return ['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này trong đơn hàng.'];
        }

        $stmt = $this->db->prepare('INSERT INTO reviews (user_id, product_id, order_id, order_item_id, rating, comment, status) VALUES (:user_id, :product_id, :order_id, :order_item_id, :rating, :comment, 1)');
        $stmt->execute([
            'user_id' => $userId,
            'product_id' => (int)$item['product_id'],
            'order_id' => (int)$item['order_id'],
            'order_item_id' => $orderItemId,
            'rating' => $rating,
            'comment' => trim($comment)
        ]);

        return ['success' => true, 'message' => 'Cảm ơn bạn đã đánh giá sản phẩm.'];
    }
}
