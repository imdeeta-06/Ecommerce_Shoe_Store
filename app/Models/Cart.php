<?php

namespace App\Models;

use PDO;

class Cart extends BaseModel {
    public function createCartItem($data) {
        $variantId = (int)($data['variant_id'] ?? 0);
        if ($variantId <= 0) {
            throw new \InvalidArgumentException('Giỏ hàng phải gắn với một phân loại sản phẩm cụ thể.');
        }

        return $this->insert('cart', [
            'user_id' => $data['user_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'variant_id' => $variantId,
            'quantity' => max(1, (int)($data['quantity'] ?? 1))
        ]);
    }

    public function getCartItem($id) {
        return $this->getById('cart', (int)$id);
    }

    public function getCartItemForOwner($id, $userId = null, $sessionId = null) {
        $where = 'c.id = :id';
        $params = ['id' => (int)$id];

        if ($userId) {
            $where .= ' AND c.user_id = :user_id';
            $params['user_id'] = (int)$userId;
        } else {
            $where .= ' AND c.session_id = :session_id AND c.user_id IS NULL';
            $params['session_id'] = (string)$sessionId;
        }

        $stmt = $this->db->prepare($this->cartSelectSql($where));
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCartQuantity($id, $quantity, $userId = null, $sessionId = null) {
        $item = $this->getCartItemForOwner($id, $userId, $sessionId);
        if (!$item) {
            return false;
        }

        $quantity = max(1, (int)$quantity);
        if ((int)$item['stock_quantity'] < $quantity) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE cart SET quantity = :quantity WHERE id = :id');
        return $stmt->execute(['quantity' => $quantity, 'id' => (int)$id]);
    }

    public function deleteCartItem($id, $userId = null, $sessionId = null) {
        $item = $this->getCartItemForOwner($id, $userId, $sessionId);
        if (!$item) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM cart WHERE id = :id');
        return $stmt->execute(['id' => (int)$id]);
    }

    public function getCartByUserId($userId) {
        $stmt = $this->db->prepare($this->cartSelectSql('c.user_id = :user_id'));
        $stmt->execute(['user_id' => (int)$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->touchReminder((int)$userId, !empty($items));
        return $items;
    }

    public function getCartBySessionId($sessionId) {
        $stmt = $this->db->prepare($this->cartSelectSql('c.session_id = :session_id AND c.user_id IS NULL'));
        $stmt->execute(['session_id' => (string)$sessionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkExists($userId, $sessionId, $variantId) {
        $where = 'variant_id = :variant_id';
        $params = ['variant_id' => (int)$variantId];

        if ($userId) {
            $where .= ' AND user_id = :user_id';
            $params['user_id'] = (int)$userId;
        } else {
            $where .= ' AND session_id = :session_id AND user_id IS NULL';
            $params['session_id'] = (string)$sessionId;
        }

        $stmt = $this->db->prepare("SELECT id, quantity FROM cart WHERE {$where} LIMIT 1");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countCartItems($userId = null, $sessionId = null) {
        if ($userId) {
            $stmt = $this->db->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = :user_id');
            $stmt->execute(['user_id' => (int)$userId]);
        } elseif ($sessionId) {
            $stmt = $this->db->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = :session_id AND user_id IS NULL');
            $stmt->execute(['session_id' => (string)$sessionId]);
        } else {
            return 0;
        }

        return (int)$stmt->fetchColumn();
    }

    public function mergeGuestCartIntoUser($sessionId, $userId) {
        $guestItems = $this->getCartBySessionId($sessionId);
        foreach ($guestItems as $guestItem) {
            $existing = $this->checkExists($userId, null, (int)$guestItem['variant_id']);
            if ($existing) {
                $merged = $this->updateCartQuantity(
                    (int)$existing['id'],
                    (int)$existing['quantity'] + (int)$guestItem['quantity'],
                    $userId,
                    null
                );
                if ($merged) {
                    $this->db->prepare('DELETE FROM cart WHERE id = :id AND session_id = :session_id')
                        ->execute(['id' => (int)$guestItem['id'], 'session_id' => (string)$sessionId]);
                }
            } else {
                $this->db->prepare('UPDATE cart SET user_id = :user_id, session_id = NULL WHERE id = :id AND session_id = :session_id')
                    ->execute([
                        'user_id' => (int)$userId,
                        'id' => (int)$guestItem['id'],
                        'session_id' => (string)$sessionId
                    ]);
            }
        }

        return !empty($guestItems);
    }

    public function clearCart($userId = null, $sessionId = null) {
        if ($userId) {
            $stmt = $this->db->prepare('DELETE FROM cart WHERE user_id = :user_id');
            $result = $stmt->execute(['user_id' => (int)$userId]);
            $this->markReminderConverted((int)$userId);
            return $result;
        }

        if ($sessionId) {
            return $this->db->prepare('DELETE FROM cart WHERE session_id = :session_id AND user_id IS NULL')
                ->execute(['session_id' => (string)$sessionId]);
        }

        return false;
    }

    public function getAbandonedReminders($limit = 100, bool $queueOnly = true) {
        if (!$this->tableExists('cart_reminders')) {
            return [];
        }

        $sql = "SELECT cr.*, u.full_name, u.email
            FROM cart_reminders cr
            JOIN user u ON u.id = cr.user_id
            WHERE 1=1";
        if ($queueOnly) {
            $sql .= " AND cr.status IN ('pending', 'failed')
              AND cr.unsubscribed_at IS NULL
              AND cr.converted_at IS NULL
              AND cr.attempt_count < 3
              AND cr.last_seen_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              AND (cr.next_attempt_at IS NULL OR cr.next_attempt_at <= NOW())
              AND EXISTS (SELECT 1 FROM cart c WHERE c.user_id = cr.user_id)";
        }
        $sql .= " ORDER BY cr.last_seen_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCartItemsForReminder(int $userId): array {
        if ($userId <= 0) {
            return [];
        }

        $stmt = $this->db->prepare($this->cartSelectSql('c.user_id = :user_id'));
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markReminderSent(int $id): bool {
        $stmt = $this->db->prepare("UPDATE cart_reminders
            SET status = 'sent', sent_at = NOW(), attempt_count = attempt_count + 1,
                next_attempt_at = NULL, last_error = NULL
            WHERE id = :id AND unsubscribed_at IS NULL AND converted_at IS NULL");
        return $stmt->execute(['id' => $id]) && $stmt->rowCount() === 1;
    }

    public function markReminderFailed(int $id, string $error): bool {
        $stmt = $this->db->prepare("UPDATE cart_reminders
            SET status = 'failed', sent_at = NULL, attempt_count = attempt_count + 1,
                next_attempt_at = DATE_ADD(NOW(), INTERVAL 1 HOUR), last_error = :last_error
            WHERE id = :id AND unsubscribed_at IS NULL AND converted_at IS NULL");
        return $stmt->execute(['id' => $id, 'last_error' => trim($error)]) && $stmt->rowCount() === 1;
    }

    public function unsubscribeByToken(string $token): bool {
        $token = trim($token);
        if ($token === '') {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE cart_reminders
            SET status = 'unsubscribed', unsubscribed_at = NOW(), next_attempt_at = NULL
            WHERE unsubscribe_token = :token");
        $stmt->execute(['token' => $token]);
        return $stmt->rowCount() === 1;
    }

    private function cartSelectSql(string $where): string {
        return "SELECT c.*, pv.product_id, p.category_id, pv.size, pv.color, pv.stock_quantity,
                       p.name, p.slug, p.base_price, (p.base_price + COALESCE(pv.price_modifier, 0)) AS price,
                       pi.image_url
                FROM cart c
                JOIN product_variants pv ON pv.id = c.variant_id
                JOIN product p ON p.id = pv.product_id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE {$where}
                ORDER BY c.id ASC";
    }

    private function touchReminder(int $userId, bool $hasItems): void {
        if (!$hasItems || !$this->tableExists('cart_reminders')) {
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO cart_reminders (user_id, unsubscribe_token, status, last_seen_at, attempt_count, next_attempt_at)
            VALUES (:user_id, LOWER(HEX(RANDOM_BYTES(24))), 'pending', NOW(), 0, NULL)
            ON DUPLICATE KEY UPDATE
                status = IF(unsubscribed_at IS NULL, 'pending', status),
                last_seen_at = IF(unsubscribed_at IS NULL, NOW(), last_seen_at),
                converted_at = IF(unsubscribed_at IS NULL, NULL, converted_at),
                sent_at = IF(unsubscribed_at IS NULL, NULL, sent_at),
                attempt_count = IF(unsubscribed_at IS NULL, 0, attempt_count),
                next_attempt_at = IF(unsubscribed_at IS NULL, NULL, next_attempt_at),
                last_error = IF(unsubscribed_at IS NULL, NULL, last_error)");
        $stmt->execute(['user_id' => $userId]);
    }

    public function markReminderConverted(int $userId): void {
        if (!$this->tableExists('cart_reminders')) {
            return;
        }

        $stmt = $this->db->prepare("UPDATE cart_reminders SET status = 'converted', converted_at = NOW() WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
    }

    private function tableExists(string $table): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
        $stmt->execute(['table_name' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
