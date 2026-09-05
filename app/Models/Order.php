<?php

namespace App\Models;

use PDO;
use Throwable;
use App\Services\OrderNotificationService;
use App\Services\PayPalService;
use App\Services\ShippingService;

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
        $order['payment_refunds'] = $this->getPaymentRefunds((int)$order['id']);
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
                $availableStock = max(0, (int)($variant['stock_quantity'] ?? 0) - (int)($variant['reserved_quantity'] ?? 0));
                if (!$variant || (int)($variant['product_status'] ?? 0) !== 1 || $availableStock < $quantity) {
                    $name = $variant['product_name'] ?? ('Variant #' . $variantId);
                    $stock = $availableStock;
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
                    'unit_cost_snapshot' => (float)($variant['cost_price'] ?? 0),
                    'product_name_snapshot' => $variant['product_name'],
                    'variant_size_snapshot' => $variant['size'],
                    'variant_color_snapshot' => $variant['color'],
                    'unit_name_snapshot' => trim((string)($variant['unit_name'] ?? 'Cái')) ?: 'Cái'
                    ,'weight_grams' => (int)($variant['weight_grams'] ?? 500)
                    ,'length_cm' => (float)($variant['length_cm'] ?? 25)
                    ,'width_cm' => (float)($variant['width_cm'] ?? 20)
                    ,'height_cm' => (float)($variant['height_cm'] ?? 5)
                ];
            }

            $shippingProvince = trim((string)($orderData['shipping_province'] ?? ''));
            $shippingCarrierCode = trim((string)($orderData['shipping_carrier_code'] ?? 'standard')) ?: 'standard';
            if ($shippingProvince === '') {
                throw new \Exception('Vui lòng chọn tỉnh/thành phố giao hàng.');
            }
            $shippingQuote = (new ShippingService())->selectedQuote($normalizedItems, $shippingProvince, $subtotal, $shippingCarrierCode);
            $shippingFee = (float)$shippingQuote['fee'];
            $discount = 0.0;
            $couponData = null;
            $couponId = !empty($orderData['coupon_id']) ? (int)$orderData['coupon_id'] : null;
            $paymentMethod = trim((string)($orderData['payment_method'] ?? 'cod')) ?: 'cod';
            if (!in_array($paymentMethod, ['cod', 'bank', 'paypal'], true)) {
                throw new \Exception('Phương thức thanh toán không được hỗ trợ.');
            }
            if ($couponId) {
                $couponModel = new Coupons();
                $couponResult = $couponModel->validateCouponById($couponId, $subtotal, $orderData['user_id'] ?? null, $normalizedItems, true);
                if (!$couponResult['is_valid']) {
                    throw new \Exception($couponResult['message']);
                }
                $discount = (float)$couponResult['discount'];
                $couponData = $couponResult['data'];
            }

            $normalizedItems = $this->allocateItemDiscounts($normalizedItems, $couponData, $discount);
            $couponCodeSnapshot = null;
            if ($couponId) {
                $couponModel = new Coupons();
                $coupon = $couponModel->getById('coupons', $couponId);
                if ($coupon) {
                    $couponCodeSnapshot = $coupon['code'];
                }
            }

            $shippingName = trim((string)($orderData['shipping_name'] ?? ''));
            $shippingPhone = trim((string)($orderData['shipping_phone'] ?? ''));
            $shippingAddress = trim((string)($orderData['shipping_address'] ?? ''));

            if ($shippingName === '' || $shippingPhone === '' || $shippingAddress === '') {
                throw new \Exception('Thông tin giao hàng chưa đầy đủ.');
            }

            $finalAmount = max(0, $subtotal + $shippingFee - $discount);
            $insertOrderData = [
                'order_code' => $orderData['order_code'],
                'user_id' => $orderData['user_id'] ?? null,
                'total_amount' => $subtotal,
                'coupon_id' => $couponId,
                'final_amount' => $finalAmount,
                'shipping_fee' => $shippingFee,
                'shipping_name' => $shippingName,
                'shipping_phone' => $shippingPhone,
                'shipping_address' => $shippingAddress,
                'shipping_province' => $shippingProvince,
                'shipping_carrier_code' => $shippingCarrierCode,
                'shipping_weight_grams' => (int)$shippingQuote['chargeable_weight_grams'],
                'shipping_carrier' => $shippingQuote['carrier_name'],
                'shipping_status' => 'not_shipped',
                'status' => 'pending',
                'reservation_status' => 'reserved',
                'reservation_expires_at' => date('Y-m-d H:i:s', time() + ($paymentMethod === 'paypal' ? 1800 : 86400)),
                'stock_reserved_at' => date('Y-m-d H:i:s'),
                'shipping_email' => trim((string)($orderData['shipping_email'] ?? '')) ?: null,
                'customer_note' => trim((string)($orderData['customer_note'] ?? '')) ?: null,
                'terms_accepted' => 1,
                'terms_accepted_at' => date('Y-m-d H:i:s'),
                'contract_version' => preg_match('/^[a-zA-Z0-9._-]{1,30}$/', (string)($orderData['contract_version'] ?? 'v2.0-2026-08-27')) ? (string)($orderData['contract_version'] ?? 'v2.0-2026-08-27') : 'v2.0-2026-08-27',
                'terms_accepted_ip' => substr((string)($orderData['terms_accepted_ip'] ?? ''), 0, 45) ?: null,
                'terms_accepted_user_agent' => substr((string)($orderData['terms_accepted_user_agent'] ?? ''), 0, 1000) ?: null
            ];

            $orderId = $this->createOrder($insertOrderData);
            if (!$orderId) {
                throw new \Exception('Không thể tạo đơn hàng.');
            }

            foreach ($normalizedItems as $item) {
                $unitPrice = (float)$item['price_at_time'];
                $qty = (int)$item['quantity'];

                $this->createOrderItem([
                    'order_id' => $orderId,
                    'product_id' => (int)$item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $qty,
                    'price_at_time' => $unitPrice,
                    'unit_cost_snapshot' => (float)($item['unit_cost_snapshot'] ?? 0),
                    'discount_amount' => (float)($item['discount_amount'] ?? 0),
                    'product_name_snapshot' => $item['product_name_snapshot'],
                    'variant_size_snapshot' => $item['variant_size_snapshot'],
                    'variant_color_snapshot' => $item['variant_color_snapshot'],
                    'unit_name_snapshot' => $item['unit_name_snapshot']
                ]);
            }

            $this->reserveStockForPendingOrder((int)$orderId, $normalizedItems);

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
                $dbMethod = $paymentMethod === 'bank' ? 'bank_transfer' : $paymentMethod;
                $this->createPayment([
                    'order_id' => $orderId,
                    'payment_method' => $dbMethod,
                    'payment_state' => 'pending',
                    'refund_status' => 'not_requested'
                ]);
            }

            $this->db->commit();
            try {
                $notificationService = new OrderNotificationService();
                if ($notificationService->queueOrderCreated((int)$orderId)) {
                    // PayPal chỉ gửi thư tiếp nhận sau khi cổng thanh toán xác
                    // nhận capture thành công. COD và chuyển khoản gửi ngay.
                    if ($paymentMethod !== 'paypal') {
                        $notificationService->processForOrder((int)$orderId, 'order_created');
                    }
                }
            } catch (Throwable $notificationError) {
                // Đơn hàng đã tạo vẫn phải thành công nếu SMTP tạm thời lỗi.
                // Email còn nguyên trong hàng đợi để quản trị viên gửi lại.
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

    public function findPublicTracking(string $orderCode, string $phone) {
        $orderCode = strtoupper(trim($orderCode));
        $phoneDigits = preg_replace('/\D+/', '', $phone);
        if ($orderCode === '' || $phoneDigits === '') {
            return false;
        }
        $alternatePhone = strpos($phoneDigits, '84') === 0
            ? '0' . substr($phoneDigits, 2)
            : (strpos($phoneDigits, '0') === 0 ? '84' . substr($phoneDigits, 1) : $phoneDigits);

        $stmt = $this->db->prepare("
            SELECT o.order_code, o.status, o.shipping_name,
                   o.shipping_carrier, o.tracking_code, o.shipping_status,
                   o.shipping_fee, o.created_at, o.shipped_at, o.delivered_at, o.completed_at
            FROM orders o
            WHERE UPPER(o.order_code) = :order_code
              AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(o.shipping_phone, ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '+', '') IN (:phone, :alternate_phone)
            LIMIT 1
        ");
        $stmt->execute(['order_code' => $orderCode, 'phone' => $phoneDigits, 'alternate_phone' => $alternatePhone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function updateStatus($id, $status, $note = '', $changedBy = null, bool $sendNotificationNow = true) {
        $orderId = (int)$id;
        $status = $this->normalizeStatus($status);

        if (!in_array($status, self::VALID_STATUSES, true)) {
            return ['success' => false, 'message' => 'Trạng thái đơn hàng không hợp lệ.'];
        }

        $statusChanged = false;
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT id, status, coupon_id, shipping_carrier, tracking_code, shipping_status, reservation_status
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
                    $payment = $this->getPaymentByOrderId($orderId);
                    if ($payment
                        && in_array($payment['payment_method'], ['bank_transfer', 'paypal'], true)
                        && $payment['payment_state'] !== 'paid') {
                        throw new \Exception('Đơn trả trước chỉ được xác nhận sau khi hệ thống ghi nhận thanh toán thành công.');
                    }
                    $this->deductStockForConfirmedOrder($orderId);
                }

                if ($status === 'canceled' && $order['status'] === 'shipping' && trim($note) === '') {
                    throw new \Exception('Khi giao thất bại hoặc hàng hoàn về, vui lòng ghi rõ lý do để lưu vết xử lý.');
                }
                if ($status === 'canceled' && in_array($order['status'], ['confirmed', 'preparing', 'shipping'], true)) {
                    $this->releaseStockForCanceledOrder($orderId);
                }
                if ($status === 'canceled' && $order['status'] === 'pending') {
                    $this->releasePendingReservation($orderId);
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
                } elseif ($status === 'canceled') {
                    $set[] = "reservation_status = 'released'";
                    $set[] = 'reservation_expires_at = NULL';
                    $set[] = 'stock_reservation_closed_at = COALESCE(stock_reservation_closed_at, NOW())';
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
                    if (!empty($order['coupon_id'])) {
                        (new Coupons())->releaseUsageForOrder($orderId);
                    }
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
                    $notificationService = new OrderNotificationService();
                    $notificationType = 'status_' . $status;
                    if ($notificationService->queueStatusChanged($orderId, $status) && $sendNotificationNow) {
                        $notificationService->processForOrder($orderId, $notificationType);
                    }
                } catch (Throwable $notificationError) {
                    // Cập nhật trạng thái vẫn thành công; email được giữ lại
                    // trong hàng đợi để có thể gửi lại từ trang quản trị.
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
        $payment = $this->getPaymentByOrderId((int)$id);
        if ($payment && in_array($payment['payment_state'], ['paid', 'refund_pending', 'partially_refunded', 'refunded'], true)) {
            return ['success' => false, 'message' => 'Đơn đã ghi nhận thanh toán nên không thể tự hủy. Vui lòng gửi yêu cầu hỗ trợ để shop đối soát và hoàn tiền.'];
        }

        return $this->updateStatus((int)$id, 'canceled', 'User canceled order', $userId);
    }

    public function updateShipping($id, $carrier, $trackingCode, $shippingStatus, $shippingFee = null) {
        $orderId = (int)$id;
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order) {
                throw new \RuntimeException('Không tìm thấy đơn hàng.');
            }

            $expectedStatus = $this->shippingStatusForOrder($order['status']);
            $submittedStatus = strtolower(trim((string)$shippingStatus));
            if ($submittedStatus !== '' && $submittedStatus !== $expectedStatus) {
                throw new \RuntimeException('Trạng thái giao hàng được đồng bộ theo luồng xử lý đơn. Hãy đổi trạng thái ở phần Luồng xử lý đơn.');
            }

            $carrier = trim((string)$carrier);
            $trackingCode = trim((string)$trackingCode);
            if ($order['status'] === 'shipping' && $carrier === '' && $trackingCode === '') {
                throw new \RuntimeException('Đơn đang giao phải có đơn vị vận chuyển hoặc mã vận đơn.');
            }

            $newShippingFee = $shippingFee === null ? (float)$order['shipping_fee'] : max(0, (float)$shippingFee);
            if ($newShippingFee !== (float)$order['shipping_fee'] && !in_array($order['status'], ['pending', 'confirmed', 'preparing'], true)) {
                throw new \RuntimeException('Không thể đổi phí ship sau khi đơn đã bắt đầu giao.');
            }
            if ($newShippingFee !== (float)$order['shipping_fee']) {
                $payment = $this->getPaymentByOrderId($orderId);
                if ($payment && in_array($payment['payment_state'], ['paid', 'refund_pending', 'partially_refunded', 'refunded'], true)) {
                    throw new \RuntimeException('Không thể đổi phí ship sau khi khoản thanh toán đã được xác nhận.');
                }
                if ($payment && $payment['payment_method'] === 'paypal' && !empty($payment['provider_order_id'])) {
                    throw new \RuntimeException('Không thể đổi phí ship sau khi đã tạo yêu cầu thanh toán PayPal. Hãy hủy đơn và tạo lại.');
                }
            }
            $newFinalAmount = max(0, (float)$order['final_amount'] - (float)$order['shipping_fee'] + $newShippingFee);
            $stmt = $this->db->prepare('UPDATE orders SET shipping_carrier = :carrier, tracking_code = :tracking_code,
                shipping_status = :shipping_status, shipping_fee = :shipping_fee, final_amount = :final_amount,
                shipped_at = CASE WHEN :is_transit = 1 THEN COALESCE(shipped_at, NOW()) ELSE shipped_at END,
                delivered_at = CASE WHEN :is_delivered = 1 THEN COALESCE(delivered_at, NOW()) ELSE delivered_at END
                WHERE id = :id');
            $stmt->execute([
                'carrier' => $carrier ?: null,
                'tracking_code' => $trackingCode ?: null,
                'shipping_status' => $expectedStatus,
                'shipping_fee' => $newShippingFee,
                'final_amount' => $newFinalAmount,
                'is_transit' => $expectedStatus === 'in_transit' ? 1 : 0,
                'is_delivered' => $expectedStatus === 'delivered' ? 1 : 0,
                'id' => $orderId
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã cập nhật thông tin giao hàng và tổng thanh toán.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
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
    public function getPaymentRefunds(int $orderId): array {
        $stmt=$this->db->prepare('SELECT * FROM payment_refunds WHERE order_id=:order_id ORDER BY created_at,id');
        $stmt->execute(['order_id'=>$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function updatePaymentStatus($id, $status) {
        $status = (int)$status;
        $state = $status === 1 ? 'paid' : ($status === 2 ? 'refunded' : 'pending');
        return $this->update('payments', $id, ['payment_state' => $state]);
    }

    public function getPaymentByOrderId($orderId) {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1");
        $stmt->execute(['order_id' => (int)$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function attachPayPalOrder(
        int $orderId,
        string $providerOrderId,
        string $currency,
        float $providerAmount,
        float $exchangeRate
    ): array {
        $providerOrderId = trim($providerOrderId);
        $currency = strtoupper(trim($currency));
        if (!preg_match('/^[A-Z0-9]{8,30}$/i', $providerOrderId)
            || !preg_match('/^[A-Z]{3}$/', $currency)
            || $providerAmount <= 0
            || $exchangeRate <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu PayPal không hợp lệ.'];
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('SELECT * FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $stmt->execute(['order_id' => $orderId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$payment || $payment['payment_method'] !== 'paypal') {
                throw new \RuntimeException('Đơn hàng không sử dụng PayPal.');
            }
            if ($payment['payment_state'] !== 'pending') {
                throw new \RuntimeException('Trạng thái thanh toán không cho phép tạo PayPal Order.');
            }
            if (!empty($payment['provider_order_id']) && $payment['provider_order_id'] !== $providerOrderId) {
                throw new \RuntimeException('Đơn hàng đã gắn với một PayPal Order khác.');
            }

            $stmt = $this->db->prepare('UPDATE payments SET provider_order_id = :provider_order_id,
                provider_currency = :provider_currency, provider_amount = :provider_amount,
                provider_exchange_rate = :provider_exchange_rate WHERE id = :id');
            $stmt->execute([
                'provider_order_id' => $providerOrderId,
                'provider_currency' => $currency,
                'provider_amount' => round($providerAmount, 2),
                'provider_exchange_rate' => round($exchangeRate, 4),
                'id' => (int)$payment['id'],
            ]);
            $this->db->commit();
            return ['success' => true, 'message' => 'Đã tạo yêu cầu thanh toán PayPal.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function completePayPalPayment(
        int $orderId,
        string $providerOrderId,
        string $captureId,
        string $currency,
        float $providerAmount
    ): array {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('SELECT o.status, p.* FROM orders o JOIN payments p ON p.order_id = o.id
                WHERE o.id = :order_id ORDER BY p.id DESC LIMIT 1 FOR UPDATE');
            $stmt->execute(['order_id' => $orderId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$payment || $payment['payment_method'] !== 'paypal') {
                throw new \RuntimeException('Không tìm thấy thanh toán PayPal của đơn hàng.');
            }
            if ($payment['provider_order_id'] !== $providerOrderId) {
                throw new \RuntimeException('Mã PayPal Order không khớp với đơn hàng.');
            }
            if (strtoupper($currency) !== strtoupper((string)$payment['provider_currency'])
                || abs($providerAmount - (float)$payment['provider_amount']) > 0.001) {
                throw new \RuntimeException('Số tiền PayPal không khớp với số tiền đã tạo trên hệ thống.');
            }
            if ($payment['payment_state'] === 'paid') {
                if ($payment['provider_capture_id'] !== $captureId) {
                    throw new \RuntimeException('Khoản PayPal đã được ghi nhận bằng mã capture khác.');
                }
                $this->db->commit();
                return ['success' => true, 'message' => 'Khoản PayPal đã được ghi nhận trước đó.'];
            }
            if ($payment['payment_state'] === 'refunded') {
                if ($payment['provider_capture_id'] !== $captureId) {
                    throw new \RuntimeException('Đơn đã hoàn tiền bằng một mã capture khác.');
                }
                $this->db->commit();
                return ['success' => true, 'message' => 'Khoản PayPal đến trễ đã được hoàn lại trước đó.'];
            }

            // Capture có thể hoàn tất đúng lúc đơn hết hạn. Không phục hồi đơn/kho
            // một cách âm thầm: ghi nhận tiền rồi chuyển thẳng sang luồng hoàn tự động.
            if ($payment['status'] === 'canceled'
                && in_array($payment['payment_state'], ['canceled', 'failed', 'refund_pending'], true)) {
                if (!empty($payment['provider_capture_id']) && $payment['provider_capture_id'] !== $captureId) {
                    throw new \RuntimeException('Đơn hết hạn đã ghi nhận một mã capture PayPal khác.');
                }
                $stmt = $this->db->prepare("UPDATE payments SET payment_status = 1, payment_state = 'refund_pending',
                    refund_status = 'pending', transaction_code = :capture_id, provider_capture_id = :capture_id,
                    paid_at = COALESCE(paid_at, NOW()), failed_at = NULL WHERE id = :id");
                $stmt->execute(['capture_id' => $captureId, 'id' => (int)$payment['id']]);
                $this->writeStatusLog($orderId, 'canceled', 'PayPal capture đến sau khi đơn hết hạn; hệ thống bắt đầu hoàn tự động.', null);
                $this->db->commit();
                return $this->refundCanceledOrderPayment($orderId, '', null);
            }

            if ($payment['payment_state'] !== 'pending' || $payment['status'] === 'canceled') {
                throw new \RuntimeException('Trạng thái đơn hàng không cho phép ghi nhận PayPal.');
            }

            $stmt = $this->db->prepare("UPDATE payments SET payment_status = 1, payment_state = 'paid',
                transaction_code = :capture_id, provider_capture_id = :capture_id, paid_at = NOW(), failed_at = NULL
                WHERE id = :id");
            $stmt->execute(['capture_id' => $captureId, 'id' => (int)$payment['id']]);
            $this->db->prepare('UPDATE orders SET reservation_expires_at = NULL WHERE id = :id')
                ->execute(['id' => $orderId]);
            $this->writeStatusLog($orderId, (string)$payment['status'], 'PayPal xác nhận thanh toán; capture ' . $captureId, null);
            $this->db->commit();
            return ['success' => true, 'message' => 'Thanh toán PayPal thành công.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function reconcilePayPalPayment(int $orderId): array {
        $order = $this->getOrder($orderId);
        $payment = $order['payment'] ?? null;
        if (!$order || !$payment || $payment['payment_method'] !== 'paypal' || empty($payment['provider_order_id'])) {
            return ['success' => false, 'message' => 'Đơn hàng chưa có PayPal Order để đối soát.'];
        }
        if ($payment['payment_state'] === 'paid') {
            return ['success' => true, 'message' => 'Khoản PayPal đã được ghi nhận thanh toán.'];
        }
        try {
            $service = new PayPalService();
            $providerOrder = $service->showOrder((string)$payment['provider_order_id']);
            if (($providerOrder['status'] ?? '') === 'APPROVED') {
                $providerOrder = $service->captureOrder((string)$payment['provider_order_id']);
            }
            $purchaseUnit = $providerOrder['purchase_units'][0] ?? [];
            $capture = $purchaseUnit['payments']['captures'][0] ?? null;
            if (($providerOrder['status'] ?? '') !== 'COMPLETED'
                || !is_array($capture)
                || ($capture['status'] ?? '') !== 'COMPLETED'
                || (string)($purchaseUnit['custom_id'] ?? '') !== (string)$orderId
                || (string)($purchaseUnit['invoice_id'] ?? '') !== (string)$order['order_code']) {
                return ['success' => false, 'message' => 'PayPal chưa có capture hoàn tất cho đơn này (trạng thái: ' . ($providerOrder['status'] ?? 'không xác định') . ').'];
            }
            $amount = $capture['amount'] ?? [];
            $result = $this->completePayPalPayment(
                $orderId,
                (string)$payment['provider_order_id'],
                (string)($capture['id'] ?? ''),
                (string)($amount['currency_code'] ?? ''),
                (float)($amount['value'] ?? 0)
            );
            if ($result['success']) {
                try {
                    (new OrderNotificationService())->processForOrder($orderId, 'order_created');
                } catch (Throwable $ignored) {
                }
            }
            return $result;
        } catch (Throwable $error) {
            return ['success' => false, 'message' => 'Đối soát PayPal thất bại: ' . $error->getMessage()];
        }
    }

    public function processPayPalWebhook(array $event): array {
        $eventId = trim((string)($event['id'] ?? ''));
        $eventType = strtoupper(trim((string)($event['event_type'] ?? '')));
        $resource = is_array($event['resource'] ?? null) ? $event['resource'] : [];
        if ($eventId === '' || $eventType === '') {
            return ['success' => false, 'message' => 'Webhook PayPal thiếu mã sự kiện hoặc loại sự kiện.'];
        }

        $stmt = $this->db->prepare("INSERT IGNORE INTO paypal_webhook_events
            (event_id,event_type,resource_id,verification_status,processing_status,payload_json)
            VALUES (:event_id,:event_type,:resource_id,'SUCCESS','received',:payload)");
        $stmt->execute([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'resource_id' => trim((string)($resource['id'] ?? '')) ?: null,
            'payload' => json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        if ($stmt->rowCount() === 0) {
            $existing = $this->db->prepare('SELECT processing_status FROM paypal_webhook_events WHERE event_id=:event_id LIMIT 1');
            $existing->execute(['event_id' => $eventId]);
            if ($existing->fetchColumn() === 'processed') {
                return ['success' => true, 'message' => 'Sự kiện đã được xử lý trước đó.'];
            }
            // PayPal sẽ gửi lại webhook khi lần trước gặp lỗi. Cho phép chạy lại;
            // các cập nhật thanh toán và hoàn tiền bên dưới đều idempotent.
            $this->db->prepare("UPDATE paypal_webhook_events SET processing_status='received',
                processed_at=NULL,error_message=NULL,payload_json=:payload WHERE event_id=:event_id")
                ->execute([
                    'event_id' => $eventId,
                    'payload' => json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);
        }

        try {
            if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
                $providerOrderId = (string)($resource['supplementary_data']['related_ids']['order_id'] ?? '');
                $find = $this->db->prepare('SELECT order_id FROM payments WHERE provider_order_id = :provider_order_id LIMIT 1');
                $find->execute(['provider_order_id' => $providerOrderId]);
                $orderId = (int)$find->fetchColumn();
                $amount = $resource['amount'] ?? [];
                if ($orderId <= 0) {
                    throw new \RuntimeException('Không tìm thấy đơn nội bộ tương ứng PayPal Order.');
                }
                $result = $this->completePayPalPayment(
                    $orderId,
                    $providerOrderId,
                    (string)($resource['id'] ?? ''),
                    (string)($amount['currency_code'] ?? ''),
                    (float)($amount['value'] ?? 0)
                );
                if (!$result['success']) throw new \RuntimeException($result['message']);
                try {
                    (new OrderNotificationService())->processForOrder($orderId, 'order_created');
                } catch (Throwable $ignored) {
                }
            } elseif ($eventType === 'PAYMENT.CAPTURE.DENIED') {
                $providerOrderId = (string)($resource['supplementary_data']['related_ids']['order_id'] ?? '');
                $find = $this->db->prepare("SELECT order_id FROM payments WHERE provider_order_id = :provider_order_id AND payment_state = 'pending' LIMIT 1");
                $find->execute(['provider_order_id' => $providerOrderId]);
                $orderId = (int)$find->fetchColumn();
                if ($orderId > 0) {
                    $this->db->prepare("UPDATE payments SET payment_state='failed', failed_at=NOW() WHERE order_id=:order_id AND payment_state='pending'")
                        ->execute(['order_id' => $orderId]);
                    $cancel = $this->updateStatus($orderId, 'canceled', 'PayPal từ chối capture; tự động trả kho và coupon.', null);
                    if (!$cancel['success']) throw new \RuntimeException($cancel['message']);
                }
            } elseif ($eventType === 'PAYMENT.CAPTURE.REFUNDED') {
                $this->recordPayPalRefundWebhook($resource);
            }

            $this->db->prepare("UPDATE paypal_webhook_events SET processing_status='processed', processed_at=NOW(), error_message=NULL WHERE event_id=:event_id")
                ->execute(['event_id' => $eventId]);
            return ['success' => true, 'message' => 'Webhook PayPal đã được xử lý.'];
        } catch (Throwable $error) {
            $this->db->prepare("UPDATE paypal_webhook_events SET processing_status='failed', processed_at=NOW(), error_message=:error WHERE event_id=:event_id")
                ->execute(['event_id' => $eventId, 'error' => mb_substr($error->getMessage(), 0, 500)]);
            return ['success' => false, 'message' => $error->getMessage()];
        }
    }

    private function recordPayPalRefundWebhook(array $resource): void {
        $captureId = (string)($resource['supplementary_data']['related_ids']['capture_id'] ?? '');
        $stmt = $this->db->prepare('SELECT p.*, o.final_amount FROM payments p JOIN orders o ON o.id=p.order_id
            WHERE p.provider_capture_id=:capture_id LIMIT 1');
        $stmt->execute(['capture_id' => $captureId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$payment) throw new \RuntimeException('Không tìm thấy capture gốc của khoản hoàn PayPal.');

        $providerRefundId = trim((string)($resource['id'] ?? ''));
        $providerAmount = (float)($resource['amount']['value'] ?? 0);
        $currency = strtoupper((string)($resource['amount']['currency_code'] ?? ''));
        $amountVnd = round($providerAmount * (float)($payment['provider_exchange_rate'] ?: 0), 2);
        if ($providerRefundId === '' || $amountVnd <= 0 || $currency !== strtoupper((string)$payment['provider_currency'])) {
            throw new \RuntimeException('Dữ liệu khoản hoàn PayPal không hợp lệ.');
        }

        $this->db->beginTransaction();
        try {
            $pendingRefund = $this->db->prepare('SELECT after_sale_request_id FROM payment_refunds WHERE provider_refund_id=:provider_refund_id AND status=\'pending\' LIMIT 1 FOR UPDATE');
            $pendingRefund->execute(['provider_refund_id' => $providerRefundId]);
            $afterSaleRequestId = (int)$pendingRefund->fetchColumn();
            $pending = $this->db->prepare("UPDATE payment_refunds SET status='completed', refunded_at=NOW(), failure_reason=NULL
                WHERE provider_refund_id=:provider_refund_id AND status='pending'");
            $pending->execute(['provider_refund_id' => $providerRefundId]);
            $completedExisting = $pending->rowCount() === 1;
            $insert = $this->db->prepare("INSERT IGNORE INTO payment_refunds
                (order_id,payment_id,provider,provider_refund_id,merchant_reference,amount_vnd,provider_amount,provider_currency,status,refunded_at)
                VALUES (:order_id,:payment_id,'paypal',:provider_refund_id,:merchant_reference,:amount_vnd,:provider_amount,:currency,'completed',NOW())");
            $insert->execute([
                'order_id' => (int)$payment['order_id'], 'payment_id' => (int)$payment['id'],
                'provider_refund_id' => $providerRefundId, 'merchant_reference' => 'paypal-webhook-' . $providerRefundId,
                'amount_vnd' => $amountVnd, 'provider_amount' => $providerAmount, 'currency' => $currency,
            ]);
            if ($insert->rowCount() === 1 || $completedExisting) {
                $total = min((float)$payment['final_amount'], (float)$payment['refunded_amount'] + $amountVnd);
                $state = $total + 0.01 >= (float)$payment['final_amount'] ? 'refunded' : 'partially_refunded';
                $this->db->prepare("UPDATE payments SET refunded_amount=:amount, payment_state=:state,
                    refund_status='completed', refund_transaction_code=:code, refunded_at=NOW() WHERE id=:id")
                    ->execute(['amount' => $total, 'state' => $state, 'code' => $providerRefundId, 'id' => (int)$payment['id']]);
                if ($completedExisting && $afterSaleRequestId > 0) {
                    $this->completePendingAfterSaleRefund($afterSaleRequestId, $providerRefundId, $amountVnd);
                }
            }
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function completePendingAfterSaleRefund(int $requestId, string $refundCode, float $refundAmount): void {
        $stmt = $this->db->prepare("SELECT r.*, oi.variant_id, o.delivered_at, o.created_at AS order_created_at
            FROM after_sale_requests r JOIN order_items oi ON oi.id=r.order_item_id
            JOIN orders o ON o.id=r.order_id WHERE r.id=:id FOR UPDATE");
        $stmt->execute(['id' => $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request || $request['refund_status'] !== 'pending') return;
        $quantity = max(1, (int)$request['approved_quantity']);
        $delta = max(0, $quantity - (int)$request['sales_reversed_quantity']);
        if ($delta > 0 && !empty($request['variant_id'])) {
            $this->db->prepare('UPDATE product p JOIN product_variants pv ON pv.product_id=p.id
                SET p.sold_count=GREATEST(0,p.sold_count-:quantity) WHERE pv.id=:variant_id')
                ->execute(['quantity' => $delta, 'variant_id' => (int)$request['variant_id']]);
            $reportDate = date('Y-m-d', strtotime((string)($request['delivered_at'] ?: $request['order_created_at'])));
            $this->db->prepare('UPDATE product_sales_reports SET quantity_sold=GREATEST(0,quantity_sold-:quantity),
                total_revenue=GREATEST(0,total_revenue-:amount) WHERE report_date=:date AND variant_id=:variant_id')
                ->execute(['quantity' => $delta, 'amount' => $refundAmount, 'date' => $reportDate, 'variant_id' => (int)$request['variant_id']]);
        }
        $this->db->prepare("INSERT INTO daily_revenue_reports (report_date,total_orders,gross_revenue,total_discount,net_revenue,refunded_amount)
            VALUES (CURDATE(),0,0,0,0,:amount) ON DUPLICATE KEY UPDATE refunded_amount=refunded_amount+VALUES(refunded_amount),
            net_revenue=net_revenue-VALUES(refunded_amount)")->execute(['amount' => $refundAmount]);
        $this->db->prepare("UPDATE after_sale_requests SET status='refunded', sales_reversed_quantity=:quantity,
            refund_status='completed', refund_transaction_code=:code, refund_processed_at=NOW() WHERE id=:id")
            ->execute(['quantity' => $quantity, 'code' => $refundCode, 'id' => $requestId]);
    }

    public function confirmBankTransfer(int $orderId, string $transactionCode, ?int $adminId = null): array {
        $transactionCode = trim($transactionCode);
        if ($transactionCode === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập mã giao dịch hoặc nội dung đối soát ngân hàng.'];
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('SELECT id, status FROM orders WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order || !in_array($order['status'], ['pending', 'confirmed', 'preparing'], true)) {
                throw new \RuntimeException('Chỉ xác minh chuyển khoản trước khi đơn bắt đầu giao.');
            }

            $stmt = $this->db->prepare('SELECT * FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $stmt->execute(['order_id' => $orderId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$payment || $payment['payment_method'] !== 'bank_transfer') {
                throw new \RuntimeException('Đơn hàng này không sử dụng phương thức chuyển khoản.');
            }
            if ($payment['payment_state'] === 'paid') {
                $this->db->commit();
                return ['success' => true, 'message' => 'Khoản chuyển khoản đã được xác minh trước đó.'];
            }
            if ($payment['payment_state'] !== 'pending') {
                throw new \RuntimeException('Trạng thái thanh toán hiện tại không cho phép xác minh chuyển khoản.');
            }

            $duplicate = $this->db->prepare('SELECT id FROM payments WHERE transaction_code = :transaction_code AND id <> :id LIMIT 1');
            $duplicate->execute(['transaction_code' => $transactionCode, 'id' => (int)$payment['id']]);
            if ($duplicate->fetchColumn()) {
                throw new \RuntimeException('Mã giao dịch này đã được dùng để xác minh một đơn hàng khác.');
            }

            $stmt = $this->db->prepare("UPDATE payments SET payment_status = 1, payment_state = 'paid',
                transaction_code = :transaction_code, paid_at = NOW() WHERE id = :id");
            $stmt->execute(['transaction_code' => $transactionCode, 'id' => (int)$payment['id']]);
            $this->db->prepare('UPDATE orders SET reservation_expires_at = NULL WHERE id = :id')
                ->execute(['id' => $orderId]);
            $this->writeStatusLog($orderId, (string)$order['status'], 'Admin xác minh đã nhận chuyển khoản: ' . $transactionCode, $adminId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Đã xác minh thanh toán chuyển khoản. Có thể tiếp tục xác nhận đơn hàng.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function markPaymentPaid(int $orderId): void {
        $stmt = $this->db->prepare("UPDATE payments
            SET payment_status = 1, payment_state = 'paid', paid_at = COALESCE(paid_at, NOW())
            WHERE order_id = :order_id AND payment_method = 'cod'
              AND payment_state NOT IN ('refunded', 'refund_pending', 'partially_refunded')");
        $stmt->execute(['order_id' => $orderId]);
    }

    private function markPaymentCanceled(int $orderId): void {
        $stmt = $this->db->prepare("UPDATE payments
            SET payment_state = 'canceled'
            WHERE order_id = :order_id AND payment_state = 'pending'");
        $stmt->execute(['order_id' => $orderId]);
        $stmt = $this->db->prepare("UPDATE payments
            SET payment_state = 'refund_pending', refund_status = 'pending'
            WHERE order_id = :order_id AND payment_state IN ('paid', 'partially_refunded')");
        $stmt->execute(['order_id' => $orderId]);
    }

    public function refundCanceledOrderPayment(int $orderId, string $transactionCode, ?int $adminId = null): array {
        $transactionCode = trim($transactionCode);
        try {
            // Gọi cổng thanh toán tuyệt đối không được nằm trong transaction DB:
            // transaction DB có rollback cũng không thể đảo ngược một refund đã xảy ra trên PayPal.
            $preview = $this->db->prepare("SELECT o.id, o.status, o.final_amount, p.id payment_id, p.payment_method, p.payment_state,
                    p.refunded_amount, p.provider_capture_id, p.provider_currency, p.provider_exchange_rate
                FROM orders o JOIN payments p ON p.order_id = o.id
                WHERE o.id = :order_id ORDER BY p.id DESC LIMIT 1");
            $preview->execute(['order_id' => $orderId]);
            $row = $preview->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['status'] !== 'canceled' || $row['payment_state'] !== 'refund_pending') {
                throw new \RuntimeException('Chỉ hoàn tiền tại đây cho đơn đã hủy đang chờ hoàn.');
            }
            $amount = max(0, (float)$row['final_amount'] - (float)$row['refunded_amount']);
            if ($amount <= 0) {
                throw new \RuntimeException('Đơn hàng không còn số tiền cần hoàn.');
            }
            $providerAmount = null;
            $providerCurrency = null;
            $provider = 'manual';
            $merchantReference = 'canceled-order-' . $orderId;
            $refundStatus = 'completed';
            if ($row['payment_method'] === 'paypal') {
                if (empty($row['provider_capture_id']) || (float)$row['provider_exchange_rate'] <= 0) {
                    throw new \RuntimeException('Khoản PayPal thiếu capture hoặc tỷ giá gốc để hoàn tiền an toàn.');
                }
                $provider = 'paypal';
                $providerCurrency = strtoupper((string)$row['provider_currency']);
                $providerAmount = round($amount / (float)$row['provider_exchange_rate'], 2);
                $refund = (new PayPalService())->refundCapture(
                    (string)$row['provider_capture_id'], $providerAmount, $providerCurrency,
                    $merchantReference, 'Hoàn đơn hàng Liên Hoa #' . $orderId
                );
                $refundStatus = strtolower((string)($refund['status'] ?? ''));
                $transactionCode = trim((string)($refund['id'] ?? ''));
                if ($transactionCode === '' || !in_array($refundStatus, ['completed', 'pending'], true)) {
                    throw new \RuntimeException('PayPal chưa chấp nhận yêu cầu hoàn tiền.');
                }
            } elseif ($transactionCode === '') {
                throw new \RuntimeException('Vui lòng nhập mã giao dịch hoặc biên nhận hoàn tiền.');
            }

            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT o.id, o.status, o.final_amount, p.id payment_id, p.payment_method, p.payment_state,
                    p.refunded_amount FROM orders o JOIN payments p ON p.order_id = o.id
                WHERE o.id = :order_id ORDER BY p.id DESC LIMIT 1 FOR UPDATE");
            $stmt->execute(['order_id' => $orderId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['status'] !== 'canceled' || $row['payment_state'] !== 'refund_pending') {
                throw new \RuntimeException('Chỉ hoàn tiền tại đây cho đơn đã hủy đang chờ hoàn.');
            }
            $duplicate = $this->db->prepare('SELECT id FROM payment_refunds WHERE provider_refund_id = :code OR merchant_reference = :reference LIMIT 1');
            $duplicate->execute(['code' => $transactionCode, 'reference' => $merchantReference]);
            if ($duplicate->fetchColumn()) {
                $this->db->commit();
                return ['success' => true, 'message' => 'Khoản hoàn tiền này đã được ghi nhận trước đó; không tạo giao dịch trùng.'];
            }
            $this->db->prepare("INSERT INTO payment_refunds
                (order_id,payment_id,provider,provider_refund_id,merchant_reference,amount_vnd,provider_amount,provider_currency,status,refunded_at)
                VALUES (:order_id,:payment_id,:provider,:provider_refund_id,:merchant_reference,:amount_vnd,:provider_amount,:currency,:status," . ($refundStatus === 'completed' ? 'NOW()' : 'NULL') . ")")
                ->execute([
                    'order_id' => $orderId, 'payment_id' => (int)$row['payment_id'], 'provider' => $provider,
                    'provider_refund_id' => $transactionCode, 'merchant_reference' => $merchantReference,
                    'amount_vnd' => $amount, 'provider_amount' => $providerAmount, 'currency' => $providerCurrency, 'status' => $refundStatus,
                ]);
            $stmt = $this->db->prepare("UPDATE payments SET payment_status = :payment_status, payment_state = :payment_state,
                refund_status = :refund_status, refund_transaction_code = :code,
                refunded_amount = :total_refunded, refunded_at = " . ($refundStatus === 'completed' ? 'NOW()' : 'NULL') . " WHERE id = :id");
            $stmt->execute([
                'code' => $transactionCode,
                'payment_status' => $refundStatus === 'completed' ? 2 : 1,
                'payment_state' => $refundStatus === 'completed' ? 'refunded' : 'refund_pending',
                'refund_status' => $refundStatus,
                'total_refunded' => $refundStatus === 'completed' ? (float)$row['final_amount'] : (float)$row['refunded_amount'],
                'id' => (int)$row['payment_id'],
            ]);
            $this->writeStatusLog($orderId, 'canceled', ($refundStatus === 'completed' ? 'Admin xác nhận hoàn ' : 'PayPal đang xử lý hoàn ') . number_format($amount, 0, ',', '.') . ' ₫; mã ' . $transactionCode, $adminId);
            $this->db->commit();
            return ['success' => true, 'message' => $refundStatus === 'completed'
                ? 'Đã ghi nhận hoàn toàn bộ tiền của đơn bị hủy.'
                : 'PayPal đã nhận yêu cầu hoàn tiền. Đơn sẽ giữ trạng thái chờ hoàn cho đến khi webhook xác nhận.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
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

    /**
     * Hủy các đơn chưa thanh toán đã quá thời hạn và trả toàn bộ kho/coupon
     * đã giữ. Hàm idempotent nên có thể gọi nhẹ từ các request thông thường.
     */
    public function expirePendingOrders(int $limit = 100): array {
        $limit = max(1, min(500, $limit));
        // Trước khi hủy một đơn PayPal đã hết thời gian giữ hàng, luôn hỏi lại
        // PayPal. Điều này cứu được trường hợp khách đã trả tiền nhưng đóng tab,
        // mất mạng hoặc callback về website không chạy. Webhook vẫn là cơ chế
        // chính; bước đối soát này là lớp dự phòng an toàn.
        $reconciliation = $this->reconcileExpiringPayPalPayments($limit);
        $deferredPayPalOrders = array_fill_keys($reconciliation['deferred'], true);

        $stmt = $this->db->prepare("SELECT o.id
            FROM orders o
            LEFT JOIN payments p ON p.id = (
                SELECT p2.id FROM payments p2 WHERE p2.order_id = o.id ORDER BY p2.id DESC LIMIT 1
            )
            WHERE o.status = 'pending'
              AND COALESCE(p.payment_state, 'pending') = 'pending'
              AND (
                    (o.reservation_expires_at IS NOT NULL AND o.reservation_expires_at <= NOW())
                 OR (o.reservation_expires_at IS NULL AND p.payment_method = 'paypal' AND o.created_at <= DATE_SUB(NOW(), INTERVAL 30 MINUTE))
                 OR (o.reservation_expires_at IS NULL AND COALESCE(p.payment_method, 'cod') <> 'paypal' AND o.created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR))
              )
            ORDER BY o.created_at ASC
            LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $expired = 0;
        $failed = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $orderId) {
            if (isset($deferredPayPalOrders[(int)$orderId])) {
                continue;
            }
            $result = $this->updateStatus((int)$orderId, 'canceled', 'Tự động hủy do quá thời hạn thanh toán/giữ hàng.', null, false);
            if ($result['success']) {
                $expired++;
            } else {
                $failed[(int)$orderId] = $result['message'];
            }
        }
        return [
            'expired' => $expired,
            'failed' => $failed,
            'paypal_reconciled' => $reconciliation['reconciled'],
            'paypal_deferred' => count($reconciliation['deferred'])
        ];
    }

    private function reconcileExpiringPayPalPayments(int $limit): array {
        $stmt = $this->db->prepare("SELECT o.id
            FROM orders o
            JOIN payments p ON p.id = (
                SELECT p2.id FROM payments p2 WHERE p2.order_id = o.id ORDER BY p2.id DESC LIMIT 1
            )
            WHERE o.status = 'pending'
              AND p.payment_method = 'paypal'
              AND p.payment_state = 'pending'
              AND p.provider_order_id IS NOT NULL
              AND p.provider_order_id <> ''
              AND (
                    (o.reservation_expires_at IS NOT NULL AND o.reservation_expires_at <= NOW())
                 OR (o.reservation_expires_at IS NULL AND o.created_at <= DATE_SUB(NOW(), INTERVAL 30 MINUTE))
              )
            ORDER BY o.created_at ASC
            LIMIT :limit");
        $stmt->bindValue(':limit', max(1, min(500, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        $orderIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        if (!$orderIds) {
            return ['reconciled' => 0, 'deferred' => []];
        }

        $service = new PayPalService();
        if (!$service->isConfigured()) {
            // Không được hủy một đơn đã gắn PayPal Order khi khóa/API/webhook
            // đang thiếu cấu hình: tiền có thể đã được capture ở phía PayPal.
            return ['reconciled' => 0, 'deferred' => $orderIds];
        }

        $reconciled = 0;
        $deferred = [];
        foreach ($orderIds as $orderId) {
            $result = $this->reconcilePayPalPayment((int)$orderId);
            if ($result['success']) {
                $reconciled++;
                continue;
            }

            // Khi PayPal không phản hồi được, không được hủy đơn ngay vì tiền
            // có thể đã bị capture. Lần cron/request kế tiếp sẽ đối soát lại.
            if (str_starts_with((string)($result['message'] ?? ''), 'Đối soát PayPal thất bại:')) {
                $deferred[] = (int)$orderId;
            }
        }

        return ['reconciled' => $reconciled, 'deferred' => $deferred];
    }

    private function reserveStockForPendingOrder(int $orderId, array $items): void {
        $reserveVariant = $this->db->prepare('UPDATE product_variants
            SET reserved_quantity = reserved_quantity + :quantity
            WHERE id = :variant_id AND stock_quantity - reserved_quantity >= :quantity');
        $reserveProduct = $this->db->prepare('UPDATE product p
            JOIN product_variants pv ON pv.product_id = p.id
            SET p.reserved_quantity = p.reserved_quantity + :quantity
            WHERE pv.id = :variant_id');

        foreach ($items as $item) {
            $params = ['quantity' => (int)$item['quantity'], 'variant_id' => (int)$item['variant_id']];
            $reserveVariant->execute($params);
            if ($reserveVariant->rowCount() !== 1) {
                throw new \RuntimeException(($item['product_name_snapshot'] ?? 'Sản phẩm') . ' vừa hết số lượng khả dụng. Vui lòng cập nhật giỏ hàng.');
            }
            $reserveProduct->execute($params);
        }
        $this->writeStatusLog($orderId, 'pending', 'Đã giữ tồn kho cho đơn hàng.', null);
    }

    private function releasePendingReservation(int $orderId): void {
        $stmt = $this->db->prepare("SELECT reservation_status FROM orders WHERE id = :id FOR UPDATE");
        $stmt->execute(['id' => $orderId]);
        if ($stmt->fetchColumn() !== 'reserved') {
            return;
        }

        $stmt = $this->db->prepare('SELECT variant_id, quantity FROM order_items
            WHERE order_id = :order_id AND variant_id IS NOT NULL');
        $stmt->execute(['order_id' => $orderId]);
        $releaseVariant = $this->db->prepare('UPDATE product_variants
            SET reserved_quantity = GREATEST(0, reserved_quantity - :quantity) WHERE id = :variant_id');
        $releaseProduct = $this->db->prepare('UPDATE product p
            JOIN product_variants pv ON pv.product_id = p.id
            SET p.reserved_quantity = GREATEST(0, p.reserved_quantity - :quantity) WHERE pv.id = :variant_id');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $params = ['quantity' => (int)$item['quantity'], 'variant_id' => (int)$item['variant_id']];
            $releaseVariant->execute($params);
            $releaseProduct->execute($params);
        }
        $this->db->prepare("UPDATE orders SET reservation_status = 'released', reservation_expires_at = NULL,
            stock_reservation_closed_at = NOW() WHERE id = :id")->execute(['id' => $orderId]);
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

        $orderStmt = $this->db->prepare('SELECT reservation_status FROM orders WHERE id = :id FOR UPDATE');
        $orderStmt->execute(['id' => $orderId]);
        $usesReservation = $orderStmt->fetchColumn() === 'reserved';

        $stmt = $this->db->prepare("
            SELECT
                oi.variant_id,
                oi.quantity,
                pv.stock_quantity,
                pv.reserved_quantity,
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
            if ($usesReservation && (int)($item['reserved_quantity'] ?? 0) < $quantity) {
                throw new \Exception("$productName không còn đủ số lượng đã giữ. Vui lòng hủy đơn và tạo lại.");
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
        $closeVariantReservation = $this->db->prepare('UPDATE product_variants
            SET reserved_quantity = GREATEST(0, reserved_quantity - :quantity) WHERE id = :variant_id');
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

            if ($usesReservation) {
                $closeVariantReservation->execute(['quantity' => $quantity, 'variant_id' => $variantId]);
            } else {
                $updateReserved->execute([
                    'quantity' => $quantity,
                    'variant_id' => $variantId
                ]);
            }
        }
        $stmt = $this->db->prepare("UPDATE orders SET reservation_status = 'committed', reservation_expires_at = NULL,
            stock_reservation_closed_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $orderId]);
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
        $this->db->prepare("UPDATE orders SET reservation_status = 'released', reservation_expires_at = NULL,
            stock_reservation_closed_at = NOW() WHERE id = :id")->execute(['id' => $orderId]);
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
        $this->db->prepare("UPDATE orders SET reservation_status = 'fulfilled', reservation_expires_at = NULL,
            stock_reservation_closed_at = COALESCE(stock_reservation_closed_at, NOW()) WHERE id = :id")
            ->execute(['id' => $orderId]);
    }

    private function canTransition(string $from, string $to): bool {
        $transitions = [
            'pending' => ['confirmed', 'canceled'],
            'confirmed' => ['preparing', 'canceled'],
            'preparing' => ['shipping', 'canceled'],
            // Giao thất bại, khách từ chối nhận hoặc hàng hoàn về: chuyển sang hủy
            // có ghi chú; kho/coupon/hoàn tiền sẽ được xử lý cùng một transaction.
            'shipping' => ['delivered', 'canceled'],
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
            SELECT pv.*, p.name AS product_name, p.base_price, p.category_id, p.status AS product_status,
                p.unit_name
            FROM product_variants pv
            LEFT JOIN product p ON pv.product_id = p.id
            WHERE pv.id = :variant_id
            FOR UPDATE
        ");
        $stmt->execute(['variant_id' => $variantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function allocateItemDiscounts(array $items, ?array $coupon, float $discount): array {
        foreach ($items as $index => $item) {
            $items[$index]['discount_amount'] = 0.0;
        }
        $discount = max(0, $discount);
        if ($discount <= 0 || empty($items)) {
            return $items;
        }

        $eligibleIndexes = [];
        $eligibleSubtotal = 0.0;
        foreach ($items as $index => $item) {
            if (!$coupon || (empty($coupon['product_id']) && empty($coupon['category_id']))) {
                $matches = true;
            } elseif (!empty($coupon['product_id'])) {
                $matches = (int)$coupon['product_id'] === (int)$item['product_id'];
            } else {
                $matches = (int)$coupon['category_id'] === (int)($item['category_id'] ?? 0);
            }
            if (!$matches) {
                continue;
            }
            $eligibleIndexes[] = $index;
            $eligibleSubtotal += (float)$item['price_at_time'] * (int)$item['quantity'];
        }
        if ($eligibleSubtotal <= 0 || empty($eligibleIndexes)) {
            return $items;
        }

        $remaining = min($discount, $eligibleSubtotal);
        $lastIndex = end($eligibleIndexes);
        foreach ($eligibleIndexes as $index) {
            $lineGross = (float)$items[$index]['price_at_time'] * (int)$items[$index]['quantity'];
            $lineDiscount = $index === $lastIndex
                ? $remaining
                : min($remaining, round($discount * ($lineGross / $eligibleSubtotal), 2));
            $items[$index]['discount_amount'] = $lineDiscount;
            $remaining = max(0, round($remaining - $lineDiscount, 2));
        }
        return $items;
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
