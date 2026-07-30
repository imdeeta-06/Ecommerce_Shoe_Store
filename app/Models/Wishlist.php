<?php

namespace App\Models;

use PDO;

class Wishlist extends BaseModel {
    protected $table = 'wishlist';

    public function __construct() {
        parent::__construct();
    }

    public function getWishlistByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT w.*, p.name, p.base_price as price, p.slug, pi.image_url 
            FROM {$this->table} w
            JOIN product p ON w.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE w.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkExists($userId, $productId) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE user_id = :user_id AND product_id = :product_id");
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
