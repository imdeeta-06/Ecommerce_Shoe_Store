<?php

namespace App\Models;

use PDO;
use Throwable;

class Order extends BaseModel {
    private const VALID_STATUSES = ['pending', 'confirmed', 'shipping', 'delivered', 'canceled'];

    private array $columnCache = [];

    public function __construct() {
        parent::__construct();
    }

    // --- ORDERS ---
    public function createOrder($data) { return $this->insert('orders', $data); }

    public function getOrder($id) { return $this->getById($id); }

    public function getById($tableOrId, $id = null) {
        if ($id !== null) {
            return parent::getById($tableOrId, $id);
        }

        $stmt = $this->db->prepare("
            SELECT o.*, u.full_name AS user_name, u.email AS user_email
            FROM orders o
            LEFT JOIN user u ON o.user_id = u.id
            WHERE o.id = :id
        ");
        $stmt->execute(['id' => (int)$tableOrId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return false;
        }

        $order['items'] = $this->getOrderItems((int)$order['id']);
        $order['payment'] = $this->getPayment((int)$order['id']);
        $order['status_logs'] = $this->getStatusLogs((int)$order['id']);

        return $order;
    }

    public function placeOrder($data, $cartItems = null) {
        $orderData = $data;
        $items = $cartItems;
        $paymentData = null;

        if ($cartItems === null) {
            $orderData = $data['order'] ?? $data;
            $items = $data['items'] ?? $data['cart_items'] ?? [];
            $paymentData = $data['payment'] ?? null;
        }

        if (empty($items)) {
            return ['success' => false, 'message' => 'Giỏ hàng đang trống.'];
        }

        $orderData['order_code'] = $orderData['order_code'] ?? $this->generateOrderCode();
        $orderData['status'] = $orderData['status'] ?? 'pending';

        try {
            $this->db->beginTransaction();

            foreach ($items as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);

                if ($variantId <= 0 || $quantity <= 0) {
                    throw new \Exception('Dữ liệu sản phẩm trong đơn hàng không hợp lệ.');
                }

                $variant = $this->getVariantForUpdate($variantId);
                if (!$variant || (int)$variant['stock_quantity'] < $quantity) {
                    $name = $variant['product_name'] ?? ('Variant #' . $variantId);
                    $stock = (int)($variant['stock_quantity'] ?? 0);
                    throw new \Exception("$name không đủ tồn kho. Hiện còn $stock, cần $quantity.");
                }
            }

            $orderId = $this->createOrder($orderData);
            if (!$orderId) {
                throw new \Exception('Không thể tạo đơn hàng.');
            }

            foreach ($items as $item) {
                $variantId = (int)$item['variant_id'];
                $quantity = (int)$item['quantity'];
                $price = (float)($item['price_at_time'] ?? $item['price'] ?? 0);

                // Insert NULL first to avoid old local trigger subtracting stock at checkout.
                $orderItemId = $this->createOrderItem([
                    'order_id' => $orderId,
                    'variant_id' => null,
                    'quantity' => $quantity,
                    'price_at_time' => $price
                ]);
                parent::update('order_items', $orderItemId, ['variant_id' => $variantId]);
            }

            $stmt = $this->db->prepare("
                DELETE FROM inventory_logs
                WHERE variant_id IS NULL
                  AND quantity_changed < 0
                  AND reason = :reason
            ");
            $stmt->execute(['reason' => "Khách mua hàng, Order ID: $orderId"]);

            if (!empty($paymentData) && is_array($paymentData)) {
                $paymentData['order_id'] = $orderId;
                $this->createPayment($paymentData);
            }

            $this->db->commit();
            return ['success' => true, 'order_id' => $orderId, 'message' => 'Đặt hàng thành công.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function generateOrderCode() {
        do {
            $randomString = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
            $orderCode = 'ORD-' . date('Ymd') . '-' . $randomString;

            $stmt = $this->db->prepare("SELECT id FROM orders WHERE order_code = :order_code LIMIT 1");
            $stmt->execute(['order_code' => $orderCode]);
        } while ($stmt->fetch());

        return $orderCode;
    }

    public function generateUniqueOrderCode() {
        return $this->generateOrderCode();
    }

    public function getByUser($userId, $page = 1, $perPage = 10, $status = 'all') {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;
        $normalizedStatus = $this->normalizeStatus($status);

        $sql = "SELECT * FROM orders WHERE user_id = :user_id";
        $params = ['user_id' => (int)$userId];

        if ($normalizedStatus && $normalizedStatus !== 'all') {
            $sql .= " AND status = :status";
            $params['status'] = $normalizedStatus;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrdersByUserId($userId, $status = 'all') {
        $normalizedStatus = $this->normalizeStatus($status);

        $sql = "SELECT * FROM orders WHERE user_id = :user_id";
        $params = ['user_id' => (int)$userId];

        if ($normalizedStatus && $normalizedStatus !== 'all') {
            $sql .= " AND status = :status";
            $params['status'] = $normalizedStatus;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminOrders($filters = [], $page = 1, $perPage = 10) {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $query = "
            SELECT o.*, u.full_name AS user_name, u.email AS user_email
            FROM orders o
            LEFT JOIN user u ON o.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND o.status = :status";
            $params['status'] = $this->normalizeStatus($filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query .= " AND DATE(o.created_at) >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $query .= " AND DATE(o.created_at) <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        if (!empty($filters['order_code'])) {
            $query .= " AND o.order_code LIKE :order_code";
            $params['order_code'] = '%' . $filters['order_code'] . '%';
        }
        if (!empty($filters['keyword'])) {
            $query .= " AND (o.order_code LIKE :keyword OR o.shipping_name LIKE :keyword OR o.shipping_phone LIKE :keyword)";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        $query .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAdminOrders($filters = []) {
        $query = "SELECT COUNT(id) as total FROM orders WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND status = :status";
            $params['status'] = $this->normalizeStatus($filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query .= " AND DATE(created_at) >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $query .= " AND DATE(created_at) <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        if (!empty($filters['order_code'])) {
            $query .= " AND order_code LIKE :order_code";
            $params['order_code'] = '%' . $filters['order_code'] . '%';
        }
        if (!empty($filters['keyword'])) {
            $query .= " AND (order_code LIKE :keyword OR shipping_name LIKE :keyword OR shipping_phone LIKE :keyword)";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['total'] : 0;
    }

    public function updateStatus($id, $status, $note = '', $changedBy = null) {
        $orderId = (int)$id;
        $status = $this->normalizeStatus($status);

        if (!in_array($status, self::VALID_STATUSES, true)) {
            return ['success' => false, 'message' => 'Trạng thái đơn hàng không hợp lệ.'];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT id, status FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new \Exception('Không tìm thấy đơn hàng.');
            }

            if ($order['status'] !== $status) {
                if ($order['status'] === 'pending' && $status === 'confirmed') {
                    $this->deductStockForConfirmedOrder($orderId);
                }

                $stmt = $this->db->prepare("UPDATE orders SET status = :status WHERE id = :id");
                $stmt->execute([
                    'status' => $status,
                    'id' => $orderId
                ]);

                if ($order['status'] === 'pending' && $status === 'canceled') {
                    $this->neutralizePendingCancelRefund($orderId);
                }

                $this->writeStatusLog($orderId, $status, $note, $changedBy);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Cập nhật trạng thái đơn hàng thành công.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateOrderStatus($id, $status) {
        $result = $this->updateStatus($id, $status);
        return $result['success'];
    }

    public function cancelOrder($id, $userId = null) {
        $order = parent::getById('orders', (int)$id);

        if (!$order) {
            return ['success' => false, 'message' => 'Đơn hàng không tồn tại.'];
        }
        if ($userId && (int)$order['user_id'] !== (int)$userId) {
            return ['success' => false, 'message' => 'Bạn không có quyền hủy đơn hàng này.'];
        }
        if ($order['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Chỉ có thể hủy đơn hàng khi đang ở trạng thái pending.'];
        }

        return $this->updateStatus((int)$id, 'canceled', 'User canceled order', $userId);
    }

    // --- ORDER_ITEMS ---
    public function createOrderItem($data) { return $this->insert('order_items', $data); }

    public function incrementSoldCount($productId, $quantity) {
        $stmt = $this->db->prepare("UPDATE product SET sold_count = sold_count + :quantity WHERE id = :product_id");
        return $stmt->execute([
            'quantity' => (int)$quantity,
            'product_id' => (int)$productId
        ]);
    }

    public function getOrderItem($id) { return parent::getById('order_items', $id); }

    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare("
            SELECT
                oi.*,
                pv.size,
                pv.color,
                p.name AS product_name,
                p.slug AS product_slug,
                pi.image_url AS product_image
            FROM order_items oi
            LEFT JOIN product_variants pv ON oi.variant_id = pv.id
            LEFT JOIN product p ON pv.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE oi.order_id = :order_id
        ");
        $stmt->execute(['order_id' => (int)$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- PAYMENTS ---
    public function createPayment($data) { return $this->insert('payments', $data); }
    public function getPayment($orderId) { return $this->getPaymentByOrderId($orderId); }
    public function getPaymentById($id) { return parent::getById('payments', $id); }
    public function updatePaymentStatus($id, $status) { return $this->update('payments', $id, ['payment_status' => $status]); }

    public function getPaymentByOrderId($orderId) {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1");
        $stmt->execute(['order_id' => (int)$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- ORDER_STATUS_LOGS ---
    public function createOrderStatusLog($data) { return $this->insert('order_status_logs', $data); }
    public function getOrderStatusLog($id) { return parent::getById('order_status_logs', $id); }

    public function getStatusLogs($orderId) {
        return $this->getStatusLogsByOrder($orderId);
    }

    public function getStatusLogsByOrder($orderId) {
        $stmt = $this->db->prepare("SELECT * FROM order_status_logs WHERE order_id = :order_id ORDER BY created_at ASC, id ASC");
        $stmt->execute(['order_id' => (int)$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestOrders($limit = 5) {
        $stmt = $this->db->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function deductStockForConfirmedOrder(int $orderId): void {
        $stockReason = "Admin confirmed order ID: $orderId";
        $legacyReason = "Khách mua hàng, Order ID: $orderId";

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM inventory_logs
            WHERE variant_id IS NOT NULL
              AND quantity_changed < 0
              AND (reason = :stock_reason OR reason = :legacy_reason)
        ");
        $stmt->execute([
            'stock_reason' => $stockReason,
            'legacy_reason' => $legacyReason
        ]);

        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }

        $stmt = $this->db->prepare("
            SELECT
                oi.variant_id,
                oi.quantity,
                pv.stock_quantity,
                p.name AS product_name
            FROM order_items oi
            LEFT JOIN product_variants pv ON oi.variant_id = pv.id
            LEFT JOIN product p ON pv.product_id = p.id
            WHERE oi.order_id = :order_id
            FOR UPDATE
        ");
        $stmt->execute(['order_id' => $orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            throw new \Exception('Đơn hàng chưa có sản phẩm để trừ kho.');
        }

        foreach ($items as $item) {
            $variantId = (int)($item['variant_id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 0);
            $stockQuantity = (int)($item['stock_quantity'] ?? -1);
            $productName = $item['product_name'] ?? 'Sản phẩm';

            if ($variantId <= 0) {
                throw new \Exception('Đơn hàng có sản phẩm chưa liên kết variant nên chưa thể trừ kho.');
            }
            if ($stockQuantity < $quantity) {
                throw new \Exception("$productName không đủ tồn kho. Hiện còn $stockQuantity, cần $quantity.");
            }
        }

        $insertLog = $this->db->prepare("
            INSERT INTO inventory_logs (variant_id, quantity_changed, reason)
            VALUES (:variant_id, :quantity_changed, :reason)
        ");
        $updateStock = $this->db->prepare("
            UPDATE product_variants
            SET stock_quantity = stock_quantity - :quantity
            WHERE id = :variant_id
        ");
        $updateSold = $this->db->prepare("
            UPDATE product p
            JOIN product_variants pv ON p.id = pv.product_id
            SET p.sold_count = p.sold_count + :quantity
            WHERE pv.id = :variant_id
        ");
        $inventoryTriggerExists = $this->triggerExists('trg_after_insert_inventory_log');

        foreach ($items as $item) {
            $variantId = (int)$item['variant_id'];
            $quantity = (int)$item['quantity'];

            $insertLog->execute([
                'variant_id' => $variantId,
                'quantity_changed' => -$quantity,
                'reason' => $stockReason
            ]);

            if (!$inventoryTriggerExists) {
                $updateStock->execute([
                    'quantity' => $quantity,
                    'variant_id' => $variantId
                ]);
            }

            $updateSold->execute([
                'quantity' => $quantity,
                'variant_id' => $variantId
            ]);
        }
    }

    private function writeStatusLog(int $orderId, string $status, string $note = '', $changedBy = null): void {
        $hasTrigger = $this->triggerExists('trg_after_order_status_update');
        $hasChangedBy = $this->tableHasColumn('order_status_logs', 'changed_by');
        $hasNote = $this->tableHasColumn('order_status_logs', 'note');

        if ($hasTrigger) {
            $set = [];
            $params = ['order_id' => $orderId, 'status' => $status];

            if ($hasChangedBy) {
                $set[] = 'changed_by = :changed_by';
                $params['changed_by'] = $changedBy;
            }
            if ($hasNote) {
                $set[] = 'note = :note';
                $params['note'] = $note;
            }

            if (!empty($set)) {
                $sql = "
                    UPDATE order_status_logs
                    SET " . implode(', ', $set) . "
                    WHERE order_id = :order_id
                      AND status = :status
                    ORDER BY id DESC
                    LIMIT 1
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }

            return;
        }

        $columns = ['order_id', 'status'];
        $values = [':order_id', ':status'];
        $params = [
            'order_id' => $orderId,
            'status' => $status
        ];

        if ($hasChangedBy) {
            $columns[] = 'changed_by';
            $values[] = ':changed_by';
            $params['changed_by'] = $changedBy;
        }
        if ($hasNote) {
            $columns[] = 'note';
            $values[] = ':note';
            $params['note'] = $note;
        }

        $stmt = $this->db->prepare("
            INSERT INTO order_status_logs (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $values) . ")
        ");
        $stmt->execute($params);
    }

    private function neutralizePendingCancelRefund(int $orderId): void {
        $reason = "Hoàn trả kho do hủy đơn hàng ID: $orderId";
        $stmt = $this->db->prepare("
            SELECT variant_id, SUM(quantity_changed) AS refunded_quantity
            FROM inventory_logs
            WHERE reason = :reason
              AND variant_id IS NOT NULL
              AND quantity_changed > 0
            GROUP BY variant_id
        ");
        $stmt->execute(['reason' => $reason]);
        $refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($refunds)) {
            return;
        }

        $deleteLogs = $this->db->prepare("DELETE FROM inventory_logs WHERE reason = :reason");
        $deleteLogs->execute(['reason' => $reason]);

        if (!$this->triggerExists('trg_after_insert_inventory_log')) {
            return;
        }

        $updateStock = $this->db->prepare("
            UPDATE product_variants
            SET stock_quantity = stock_quantity - :quantity
            WHERE id = :variant_id
        ");

        foreach ($refunds as $refund) {
            $updateStock->execute([
                'quantity' => (int)$refund['refunded_quantity'],
                'variant_id' => (int)$refund['variant_id']
            ]);
        }
    }

    private function getVariantForUpdate(int $variantId) {
        $stmt = $this->db->prepare("
            SELECT pv.*, p.name AS product_name
            FROM product_variants pv
            LEFT JOIN product p ON pv.product_id = p.id
            WHERE pv.id = :variant_id
            FOR UPDATE
        ");
        $stmt->execute(['variant_id' => $variantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function normalizeStatus($status): ?string {
        $status = strtolower((string)$status);
        $map = [
            'cancelled' => 'canceled',
            'cancel' => 'canceled',
            'confirm' => 'confirmed',
            'completed' => 'delivered',
            'complete' => 'delivered',
            'all' => 'all'
        ];

        return $map[$status] ?? $status;
    }

    private function tableHasColumn(string $table, string $column): bool {
        $cacheKey = "$table.$column";
        if (array_key_exists($cacheKey, $this->columnCache)) {
            return $this->columnCache[$cacheKey];
        }

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

        $this->columnCache[$cacheKey] = (int)$stmt->fetchColumn() > 0;
        return $this->columnCache[$cacheKey];
    }

    private function triggerExists(string $triggerName): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
              AND TRIGGER_NAME = :trigger_name
        ");
        $stmt->execute(['trigger_name' => $triggerName]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
