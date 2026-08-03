<?php

namespace App\Models;

use PDO;
use Throwable;

class AfterSale extends BaseModel {
    private const TYPES = ['return', 'exchange', 'warranty', 'refund'];
    private const STATUSES = ['pending', 'approved', 'rejected', 'received', 'refunded', 'completed'];
    private const REFUND_TYPES = ['return', 'refund'];

    public function createRequest(int $userId, int $orderItemId, string $type, string $reason, int $requestedQuantity = 1, array $evidencePaths = []): array {
        $type = strtolower(trim($type));
        $reason = trim($reason);
        if (!in_array($type, self::TYPES, true)) {
            return ['success' => false, 'message' => 'Loại yêu cầu sau bán hàng không hợp lệ.'];
        }
        if ($reason === '') {
            return ['success' => false, 'message' => 'Vui lòng nêu lý do đổi trả/bảo hành.'];
        }
        if ($type === 'warranty' && empty($evidencePaths)) {
            return ['success' => false, 'message' => 'Yêu cầu bảo hành cần ít nhất một ảnh bằng chứng sản phẩm lỗi.'];
        }

        $stmt = $this->db->prepare("SELECT oi.id, oi.order_id, oi.quantity, oi.price_at_time,
                o.status, o.user_id, o.delivered_at, o.created_at, pv.product_id
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            LEFT JOIN product_variants pv ON pv.id = oi.variant_id
            WHERE oi.id = :order_item_id AND o.user_id = :user_id LIMIT 1");
        $stmt->execute(['order_item_id' => $orderItemId, 'user_id' => $userId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item || !in_array($item['status'], ['delivered', 'completed'], true)) {
            return ['success' => false, 'message' => 'Chỉ được tạo yêu cầu sau bán hàng sau khi đơn đã giao thành công.'];
        }

        $deadline = $this->calculateDeadline($item, $type);
        if ($deadline < new \DateTimeImmutable('now')) {
            return ['success' => false, 'message' => 'Yêu cầu đã quá thời hạn đổi trả/bảo hành theo chính sách.'];
        }

        $maxQuantity = max(1, (int)$item['quantity']);
        if ($requestedQuantity < 1 || $requestedQuantity > $maxQuantity) {
            return ['success' => false, 'message' => 'Số lượng yêu cầu không hợp lệ.'];
        }
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(CASE WHEN status = 'rejected' THEN 0 ELSE COALESCE(NULLIF(approved_quantity, 0), requested_quantity) END), 0)
            FROM after_sale_requests WHERE order_item_id = :order_item_id");
        $stmt->execute(['order_item_id' => $orderItemId]);
        $alreadyRequested = (int)$stmt->fetchColumn();
        if ($alreadyRequested + $requestedQuantity > $maxQuantity) {
            return ['success' => false, 'message' => 'Số lượng yêu cầu vượt quá số lượng đã mua hoặc đã có yêu cầu trước đó.'];
        }

        try {
            $this->db->beginTransaction();
            $refundAmount = in_array($type, self::REFUND_TYPES, true)
                ? (float)$item['price_at_time'] * $requestedQuantity
                : 0;
            $stmt = $this->db->prepare("INSERT INTO after_sale_requests
                (user_id, order_id, order_item_id, request_type, reason, requested_quantity,
                 approved_quantity, return_deadline, refund_amount, refund_status)
                VALUES (:user_id, :order_id, :order_item_id, :request_type, :reason,
                    :requested_quantity, 0, :return_deadline, :refund_amount, :refund_status)");
            $stmt->execute([
                'user_id' => $userId,
                'order_id' => (int)$item['order_id'],
                'order_item_id' => $orderItemId,
                'request_type' => $type,
                'reason' => $reason,
                'requested_quantity' => $requestedQuantity,
                'return_deadline' => $deadline->format('Y-m-d H:i:s'),
                'refund_amount' => $refundAmount,
                'refund_status' => in_array($type, self::REFUND_TYPES, true) ? 'pending' : 'not_requested'
            ]);
            $requestId = (int)$this->db->lastInsertId();

            if ($evidencePaths) {
                $insertEvidence = $this->db->prepare('INSERT INTO after_sale_evidence (request_id, image_url) VALUES (:request_id, :image_url)');
                foreach (array_slice($evidencePaths, 0, 5) as $path) {
                    $insertEvidence->execute(['request_id' => $requestId, 'image_url' => (string)$path]);
                }
            }

            $this->db->commit();
            return ['success' => true, 'request_id' => $requestId, 'message' => 'Đã gửi yêu cầu sau bán hàng. Bộ phận chăm sóc khách hàng sẽ xử lý.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Không thể tạo yêu cầu sau bán hàng: ' . $e->getMessage()];
        }
    }

    public function getByUser(int $userId): array {
        $stmt = $this->db->prepare("SELECT r.*, o.order_code, oi.product_name_snapshot, oi.variant_size_snapshot, oi.variant_color_snapshot,
                (SELECT GROUP_CONCAT(e.image_url SEPARATOR '||') FROM after_sale_evidence e WHERE e.request_id = r.id) AS evidence_images
            FROM after_sale_requests r
            JOIN orders o ON o.id = r.order_id
            JOIN order_items oi ON oi.id = r.order_item_id
            WHERE r.user_id = :user_id ORDER BY r.created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminRequests(int $limit = 100): array {
        $stmt = $this->db->prepare("SELECT r.*, o.order_code, u.full_name, u.email,
                COALESCE(oi.product_name_snapshot, p.name) AS product_name,
                (SELECT GROUP_CONCAT(e.image_url SEPARATOR '||') FROM after_sale_evidence e WHERE e.request_id = r.id) AS evidence_images
            FROM after_sale_requests r
            JOIN orders o ON o.id = r.order_id
            JOIN user u ON u.id = r.user_id
            JOIN order_items oi ON oi.id = r.order_item_id
            LEFT JOIN product_variants pv ON pv.id = oi.variant_id
            LEFT JOIN product p ON p.id = pv.product_id
            ORDER BY r.created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(
        int $id,
        string $status,
        string $note,
        int $approvedQuantity = 0,
        bool $restockable = true,
        string $refundTransactionCode = ''
    ): array {
        $status = strtolower(trim($status));
        if (!in_array($status, self::STATUSES, true)) {
            return ['success' => false, 'message' => 'Trạng thái xử lý không hợp lệ.'];
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT r.*, oi.quantity AS item_quantity, oi.price_at_time, oi.variant_id,
                    o.order_code, o.status AS order_status, o.delivered_at, o.created_at AS order_created_at
                FROM after_sale_requests r
                JOIN order_items oi ON oi.id = r.order_item_id
                JOIN orders o ON o.id = r.order_id
                WHERE r.id = :id FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                throw new \RuntimeException('Không tìm thấy yêu cầu sau bán hàng.');
            }

            $current = (string)$request['status'];
            if ($current === $status) {
                $stmt = $this->db->prepare('UPDATE after_sale_requests SET resolution_note = :note WHERE id = :id');
                $stmt->execute(['note' => trim($note), 'id' => $id]);
                $this->db->commit();
                return ['success' => true, 'message' => 'Không có thay đổi trạng thái mới.'];
            }
            if (!$this->canTransition($current, $status, (string)$request['request_type'])) {
                throw new \RuntimeException('Không thể chuyển yêu cầu từ ' . $this->statusLabel($current) . ' sang ' . $this->statusLabel($status) . '.');
            }

            $approvedQuantity = (int)$request['approved_quantity'];
            if ($status === 'approved') {
                $approvedQuantity = $approvedQuantity > 0 ? $approvedQuantity : (int)$request['requested_quantity'];
                $approvedQuantity = max(1, min((int)$request['requested_quantity'], $approvedQuantity));
                $this->assertQuantityStillAvailable($request, $approvedQuantity);
                $refundAmount = in_array($request['request_type'], self::REFUND_TYPES, true)
                    ? (float)$request['price_at_time'] * $approvedQuantity
                    : 0;
                $stmt = $this->db->prepare("UPDATE after_sale_requests SET status = 'approved', approved_quantity = :approved_quantity,
                        refund_amount = :refund_amount, restockable = :restockable, approved_at = NOW(),
                        refund_status = CASE WHEN :is_refund = 1 THEN 'pending' ELSE refund_status END,
                        resolution_note = :note WHERE id = :id");
                $stmt->execute([
                    'approved_quantity' => $approvedQuantity,
                    'refund_amount' => $refundAmount,
                    'restockable' => $restockable ? 1 : 0,
                    'is_refund' => in_array($request['request_type'], self::REFUND_TYPES, true) ? 1 : 0,
                    'note' => trim($note),
                    'id' => $id
                ]);
                $this->markOrderRefundPending((int)$request['order_id'], $refundAmount, $request['request_type']);
            } elseif ($status === 'received') {
                if ($current !== 'approved' || $approvedQuantity <= 0) {
                    throw new \RuntimeException('Chỉ được xác nhận đã nhận hàng sau khi yêu cầu đã được duyệt.');
                }
                $this->processReceivedProduct($request, $approvedQuantity, $restockable);
                $stmt = $this->db->prepare("UPDATE after_sale_requests SET status = 'received', restockable = :restockable,
                        received_at = NOW(), resolution_note = :note WHERE id = :id");
                $stmt->execute(['restockable' => $restockable ? 1 : 0, 'note' => trim($note), 'id' => $id]);
            } elseif ($status === 'refunded') {
                if (!in_array($request['request_type'], self::REFUND_TYPES, true)) {
                    throw new \RuntimeException('Yêu cầu đổi sản phẩm/bảo hành không có bước hoàn tiền.');
                }
                if ($request['request_type'] === 'return' && $current !== 'received') {
                    throw new \RuntimeException('Đổi trả phải xác nhận đã nhận lại sản phẩm trước khi hoàn tiền.');
                }
                $approvedQuantity = max(1, (int)$request['approved_quantity']);
                if (trim($refundTransactionCode) === '') {
                    throw new \RuntimeException('Vui lòng nhập mã giao dịch hoặc biên nhận hoàn tiền.');
                }
                $this->reverseDeliveredSales($request, $approvedQuantity);
                $this->markPaymentRefunded((int)$request['order_id'], (float)$request['refund_amount'], $refundTransactionCode);
                $stmt = $this->db->prepare("UPDATE after_sale_requests SET status = 'refunded',
                        sales_reversed_quantity = :sales_reversed_quantity, refund_status = 'completed',
                        refund_transaction_code = :refund_transaction_code, refund_processed_at = NOW(),
                        resolution_note = :note WHERE id = :id");
                $stmt->execute([
                    'sales_reversed_quantity' => $approvedQuantity,
                    'refund_transaction_code' => trim($refundTransactionCode),
                    'note' => trim($note),
                    'id' => $id
                ]);
                $this->adjustRevenueReports($request, $approvedQuantity, (float)$request['refund_amount']);
            } elseif ($status === 'completed') {
                if ($request['request_type'] === 'return' && $current !== 'refunded') {
                    throw new \RuntimeException('Yêu cầu đổi trả chỉ hoàn tất sau khi đã hoàn tiền.');
                }
                if (in_array($request['request_type'], ['exchange', 'warranty'], true) && $current !== 'received') {
                    throw new \RuntimeException('Yêu cầu đổi sản phẩm/bảo hành chỉ hoàn tất sau khi đã nhận sản phẩm.');
                }
                $stmt = $this->db->prepare("UPDATE after_sale_requests SET status = 'completed', completed_at = NOW(), resolution_note = :note WHERE id = :id");
                $stmt->execute(['note' => trim($note), 'id' => $id]);
            } else {
                if ($status === 'rejected') {
                    $this->markOrderRefundCanceled((int)$request['order_id'], (string)$request['request_type']);
                }
                $stmt = $this->db->prepare('UPDATE after_sale_requests SET status = :status, resolution_note = :note WHERE id = :id');
                $stmt->execute(['status' => $status, 'note' => trim($note), 'id' => $id]);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã cập nhật yêu cầu và đồng bộ kho, doanh thu, thanh toán theo nghiệp vụ.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function calculateDeadline(array $item, string $type): \DateTimeImmutable {
        $days = $type === 'warranty' ? 180 : 7;
        $base = $item['delivered_at'] ?: $item['created_at'];
        return (new \DateTimeImmutable((string)$base))->modify('+' . $days . ' days');
    }

    private function canTransition(string $from, string $to, string $type): bool {
        if ($from === $to && $to === 'pending') {
            return true;
        }
        if ($from === $to) {
            return false;
        }
        $map = [
            'pending' => ['approved', 'rejected'],
            'approved' => ['received', 'refunded', 'rejected'],
            'received' => ['refunded', 'completed'],
            'refunded' => ['completed'],
            'rejected' => [],
            'completed' => []
        ];
        return in_array($to, $map[$from] ?? [], true);
    }

    private function statusLabel(string $status): string {
        return [
            'pending' => 'Chờ xử lý',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'received' => 'Đã nhận hàng',
            'refunded' => 'Đã hoàn tiền',
            'completed' => 'Hoàn tất'
        ][$status] ?? $status;
    }

    private function assertQuantityStillAvailable(array $request, int $approvedQuantity): void {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(CASE WHEN status = 'rejected' THEN 0 ELSE COALESCE(NULLIF(approved_quantity, 0), requested_quantity) END), 0)
            FROM after_sale_requests WHERE order_item_id = :order_item_id AND id <> :id");
        $stmt->execute(['order_item_id' => (int)$request['order_item_id'], 'id' => (int)$request['id']]);
        if ((int)$stmt->fetchColumn() + $approvedQuantity > (int)$request['item_quantity']) {
            throw new \RuntimeException('Số lượng duyệt vượt quá số lượng còn lại của sản phẩm trong đơn.');
        }
    }

    private function processReceivedProduct(array $request, int $quantity, bool $restockable): void {
        $already = (int)$request['inventory_processed_quantity'];
        $delta = max(0, $quantity - $already);
        if ($delta <= 0) {
            return;
        }

        if ($restockable && !empty($request['variant_id'])) {
            $reason = 'Nhập lại hàng sau đổi trả, yêu cầu #' . (int)$request['id'];
            $stmt = $this->db->prepare('INSERT INTO inventory_logs (variant_id, quantity_changed, reason) VALUES (:variant_id, :quantity_changed, :reason)');
            $stmt->execute(['variant_id' => (int)$request['variant_id'], 'quantity_changed' => $delta, 'reason' => $reason]);
            if (!$this->triggerExists('trg_after_insert_inventory_log')) {
                $stmt = $this->db->prepare('UPDATE product_variants SET stock_quantity = stock_quantity + :quantity WHERE id = :variant_id');
                $stmt->execute(['quantity' => $delta, 'variant_id' => (int)$request['variant_id']]);
            }
        }

        $stmt = $this->db->prepare('UPDATE product p JOIN product_variants pv ON pv.product_id = p.id
            SET p.returned_count = p.returned_count + :quantity WHERE pv.id = :variant_id');
        if (!empty($request['variant_id'])) {
            $stmt->execute(['quantity' => $delta, 'variant_id' => (int)$request['variant_id']]);
        }

        $stmt = $this->db->prepare('UPDATE after_sale_requests SET inventory_processed_quantity = :quantity WHERE id = :id');
        $stmt->execute(['quantity' => $quantity, 'id' => (int)$request['id']]);
    }

    private function reverseDeliveredSales(array $request, int $quantity): void {
        if (!in_array($request['request_type'], self::REFUND_TYPES, true)) {
            return;
        }
        $already = (int)$request['sales_reversed_quantity'];
        $delta = max(0, $quantity - $already);
        if ($delta <= 0 || empty($request['variant_id'])) {
            return;
        }

        $stmt = $this->db->prepare('UPDATE product p JOIN product_variants pv ON pv.product_id = p.id
            SET p.sold_count = GREATEST(0, p.sold_count - :quantity)
            WHERE pv.id = :variant_id');
        $stmt->execute(['quantity' => $delta, 'variant_id' => (int)$request['variant_id']]);
    }

    private function markOrderRefundPending(int $orderId, float $amount, string $type): void {
        if (!in_array($type, self::REFUND_TYPES, true)) {
            return;
        }
        $stmt = $this->db->prepare("UPDATE payments SET refund_status = 'pending', payment_state = CASE WHEN payment_state = 'paid' THEN 'refund_pending' ELSE payment_state END,
                refunded_amount = :amount WHERE order_id = :order_id");
        $stmt->execute(['amount' => $amount, 'order_id' => $orderId]);
    }

    private function markOrderRefundCanceled(int $orderId, string $type): void {
        if (!in_array($type, self::REFUND_TYPES, true)) {
            return;
        }
        $stmt = $this->db->prepare("UPDATE payments SET refund_status = 'not_requested', payment_state = CASE WHEN payment_state = 'refund_pending' THEN 'paid' ELSE payment_state END,
                refunded_amount = 0 WHERE order_id = :order_id AND refund_status = 'pending'");
        $stmt->execute(['order_id' => $orderId]);
    }

    private function markPaymentRefunded(int $orderId, float $amount, string $transactionCode): void {
        $stmt = $this->db->prepare('SELECT id FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['order_id' => $orderId]);
        $paymentId = $stmt->fetchColumn();
        if (!$paymentId) {
            $stmt = $this->db->prepare("INSERT INTO payments (order_id, payment_method, payment_status, payment_state, refund_status, refund_transaction_code, refunded_amount, refunded_at)
                VALUES (:order_id, 'manual', 2, 'refunded', 'completed', :transaction_code, :amount, NOW())");
            $stmt->execute(['order_id' => $orderId, 'transaction_code' => $transactionCode, 'amount' => $amount]);
            return;
        }

        $stmt = $this->db->prepare("UPDATE payments SET payment_status = 2, payment_state = 'refunded', refund_status = 'completed',
                refund_transaction_code = :transaction_code, refunded_amount = :amount, refunded_at = NOW()
            WHERE id = :id");
        $stmt->execute(['transaction_code' => $transactionCode, 'amount' => $amount, 'id' => (int)$paymentId]);
    }

    private function adjustRevenueReports(array $request, int $quantity, float $refundAmount): void {
        $reportDate = date('Y-m-d', strtotime((string)($request['delivered_at'] ?: $request['order_created_at'])));
        if ($this->tableHasColumn('product_sales_reports', 'quantity_sold')) {
            $stmt = $this->db->prepare("UPDATE product_sales_reports
                SET quantity_sold = GREATEST(0, quantity_sold - :quantity),
                    total_revenue = GREATEST(0, total_revenue - :amount)
                WHERE report_date = :report_date AND variant_id = :variant_id");
            $stmt->execute(['quantity' => $quantity, 'amount' => $refundAmount, 'report_date' => $reportDate, 'variant_id' => (int)$request['variant_id']]);
        }

        if ($this->tableHasColumn('daily_revenue_reports', 'refunded_amount')) {
            $stmt = $this->db->prepare("INSERT INTO daily_revenue_reports (report_date, total_orders, gross_revenue, total_discount, net_revenue, refunded_amount)
                VALUES (:report_date, 0, 0, 0, 0, :amount)
                ON DUPLICATE KEY UPDATE refunded_amount = refunded_amount + VALUES(refunded_amount), net_revenue = net_revenue - VALUES(refunded_amount)");
            $stmt->execute(['report_date' => $reportDate, 'amount' => $refundAmount]);
        }
    }

    private function tableHasColumn(string $table, string $column): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name');
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function triggerExists(string $trigger): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = :trigger_name');
        $stmt->execute(['trigger_name' => $trigger]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
