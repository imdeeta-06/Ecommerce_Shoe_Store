<?php

namespace App\Models;

use PDO;

class Cart extends BaseModel {
    private ?string $productColumn = null;

    public function __construct() {
        parent::__construct();
        $this->ensureProductColumn();
    }

    // --- CART ---
    public function createCartItem($data) {
        $data = $this->normalizeCartData($data);
        return $this->insert('cart', $data);
    }
    public function getCartItem($id) { return $this->getById('cart', $id); }
    public function updateCartQuantity($id, $quantity) { return $this->update('cart', $id, ['quantity' => $quantity]); }
    public function deleteCartItem($id) { return $this->delete('cart', $id); }

    public function getCartByUserId($userId) {
        $stmt = $this->db->prepare($this->cartSelectSql("c.user_id = :user_id"));
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCartBySessionId($sessionId) {
        $stmt = $this->db->prepare($this->cartSelectSql("c.session_id = :session_id"));
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function checkExists($userId, $sessionId, $productId) {
        $column = $this->getProductColumn();
        $lookupId = $column === 'variant_id' ? $this->resolveVariantId($productId) : $productId;

        if (!$lookupId) {
            return false;
        }

        if ($userId) {
            $stmt = $this->db->prepare("SELECT id, quantity FROM cart WHERE user_id = :user_id AND {$column} = :product_id");
            $stmt->execute(['user_id' => $userId, 'product_id' => $lookupId]);
        } else {
            $stmt = $this->db->prepare("SELECT id, quantity FROM cart WHERE session_id = :session_id AND {$column} = :product_id");
            $stmt->execute(['session_id' => $sessionId, 'product_id' => $lookupId]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Đếm tổng số lượng sản phẩm trong giỏ hàng (dùng cho badge trên navbar)
    public function countCartItems($userId = null, $sessionId = null) {
        if ($userId) {
            $stmt = $this->db->prepare("SELECT SUM(quantity) as total_quantity FROM cart WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
        } elseif ($sessionId) {
            $stmt = $this->db->prepare("SELECT SUM(quantity) as total_quantity FROM cart WHERE session_id = :session_id");
            $stmt->execute(['session_id' => $sessionId]);
        } else {
            return 0;
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_quantity'] ? (int)$result['total_quantity'] : 0;
    }

    public function mergeGuestCartIntoUser($sessionId, $userId) {
        $guestCartItems = $this->getCartBySessionId($sessionId);
        
        if (empty($guestCartItems)) {
            return false;
        }

        foreach ($guestCartItems as $guestItem) {
            $productId = $guestItem['product_id'];
            $guestQuantity = $guestItem['quantity'];

            // Kiểm tra xem user đã có sản phẩm này trong giỏ hàng chưa
            $column = $this->getProductColumn();
            $lookupId = $column === 'variant_id' ? ($guestItem['variant_id'] ?? $this->resolveVariantId($productId)) : $productId;
            $stmt = $this->db->prepare("SELECT * FROM cart WHERE user_id = :user_id AND {$column} = :product_id");
            $stmt->execute(['user_id' => $userId, 'product_id' => $lookupId]);
            $userItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userItem) {
                // Nếu đã có, cộng dồn số lượng
                $newQuantity = $userItem['quantity'] + $guestQuantity;
                $this->updateCartQuantity($userItem['id'], $newQuantity);
                // Xóa item của session
                $this->deleteCartItem($guestItem['id']);
            } else {
                // Nếu chưa có, chuyển item của session sang cho user
                $stmtUpdate = $this->db->prepare("UPDATE cart SET user_id = :user_id, session_id = NULL WHERE id = :id");
                $stmtUpdate->execute(['user_id' => $userId, 'id' => $guestItem['id']]);
            }
        }
        return true;
    }

    // Xóa toàn bộ giỏ hàng (sau khi đặt hàng thành công)
    public function clearCart($userId = null, $sessionId = null) {
        if ($userId) {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = :user_id");
            return $stmt->execute(['user_id' => $userId]);
        } elseif ($sessionId) {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE session_id = :session_id");
            return $stmt->execute(['session_id' => $sessionId]);
        }
        return false;
    }

    private function ensureProductColumn(): void {
        if ($this->tableHasColumn('cart', 'product_id')) {
            $this->fixProductIdForeignKey();
            $this->productColumn = 'product_id';
            return;
        }

        if ($this->tableHasColumn('cart', 'variant_id')) {
            try {
                $this->db->exec("ALTER TABLE `cart` ADD `product_id` INT(11) NULL DEFAULT NULL");
                $this->db->exec("
                    UPDATE cart c
                    JOIN product_variants pv ON c.variant_id = pv.id
                    SET c.product_id = pv.product_id
                    WHERE c.product_id IS NULL
                ");
                $this->fixProductIdForeignKey();
                $this->productColumn = 'product_id';
                return;
            } catch (\PDOException $e) {
                $this->productColumn = 'variant_id';
                return;
            }
        }

        $this->productColumn = 'product_id';
    }

    private function fixProductIdForeignKey(): void {
        try {
            $foreignKeys = $this->getForeignKeysForColumn('cart', 'product_id');
            $hasCorrectForeignKey = false;

            foreach ($foreignKeys as $foreignKey) {
                $constraintName = $foreignKey['CONSTRAINT_NAME'];
                $referencedTable = strtolower((string)$foreignKey['REFERENCED_TABLE_NAME']);
                $referencedColumn = strtolower((string)$foreignKey['REFERENCED_COLUMN_NAME']);

                if ($referencedTable === 'product' && $referencedColumn === 'id') {
                    $hasCorrectForeignKey = true;
                    continue;
                }

                $this->db->exec("ALTER TABLE `cart` DROP FOREIGN KEY `{$constraintName}`");
            }

            $this->db->exec("
                UPDATE cart c
                LEFT JOIN product p ON c.product_id = p.id
                LEFT JOIN product_variants pv ON c.product_id = pv.id
                SET c.product_id = pv.product_id
                WHERE c.product_id IS NOT NULL
                  AND p.id IS NULL
                  AND pv.id IS NOT NULL
            ");

            $this->db->exec("
                UPDATE cart c
                LEFT JOIN product p ON c.product_id = p.id
                SET c.product_id = NULL
                WHERE c.product_id IS NOT NULL
                  AND p.id IS NULL
            ");

            if (!$hasCorrectForeignKey) {
                $this->db->exec("
                    ALTER TABLE `cart`
                    ADD CONSTRAINT `cart_product_fk`
                    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
                    ON DELETE CASCADE
                ");
            }
        } catch (\PDOException $e) {
            // If the local DB user cannot alter constraints, cart still works once the SQL fix is run manually.
        }
    }

    private function getProductColumn(): string {
        if ($this->productColumn === null) {
            $this->ensureProductColumn();
        }

        return $this->productColumn;
    }

    private function normalizeCartData(array $data): array {
        $column = $this->getProductColumn();

        if ($column === 'product_id') {
            unset($data['variant_id']);
            return $data;
        }

        $productId = (int)($data['product_id'] ?? 0);
        unset($data['product_id']);
        $data['variant_id'] = $this->resolveVariantId($productId);

        return $data;
    }

    private function cartSelectSql(string $where): string {
        if ($this->getProductColumn() === 'variant_id') {
            return "
                SELECT c.*, pv.product_id, p.name, p.base_price as price, p.slug, pi.image_url
                FROM cart c
                JOIN product_variants pv ON c.variant_id = pv.id
                JOIN product p ON pv.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE {$where}
            ";
        }

        return "
            SELECT c.*, p.name, p.base_price as price, p.slug, pi.image_url
            FROM cart c
            JOIN product p ON c.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE {$where}
        ";
    }

    private function resolveVariantId(int $productId): ?int {
        if ($productId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT id
            FROM product_variants
            WHERE product_id = :product_id
            ORDER BY stock_quantity DESC, id ASC
            LIMIT 1
        ");
        $stmt->execute(['product_id' => $productId]);
        $id = $stmt->fetchColumn();

        return $id ? (int)$id : null;
    }

    private function tableHasColumn(string $table, string $column): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
        ");
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function getForeignKeysForColumn(string $table, string $column): array {
        $stmt = $this->db->prepare("
            SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
