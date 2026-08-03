<?php

namespace App\Models;

use PDO;
use Throwable;
use App\Services\OrderNotificationService;

class Order extends BaseModel {
    private const VALID_STATUSES = ['pending', 'confirmed', 'preparing', 'shipping', 'delivered', 'completed', 'canceled'];

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

        if (!filter_var($orderData['terms_accepted'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return ['success' => false, 'message' => 'Bạn cần đồng ý với điều khoản mua hàng trước khi đặt hàng.'];
        }

        $orderData['order_code'] = $orderData['order_code'] ?? $this->generateOrderCode();

        try {
            $this->db->beginTransaction();

            $normalizedItems = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $variantId = (int)($item['variant_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);

                if ($variantId <= 0 || $quantity <= 0) {
                    throw new \Exception('Dữ liệu sản phẩm trong đơn hàng không hợp lệ.');
                }

                $variant = $this->getVariantForUpdate($variantId);
                if (!$variant || (int)($variant['product_status'] ?? 0) !== 1 || (int)$variant['stock_quantity'] < $quantity) {
                    $name = $variant['product_name'] ?? ('Variant #' . $variantId);
                    $stock = (int)($variant['stock_quantity'] ?? 0);
                    throw new \Exception("$name không đủ tồn kho. Hiện còn $stock, cần $quantity.");
                }

                $unitPrice = (float)$variant['base_price'] + (float)($variant['price_modifier'] ?? 0);
                $subtotal += $unitPrice * $quantity;
                $normalizedItems[] = [
                    'variant_id' => $variantId,
                    'product_id' => (int)$variant['product_id'],
                    'category_id' => $variant['category_id'] !== null ? (int)$variant['category_id'] : null,
                    'quantity' => $quantity,
                    'price_at_time' => $unitPrice,
                    'product_name_snapshot' => $variant['product_name'],
                    'variant_size_snapshot' => $variant['size'],
                    'variant_color_snapshot' => $variant['color']
                ];
            }

            $shippingFee = $subtotal >= 1000000 ? 0 : 30000;
            $discount = 0.0;
            $couponId = !empty($orderData['coupon_id']) ? (int)$orderData['coupon_id'] : null;
            $paymentMethod = trim((string)($orderData['payment_method'] ?? 'cod')) ?: 'cod';
            if ($paymentMethod !== 'cod') {
                throw new \Exception('Hiện tại website chỉ hỗ trợ thanh toán khi nhận hàng (COD).');
            }
            if ($couponId) {
                $couponModel = new Coupons();
                $couponResult = $couponModel->validateCouponById($couponId, $subtotal, $orderData['user_id'] ?? null, $normalizedItems, true);
                if (!$couponResult['is_valid']) {
                    throw new \Exception($couponResult['message']);
                }
                $discount = (float)$couponResult['discount'];
            }

            $orderData = [
                'order_code' => $orderData['order_code'],
                'user_id' => $orderData['user_id'] ?? null,
                'total_amount' => $subtotal,
                'coupon_id' => $couponId,
                'final_amount' => max(0, $subtotal + $shippingFee - $discount),
                'shipping_fee' => $shippingFee,
                'shipping_name' => trim((string)($orderData['shipping_name'] ?? '')),
                'shipping_phone' => trim((string)($orderData['shipping_phone'] ?? '')),
                'shipping_address' => trim((string)($orderData['shipping_address'] ?? '')),
                'shipping_email' => trim((string)($orderData['shipping_email'] ?? '')) ?: null,
                'customer_note' => trim((string)($orderData['customer_note'] ?? '')) ?: null,
                'terms_accepted' => 1,
                'terms_accepted_at' => date('Y-m-d H:i:s'),
                'contract_version' => preg_match('/^[a-zA-Z0-9._-]{1,30}$/', (string)($orderData['contract_version'] ?? 'v1.0')) ? (string)($orderData['contract_version'] ?? 'v1.0') : 'v1.0',
                'terms_accepted_ip' => substr((string)($orderData['terms_accepted_ip'] ?? ''), 0, 45) ?: null,
                'terms_accepted_user_agent' => substr((string)($orderData['terms_accepted_user_agent'] ?? ''), 0, 1000) ?: null,
                'shipping_status' => 'not_shipped',
                'status' => 'pending'
            ];

            if ($orderData['shipping_name'] === '' || $orderData['shipping_phone'] === '' || $orderData['shipping_address'] === '') {
                throw new \Exception('Thông tin giao hàng chưa đầy đủ.');
            }

            $orderId = $this->createOrder($orderData);
            if (!$orderId) {
                throw new \Exception('Không thể tạo đơn hàng.');
            }

            foreach ($normalizedItems as $item) {
                $this->createOrderItem([
                    'order_id' => $orderId,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'price_at_time' => $item['price_at_time'],
                    'product_name_snapshot' => $item['product_name_snapshot'],
                    'variant_size_snapshot' => $item['variant_size_snapshot'],
                    'variant_color_snapshot' => $item['variant_color_snapshot']
                ]);
            }

            $this->writeStatusLog($orderId, 'pending', 'Đơn hàng được tạo, chờ shop xác nhận.', $orderData['user_id'] ?? null);

            if ($couponId) {
                $couponModel = new Coupons();
                if (!$couponModel->reserveUsage($couponId, (int)$orderData['user_id'], $orderId)) {
                    throw new \Exception('Mã giảm giá vừa hết lượt sử dụng. Vui lòng thử lại.');
                }
            }

            if (!empty($paymentData) && is_array($paymentData)) {
                $paymentData['order_id'] = $orderId;
                $this->createPayment($paymentData);
            } elseif ($paymentMethod !== '') {
                $this->createPayment([
                    'order_id' => $orderId,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 0,
                    'payment_state' => 'pending',
                    'refund_status' => 'not_requested'
                ]);
            }

            $this->db->commit();
            try {
                (new OrderNotificationService())->queueOrderCreated((int)$orderId);
            } catch (Throwable $notificationError) {
                // Email is an asynchronous channel; an SMTP/queue issue must
                // never roll back an order that was already created.
            }
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

    /**
     * Public order lookup is intentionally limited to the order code and the
     * exact phone number used at checkout. Never expose an order by code alone.
     */
    public function findPublicTracking(string $orderCode, string $phone): ?array {
        $orderCode = strtoupper(ltrim(trim($orderCode), '#'));
        $phoneDigits = preg_replace('/\D+/', '', $phone);
        if ($orderCode === '' || $phoneDigits === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id, order_code, status, shipping_name,
                shipping_carrier, tracking_code, shipping_status, shipping_fee,
                created_at, shipped_at, delivered_at, completed_at
            FROM orders
            WHERE UPPER(order_code) = :order_code
              AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(shipping_phone, ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '+', '') = :phone
            LIMIT 1");
        $stmt->execute([
            'order_code' => $orderCode,
            'phone' => $phoneDigits
        ]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        return $order ?: null;
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

        $statusChanged = false;
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT id, status, shipping_carrier, tracking_code, shipping_status
                FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new \Exception('Không tìm thấy đơn hàng.');
            }

            if ($status === 'shipping'
                && trim((string)$order['shipping_carrier']) === ''
                && trim((string)$order['tracking_code']) === '') {
                throw new \Exception('Vui lòng cập nhật đơn vị vận chuyển hoặc mã vận đơn trước khi chuyển sang Đang giao.');
            }

            if ($order['status'] !== $status) {
                if (!$this->canTransition($order['status'], $status)) {
                    throw new \Exception('Không thể chuyển đơn từ trạng thái ' . $this->statusLabel($order['status']) . ' sang ' . $this->statusLabel($status) . '.');
                }

                if ($status === 'delivered' && $order['status'] !== 'shipping') {
                    throw new \Exception('Đơn hàng phải ở trạng thái Đang giao trước khi xác nhận giao thành công.');
                }

                if ($order['status'] === 'pending' && $status === 'confirmed') {
                    $this->deductStockForConfirmedOrder($orderId);
                }

                if ($status === 'canceled' && in_array($order['status'], ['confirmed', 'preparing'], true)) {
                    $this->releaseStockForCanceledOrder($orderId);
                }

                $set = ['status = :status', 'shipping_status = :shipping_status'];
                $params = [
                    'status' => $status,
                    'shipping_status' => $this->shippingStatusForOrder($status),
                    'id' => $orderId
                ];
                if ($status === 'shipping') {
                    $set[] = 'shipped_at = COALESCE(shipped_at, NOW())';
                } elseif ($status === 'delivered') {
                    $set[] = 'delivered_at = COALESCE(delivered_at, NOW())';
                } elseif ($status === 'completed') {
                    $set[] = 'completed_at = COALESCE(completed_at, NOW())';
                }

                $stmt = $this->db->prepare("UPDATE orders SET " . implode(', ', $set) . " WHERE id = :id");
                $stmt->execute([
                    'status' => $params['status'],
                    'shipping_status' => $params['shipping_status'],
                    'id' => $params['id']
                ]);

                if ($status === 'delivered') {
                    $this->recognizeDeliveredSales($orderId);
                    $this->markPaymentPaid($orderId);
                } elseif ($status === 'canceled') {
                    $this->markPaymentCanceled($orderId);
                }

                $this->writeStatusLog($orderId, $status, $note, $changedBy);
                $statusChanged = true;
            } elseif ($order['shipping_status'] !== $this->shippingStatusForOrder($status)) {
                $stmt = $this->db->prepare('UPDATE orders SET shipping_status = :shipping_status WHERE id = :id');
                $stmt->execute([
                    'shipping_status' => $this->shippingStatusForOrder($status),
                    'id' => $orderId
                ]);
            }

            $this->db->commit();
            if ($statusChanged) {
                try {
                    (new OrderNotificationService())->queueStatusChanged($orderId, $status);
                } catch (Throwable $notificationError) {
                    // Keep the order transition successful; the notification
                    // queue can be retried independently.
                }
            }
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

    public function updateShipping($id, $carrier, $trackingCode, $shippingStatus, $shippingFee = null) {
        $orderId = (int)$id;
        $order = parent::getById('orders', $orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Không tìm thấy đơn hàng.'];
        }

        $expectedStatus = $this->shippingStatusForOrder($order['status']);
        $submittedStatus = strtolower(trim((string)$shippingStatus));
        if ($submittedStatus !== '' && $submittedStatus !== $expectedStatus) {
            return ['success' => false, 'message' => 'Trạng thái giao hàng được đồng bộ theo luồng xử lý đơn. Hãy đổi trạng thái ở phần Luồng xử lý đơn.'];
        }

        $carrier = trim((string)$carrier);
        $trackingCode = trim((string)$trackingCode);
        if ($order['status'] === 'shipping' && $carrier === '' && $trackingCode === '') {
            return ['success' => false, 'message' => 'Đơn đang giao phải có đơn vị vận chuyển hoặc mã vận đơn.'];
        }

        $data = [
            'shipping_carrier' => $carrier ?: null,
            'tracking_code' => $trackingCode ?: null,
            'shipping_status' => $expectedStatus
        ];
        if ($shippingFee !== null) {
            $data['shipping_fee'] = max(0, (float)$shippingFee);
        }
        if ($expectedStatus === 'in_transit') {
            $data['shipped_at'] = $order['shipped_at'] ?: date('Y-m-d H:i:s');
        }
        if ($expectedStatus === 'delivered') {
            $data['delivered_at'] = $order['delivered_at'] ?: date('Y-m-d H:i:s');
        }

        $this->update('orders', $orderId, $data);
        return ['success' => true, 'message' => 'Đã cập nhật thông tin giao hàng.'];
    }

    // --- ORDER_ITEMS ---
    public function createOrderItem($data) { return $this->insert('order_items', $data); }

    public function incrementSoldCount($productId, $quantity) {
        throw new \LogicException('Không được tăng sold_count trực tiếp. Hãy chuyển đơn sang delivered để hệ thống ghi nhận bán thực tế.');
    }

    public function getOrderItem($id) { return parent::getById('order_items', $id); }

    public function getOrderItems($orderId) {
        $stmt = $this->db->prepare("
            SELECT
                oi.*,
                COALESCE(pv.size, oi.variant_size_snapshot) AS size,
                COALESCE(pv.color, oi.variant_color_snapshot) AS color,
                COALESCE(p.name, oi.product_name_snapshot) AS product_name,
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
    public function updatePaymentStatus($id, $status) {
        $status = (int)$status;
        $state = $status === 1 ? 'paid' : ($status === 2 ? 'refunded' : 'pending');
        return $this->update('payments', $id, ['payment_status' => $status, 'payment_state' => $state]);
    }

    public function getPaymentByOrderId($orderId) {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1");
        $stmt->execute(['order_id' => (int)$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function markPaymentPaid(int $orderId): void {
        $stmt = $this->db->prepare("UPDATE payments
            SET payment_status = 1, payment_state = 'paid', paid_at = COALESCE(paid_at, NOW())
            WHERE order_id = :order_id AND payment_state NOT IN ('refunded', 'refund_pending')");
        $stmt->execute(['order_id' => $orderId]);
    }

    private function markPaymentCanceled(int $orderId): void {
        $stmt = $this->db->prepare("UPDATE payments
            SET payment_state = 'canceled'
            WHERE order_id = :order_id AND payment_state = 'pending'");
        $stmt->execute(['order_id' => $orderId]);
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
        $updateReserved = $this->db->prepare("
            UPDATE product p
            JOIN product_variants pv ON p.id = pv.product_id
            SET p.reserved_quantity = p.reserved_quantity + :quantity
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

            $updateReserved->execute([
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

    private function releaseStockForCanceledOrder(int $orderId): void {
        $reason = "Hoàn kho do hủy đơn hàng ID: $orderId";
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM inventory_logs WHERE reason = :reason AND quantity_changed > 0");
        $stmt->execute(['reason' => $reason]);
        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }

        $stmt = $this->db->prepare("SELECT variant_id, quantity FROM order_items WHERE order_id = :order_id AND variant_id IS NOT NULL");
        $stmt->execute(['order_id' => $orderId]);
        $insert = $this->db->prepare("INSERT INTO inventory_logs (variant_id, quantity_changed, reason) VALUES (:variant_id, :quantity_changed, :reason)");
        $decreaseReserved = $this->db->prepare("UPDATE product p JOIN product_variants pv ON pv.product_id = p.id
            SET p.reserved_quantity = GREATEST(0, p.reserved_quantity - :quantity) WHERE pv.id = :variant_id");
        $updateStock = $this->db->prepare('UPDATE product_variants SET stock_quantity = stock_quantity + :quantity WHERE id = :variant_id');
        $inventoryTriggerExists = $this->triggerExists('trg_after_insert_inventory_log');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $insert->execute([
                'variant_id' => (int)$item['variant_id'],
                'quantity_changed' => (int)$item['quantity'],
                'reason' => $reason
            ]);
            if (!$inventoryTriggerExists) {
                $updateStock->execute(['quantity' => (int)$item['quantity'], 'variant_id' => (int)$item['variant_id']]);
            }
            $decreaseReserved->execute([
                'quantity' => (int)$item['quantity'],
                'variant_id' => (int)$item['variant_id']
            ]);
        }
    }

    private function recognizeDeliveredSales(int $orderId): void {
        $stmt = $this->db->prepare('INSERT IGNORE INTO order_sales_recognition (order_id) VALUES (:order_id)');
        $stmt->execute(['order_id' => $orderId]);
        if ($stmt->rowCount() !== 1) {
            return;
        }

        $stmt = $this->db->prepare('SELECT variant_id, quantity FROM order_items WHERE order_id = :order_id AND variant_id IS NOT NULL');
        $stmt->execute(['order_id' => $orderId]);
        $incrementSold = $this->db->prepare("UPDATE product p JOIN product_variants pv ON pv.product_id = p.id
            SET p.sold_count = p.sold_count + :quantity,
                p.reserved_quantity = GREATEST(0, p.reserved_quantity - :quantity)
            WHERE pv.id = :variant_id");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $incrementSold->execute([
                'quantity' => (int)$item['quantity'],
                'variant_id' => (int)$item['variant_id']
            ]);
        }
    }

    private function canTransition(string $from, string $to): bool {
        $transitions = [
            'pending' => ['confirmed', 'canceled'],
            'confirmed' => ['preparing', 'canceled'],
            'preparing' => ['shipping', 'canceled'],
            'shipping' => ['delivered'],
            'delivered' => ['completed'],
            'completed' => [],
            'canceled' => []
        ];

        return in_array($to, $transitions[$from] ?? [], true);
    }

    private function statusLabel(string $status): string {
        return [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'preparing' => 'Đang chuẩn bị',
            'shipping' => 'Đang giao',
            'delivered' => 'Giao thành công',
            'completed' => 'Hoàn thành',
            'canceled' => 'Đã hủy'
        ][$status] ?? $status;
    }

    private function getVariantForUpdate(int $variantId) {
        $stmt = $this->db->prepare("
            SELECT pv.*, p.name AS product_name, p.base_price, p.category_id, p.status AS product_status
            FROM product_variants pv
            LEFT JOIN product p ON pv.product_id = p.id
            WHERE pv.id = :variant_id
            FOR UPDATE
        ");
        $stmt->execute(['variant_id' => $variantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function shippingStatusForOrder(string $status): string {
        return [
            'pending' => 'not_shipped',
            'confirmed' => 'not_shipped',
            'preparing' => 'packing',
            'shipping' => 'in_transit',
            'delivered' => 'delivered',
            'completed' => 'delivered',
            'canceled' => 'canceled'
        ][$status] ?? 'not_shipped';
    }

    private function normalizeStatus($status): ?string {
        $status = strtolower((string)$status);
        $map = [
            'cancelled' => 'canceled',
            'cancel' => 'canceled',
            'confirm' => 'confirmed',
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
