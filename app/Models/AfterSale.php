<?php

namespace App\Models;

use PDO;
use Throwable;
use App\Services\PayPalService;

class AfterSale extends BaseModel {
    private const TYPES = ['return', 'exchange', 'warranty', 'refund'];
    private const STATUSES = ['pending', 'approved', 'rejected', 'received', 'replacement_shipped', 'refunded', 'completed'];
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

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT oi.id, oi.order_id, oi.quantity AS item_quantity, oi.price_at_time, oi.discount_amount,
                    o.status, o.user_id, o.delivered_at, o.created_at, o.total_amount, o.final_amount, o.shipping_fee, pv.product_id
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                LEFT JOIN product_variants pv ON pv.id = oi.variant_id
                WHERE oi.id = :order_item_id AND o.user_id = :user_id LIMIT 1 FOR UPDATE");
            $stmt->execute(['order_item_id' => $orderItemId, 'user_id' => $userId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item || !in_array($item['status'], ['delivered', 'completed'], true)) {
                throw new \RuntimeException('Chỉ được tạo yêu cầu sau bán hàng sau khi đơn đã giao thành công.');
            }

            $deadline = $this->calculateDeadline($item, $type);
            if ($deadline < new \DateTimeImmutable('now')) {
                throw new \RuntimeException('Yêu cầu đã quá thời hạn đổi trả/bảo hành theo chính sách.');
            }

            $maxQuantity = max(1, (int)$item['item_quantity']);
            if ($requestedQuantity < 1 || $requestedQuantity > $maxQuantity) {
                throw new \RuntimeException('Số lượng yêu cầu không hợp lệ.');
            }
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(CASE WHEN status = 'rejected' THEN 0 ELSE COALESCE(NULLIF(approved_quantity, 0), requested_quantity) END), 0)
                FROM after_sale_requests WHERE order_item_id = :order_item_id");
            $stmt->execute(['order_item_id' => $orderItemId]);
            $alreadyRequested = (int)$stmt->fetchColumn();
            if ($alreadyRequested + $requestedQuantity > $maxQuantity) {
                throw new \RuntimeException('Số lượng yêu cầu vượt quá số lượng đã mua hoặc đã có yêu cầu trước đó.');
            }

            $refundAmount = in_array($type, self::REFUND_TYPES, true)
                ? $this->calculateRefundAmount($item, $requestedQuantity)
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
        $stmt = $this->db->prepare("SELECT r.*, o.order_code, u.full_name, u.email, COALESCE(oi.product_id, pv.product_id) AS original_product_id,
                (SELECT payment_method FROM payments px WHERE px.order_id=r.order_id ORDER BY px.id DESC LIMIT 1) payment_method,
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

    public function getReplacementVariants(): array {
        $stmt = $this->db->query("SELECT pv.id, pv.product_id, pv.size, pv.color,
                GREATEST(0,pv.stock_quantity-COALESCE(pv.reserved_quantity,0)) stock_quantity, p.name AS product_name
            FROM product_variants pv JOIN product p ON p.id = pv.product_id
            WHERE p.status = 1 ORDER BY p.name, pv.size, pv.color");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(
        int $id,
        string $status,
        string $note,
        int $approvedQuantity = 0,
        bool $restockable = true,
        string $refundTransactionCode = '',
        int $replacementVariantId = 0,
        int $replacementQuantity = 0,
        string $replacementCarrier = '',
        string $replacementTrackingCode = ''
    ): array {
        $status = strtolower(trim($status));
        if (!in_array($status, self::STATUSES, true)) {
            return ['success' => false, 'message' => 'Trạng thái xử lý không hợp lệ.'];
        }

        if ($status === 'refunded') {
            return $this->initiateRefund($id, $note, $refundTransactionCode);
        }

        try {
            $this->db->beginTransaction();
            $submittedApprovedQuantity = $approvedQuantity;
            $stmt = $this->db->prepare("SELECT r.*, oi.quantity AS item_quantity, oi.price_at_time, oi.discount_amount, oi.variant_id,
                    o.order_code, o.status AS order_status, o.delivered_at, o.created_at AS order_created_at,
                    o.total_amount, o.final_amount, o.shipping_fee, COALESCE(oi.product_id, pv.product_id) AS original_product_id
                FROM after_sale_requests r
                JOIN order_items oi ON oi.id = r.order_item_id
                JOIN orders o ON o.id = r.order_id
                LEFT JOIN product_variants pv ON pv.id = oi.variant_id
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
                if ($submittedApprovedQuantity < 1 || $submittedApprovedQuantity > (int)$request['requested_quantity']) {
                    throw new \RuntimeException('Số lượng duyệt phải từ 1 đến số lượng khách yêu cầu.');
                }
                $approvedQuantity = $submittedApprovedQuantity;
                $this->assertQuantityStillAvailable($request, $approvedQuantity);
                $refundAmount = in_array($request['request_type'], self::REFUND_TYPES, true)
                    ? $this->calculateRefundAmount($request, $approvedQuantity)
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
                $this->markOrderRefundPending((int)$request['order_id'], $request['request_type']);
            } elseif ($status === 'received') {
                if ($current !== 'approved' || $approvedQuantity <= 0) {
                    throw new \RuntimeException('Chỉ được xác nhận đã nhận hàng sau khi yêu cầu đã được duyệt.');
                }
                $this->processReceivedProduct($request, $approvedQuantity, $restockable);
                $stmt = $this->db->prepare("UPDATE after_sale_requests SET status = 'received', restockable = :restockable,
                        received_at = NOW(), resolution_note = :note WHERE id = :id");
                $stmt->execute(['restockable' => $restockable ? 1 : 0, 'note' => trim($note), 'id' => $id]);
            } elseif ($status === 'replacement_shipped') {
                if (!in_array($request['request_type'], ['exchange', 'warranty'], true) || $current !== 'received') {
                    throw new \RuntimeException('Chỉ yêu cầu đổi/bảo hành đã nhận lại hàng mới được tạo chuyến giao thay thế.');
                }
                $this->shipReplacement(
                    $request,
                    $replacementVariantId,
                    $replacementQuantity,
                    $replacementCarrier,
                    $replacementTrackingCode
                );
                $stmt = $this->db->prepare("UPDATE after_sale_requests SET status = 'replacement_shipped', resolution_note = :note WHERE id = :id");
                $stmt->execute(['note' => trim($note), 'id' => $id]);
            } elseif ($status === 'completed') {
                if ($request['request_type'] === 'return' && $current !== 'refunded') {
                    throw new \RuntimeException('Yêu cầu đổi trả chỉ hoàn tất sau khi đã hoàn tiền.');
                }
                if (in_array($request['request_type'], ['exchange', 'warranty'], true) && $current !== 'replacement_shipped') {
                    throw new \RuntimeException('Yêu cầu đổi/bảo hành chỉ hoàn tất sau khi đã tạo chuyến giao sản phẩm thay thế.');
                }
                $stmt = $this->db->prepare("UPDATE after_sale_requests SET status = 'completed', completed_at = NOW(), resolution_note = :note WHERE id = :id");
                $stmt->execute(['note' => trim($note), 'id' => $id]);
            } else {
                if ($status === 'rejected') {
                    $this->markOrderRefundCanceled((int)$request['order_id'], (int)$request['id'], (string)$request['request_type']);
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

    /**
     * Tách PayPal ra khỏi DB transaction. PayPal là hệ thống bên ngoài: nếu
     * API đã chấp nhận hoàn tiền thì rollback MySQL không thể đảo ngược nó.
     */
    private function initiateRefund(int $id, string $note, string $manualCode): array {
        $paypalContext = null;
        try {
            $this->db->beginTransaction();
            $request = $this->refundRequestForUpdate($id);
            if (!$request || !in_array((string)$request['request_type'], self::REFUND_TYPES, true)) {
                throw new \RuntimeException('Yêu cầu sau bán hàng không hợp lệ để hoàn tiền.');
            }
            $current = (string)$request['status'];
            if (!$this->canTransition($current, 'refunded', (string)$request['request_type'])) {
                throw new \RuntimeException('Yêu cầu chưa ở bước có thể hoàn tiền.');
            }
            if ($request['request_type'] === 'return' && $current !== 'received') {
                throw new \RuntimeException('Đổi trả phải xác nhận đã nhận lại sản phẩm trước khi hoàn tiền.');
            }
            $amount = max(0, (float)$request['refund_amount']);
            if ($amount <= 0) throw new \RuntimeException('Số tiền hoàn không hợp lệ.');

            $paymentStmt = $this->db->prepare('SELECT * FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $paymentStmt->execute(['order_id' => (int)$request['order_id']]);
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
            if ($payment && $payment['payment_method'] === 'paypal') {
                if (empty($payment['provider_capture_id']) || (float)$payment['provider_exchange_rate'] <= 0) {
                    throw new \RuntimeException('Khoản PayPal thiếu capture hoặc tỷ giá gốc để hoàn tiền an toàn.');
                }
                $reference = 'after-sale-' . (int)$request['id'];
                $existing = $this->db->prepare('SELECT provider_refund_id, status FROM payment_refunds WHERE merchant_reference = :reference LIMIT 1');
                $existing->execute(['reference' => $reference]);
                $existingRefund = $existing->fetch(PDO::FETCH_ASSOC);
                if ($existingRefund) {
                    $this->db->commit();
                    return ['success' => true, 'message' => 'Yêu cầu hoàn tiền PayPal đã được ghi nhận trước đó; không tạo giao dịch trùng.'];
                }
                $this->db->prepare("UPDATE after_sale_requests SET refund_status = 'processing', resolution_note = :note WHERE id = :id")
                    ->execute(['note' => trim($note), 'id' => $id]);
                $paypalContext = [
                    'capture_id' => (string)$payment['provider_capture_id'],
                    'amount' => round($amount / (float)$payment['provider_exchange_rate'], 2),
                    'currency' => strtoupper((string)$payment['provider_currency']),
                    'reference' => $reference,
                ];
                $this->db->commit();
            } else {
                $manualCode = trim($manualCode);
                if ($manualCode === '') throw new \RuntimeException('Vui lòng nhập mã giao dịch hoặc biên nhận hoàn tiền.');
                $this->completeRefundInTransaction($request, $manualCode, $note);
                $this->db->commit();
                return ['success' => true, 'message' => 'Đã ghi nhận hoàn tiền và đồng bộ doanh thu.'];
            }
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $error->getMessage()];
        }

        try {
            $refund = (new PayPalService())->refundCapture(
                $paypalContext['capture_id'], $paypalContext['amount'], $paypalContext['currency'],
                $paypalContext['reference'], 'Hoàn tiền yêu cầu sau bán #' . $id
            );
            $providerId = trim((string)($refund['id'] ?? ''));
            $providerStatus = strtolower((string)($refund['status'] ?? ''));
            if ($providerId === '' || !in_array($providerStatus, ['completed', 'pending'], true)) {
                throw new \RuntimeException('PayPal chưa chấp nhận yêu cầu hoàn tiền.');
            }

            $this->db->beginTransaction();
            $request = $this->refundRequestForUpdate($id);
            if (!$request) throw new \RuntimeException('Không tìm thấy yêu cầu sau bán hàng.');
            if ($providerStatus === 'completed') {
                $this->completeRefundInTransaction($request, $providerId, $note);
            } else {
                $this->recordPendingPayPalRefund($request, $providerId, $paypalContext, $note);
            }
            $this->db->commit();
            return ['success' => true, 'message' => $providerStatus === 'completed'
                ? 'PayPal đã xác nhận hoàn tiền; hệ thống đã đồng bộ đơn hàng.'
                : 'PayPal đang xử lý hoàn tiền. Hệ thống sẽ hoàn tất khi webhook xác nhận.'];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            // Trả request về trạng thái chờ để admin có thể thử lại; không lộ lỗi API ra giao diện khách.
            try {
                $this->db->prepare("UPDATE after_sale_requests SET refund_status = 'pending' WHERE id = :id AND refund_status = 'processing'")
                    ->execute(['id' => $id]);
            } catch (Throwable $ignored) {
            }
            return ['success' => false, 'message' => 'Không thể gửi yêu cầu hoàn tiền PayPal. Vui lòng đối soát lại trước khi thử lại.'];
        }
    }

    private function refundRequestForUpdate(int $id): ?array {
        $stmt = $this->db->prepare("SELECT r.*, oi.quantity AS item_quantity, oi.price_at_time, oi.discount_amount, oi.variant_id,
                o.order_code, o.status AS order_status, o.delivered_at, o.created_at AS order_created_at,
                o.total_amount, o.final_amount, o.shipping_fee, COALESCE(oi.product_id, pv.product_id) AS original_product_id
            FROM after_sale_requests r JOIN order_items oi ON oi.id = r.order_item_id
            JOIN orders o ON o.id = r.order_id LEFT JOIN product_variants pv ON pv.id = oi.variant_id
            WHERE r.id = :id FOR UPDATE");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function completeRefundInTransaction(array $request, string $transactionCode, string $note): void {
        $existing = $this->db->prepare('SELECT merchant_reference FROM payment_refunds WHERE merchant_reference = :reference OR provider_refund_id = :code LIMIT 1');
        $reference = 'after-sale-' . (int)$request['id'];
        $existing->execute(['reference' => $reference, 'code' => trim($transactionCode)]);
        $existingReference = $existing->fetchColumn();
        if ($existingReference !== false) {
            if ($existingReference !== $reference) {
                throw new \RuntimeException('Mã giao dịch hoàn tiền đã được sử dụng cho yêu cầu khác.');
            }
            return;
        }
        $quantity = max(1, (int)$request['approved_quantity']);
        $this->reverseDeliveredSales($request, $quantity);
        $this->markPaymentRefunded((int)$request['order_id'], (int)$request['id'], (float)$request['refund_amount'], $transactionCode);
        $this->db->prepare("UPDATE after_sale_requests SET status = 'refunded', sales_reversed_quantity = :quantity,
                refund_status = 'completed', refund_transaction_code = :code, refund_processed_at = NOW(), resolution_note = :note WHERE id = :id")
            ->execute(['quantity' => $quantity, 'code' => trim($transactionCode), 'note' => trim($note), 'id' => (int)$request['id']]);
        $this->adjustRevenueReports($request, $quantity, (float)$request['refund_amount']);
    }

    private function recordPendingPayPalRefund(array $request, string $providerId, array $context, string $note): void {
        $payment = $this->db->prepare('SELECT id FROM payments WHERE order_id = :order_id ORDER BY id DESC LIMIT 1 FOR UPDATE');
        $payment->execute(['order_id' => (int)$request['order_id']]);
        $paymentId = (int)$payment->fetchColumn();
        if ($paymentId <= 0) throw new \RuntimeException('Không tìm thấy giao dịch thanh toán gốc.');
        $this->db->prepare("INSERT INTO payment_refunds (order_id,payment_id,after_sale_request_id,provider,provider_refund_id,merchant_reference,
                amount_vnd,provider_amount,provider_currency,status) VALUES (:order_id,:payment_id,:request_id,'paypal',:provider_id,:reference,
                :amount_vnd,:provider_amount,:currency,'pending')")
            ->execute(['order_id' => (int)$request['order_id'], 'payment_id' => $paymentId, 'request_id' => (int)$request['id'],
                'provider_id' => $providerId, 'reference' => $context['reference'], 'amount_vnd' => (float)$request['refund_amount'],
                'provider_amount' => $context['amount'], 'currency' => $context['currency']]);
        $this->db->prepare("UPDATE payments SET payment_state = 'refund_pending', refund_status = 'pending', refund_transaction_code = :code WHERE id = :id")
            ->execute(['code' => $providerId, 'id' => $paymentId]);
        $this->db->prepare("UPDATE after_sale_requests SET refund_status = 'pending', refund_transaction_code = :code, resolution_note = :note WHERE id = :id")
            ->execute(['code' => $providerId, 'note' => trim($note), 'id' => (int)$request['id']]);
    }

    private function calculateDeadline(array $item, string $type): \DateTimeImmutable {
        $days = $type === 'warranty' ? 180 : 7;
        $base = $item['delivered_at'] ?: $item['created_at'];
        return (new \DateTimeImmutable((string)$base))->modify('+' . $days . ' days');
    }

    private function calculateRefundAmount(array $request, int $quantity): float {
        $itemQuantity = max(1, (int)($request['item_quantity'] ?? 1));
        $lineGross = max(0, (float)$request['price_at_time'] * $itemQuantity);
        if (array_key_exists('discount_amount', $request) && $request['discount_amount'] !== null) {
            $lineDiscount = min($lineGross, max(0, (float)$request['discount_amount']));
        } else {
            // Đơn cũ chưa lưu phân bổ theo dòng: phân bổ giảm giá toàn đơn
            // theo tỷ trọng giá trị hàng, tuyệt đối không hoàn theo giá trước giảm.
            $orderSubtotal = max(0, (float)($request['total_amount'] ?? 0));
            $orderDiscount = max(0, $orderSubtotal + (float)($request['shipping_fee'] ?? 0) - (float)($request['final_amount'] ?? 0));
            $lineDiscount = $orderSubtotal > 0 ? min($lineGross, $orderDiscount * ($lineGross / $orderSubtotal)) : 0;
        }

        $netUnitPrice = max(0, $lineGross - $lineDiscount) / $itemQuantity;
        return round($netUnitPrice * max(0, $quantity), 2);
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
            'received' => ['replacement_shipped', 'refunded'],
            'replacement_shipped' => ['completed'],
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
            'replacement_shipped' => 'Đã gửi hàng thay thế',
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

    private function shipReplacement(array $request, int $variantId, int $quantity, string $carrier, string $trackingCode): void {
        $approvedQuantity = (int)$request['approved_quantity'];
        if ($variantId <= 0 || $quantity !== $approvedQuantity || $quantity <= 0) {
            throw new \RuntimeException('Phải chọn sản phẩm thay thế và giao đủ đúng số lượng đã duyệt.');
        }
        $carrier = trim($carrier);
        $trackingCode = trim($trackingCode);
        if ($carrier === '' && $trackingCode === '') {
            throw new \RuntimeException('Vui lòng nhập đơn vị vận chuyển hoặc mã vận đơn của chuyến giao thay thế.');
        }

        $stmt = $this->db->prepare("SELECT pv.id, pv.product_id, pv.stock_quantity, pv.reserved_quantity, pv.status AS variant_status, p.name, p.status AS product_status
            FROM product_variants pv JOIN product p ON p.id = pv.product_id
            WHERE pv.id = :id FOR UPDATE");
        $stmt->execute(['id' => $variantId]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$variant || (int)$variant['variant_status'] !== 1 || (int)$variant['product_status'] !== 1 || (int)$variant['product_id'] !== (int)$request['original_product_id']) {
            throw new \RuntimeException('Variant thay thế không hợp lệ hoặc không thuộc sản phẩm ban đầu.');
        }

        $processed = (int)$request['replacement_processed_quantity'];
        $delta = $quantity - $processed;
        if ($delta <= 0) {
            return;
        }
        $available=max(0,(int)$variant['stock_quantity']-(int)($variant['reserved_quantity']??0));
        if ($available < $delta) {
            throw new \RuntimeException('Sản phẩm thay thế không đủ tồn kho khả dụng. Hiện còn ' . $available . '.');
        }

        $reason = 'Xuất hàng thay thế cho yêu cầu sau bán #' . (int)$request['id'];
        $stmt = $this->db->prepare('INSERT INTO inventory_logs (variant_id, quantity_changed, reason) VALUES (:variant_id, :quantity_changed, :reason)');
        $stmt->execute(['variant_id' => $variantId, 'quantity_changed' => -$delta, 'reason' => $reason]);
        if (!$this->triggerExists('trg_after_insert_inventory_log')) {
            $stmt = $this->db->prepare('UPDATE product_variants SET stock_quantity = stock_quantity - :quantity WHERE id = :variant_id');
            $stmt->execute(['quantity' => $delta, 'variant_id' => $variantId]);
        }

        $stmt = $this->db->prepare('UPDATE after_sale_requests SET replacement_variant_id = :variant_id,
            replacement_quantity = :quantity, replacement_processed_quantity = :processed,
            replacement_shipping_carrier = :carrier, replacement_tracking_code = :tracking_code,
            replacement_shipped_at = NOW() WHERE id = :id');
        $stmt->execute([
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'processed' => $quantity,
            'carrier' => $carrier ?: null,
            'tracking_code' => $trackingCode ?: null,
            'id' => (int)$request['id']
        ]);
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

    private function markOrderRefundPending(int $orderId, string $type): void {
        if (!in_array($type, self::REFUND_TYPES, true)) {
            return;
        }
        $stmt = $this->db->prepare("UPDATE payments SET refund_status = 'pending',
                payment_state = CASE WHEN payment_state IN ('paid', 'partially_refunded') THEN 'refund_pending' ELSE payment_state END
            WHERE order_id = :order_id");
        $stmt->execute(['order_id' => $orderId]);
    }

    private function markOrderRefundCanceled(int $orderId, int $requestId, string $type): void {
        if (!in_array($type, self::REFUND_TYPES, true)) {
            return;
        }
        $pending = $this->db->prepare("SELECT COUNT(*) FROM after_sale_requests
            WHERE order_id = :order_id AND id <> :request_id AND request_type IN ('return','refund')
              AND status IN ('approved','received')");
        $pending->execute(['order_id' => $orderId, 'request_id' => $requestId]);
        if ((int)$pending->fetchColumn() > 0) {
            return;
        }
        $stmt = $this->db->prepare("UPDATE payments SET
                refund_status = CASE WHEN refunded_amount > 0 THEN 'completed' ELSE 'not_requested' END,
                payment_state = CASE WHEN refunded_amount > 0 THEN 'partially_refunded' ELSE 'paid' END
            WHERE order_id = :order_id AND refund_status = 'pending'");
        $stmt->execute(['order_id' => $orderId]);
    }

    private function markPaymentRefunded(int $orderId, int $requestId, float $amount, string $transactionCode): void {
        $pendingStmt = $this->db->prepare("SELECT COUNT(*) FROM after_sale_requests
            WHERE order_id = :order_id AND id <> :request_id AND request_type IN ('return','refund')
              AND status IN ('approved','received')");
        $pendingStmt->execute(['order_id' => $orderId, 'request_id' => $requestId]);
        $hasOtherPendingRefund = (int)$pendingStmt->fetchColumn() > 0;

        $stmt = $this->db->prepare('SELECT p.*, o.final_amount FROM payments p JOIN orders o ON o.id = p.order_id
            WHERE p.order_id = :order_id ORDER BY p.id DESC LIMIT 1 FOR UPDATE');
        $stmt->execute(['order_id' => $orderId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$payment) {
            $orderStmt = $this->db->prepare('SELECT final_amount FROM orders WHERE id = :order_id FOR UPDATE');
            $orderStmt->execute(['order_id' => $orderId]);
            $finalAmount = max(0, (float)$orderStmt->fetchColumn());
            $cumulative = min($finalAmount, max(0, $amount));
            $fullyRefunded = $cumulative >= $finalAmount - 0.01;
            $stmt = $this->db->prepare("INSERT INTO payments (order_id, payment_method, payment_status, payment_state, refund_status, refund_transaction_code, refunded_amount, refunded_at)
                VALUES (:order_id, 'manual', :payment_status, :payment_state, :refund_status, :transaction_code, :amount, NOW())");
            $stmt->execute([
                'order_id' => $orderId,
                'payment_status' => $fullyRefunded && !$hasOtherPendingRefund ? 2 : 1,
                'payment_state' => $hasOtherPendingRefund ? 'refund_pending' : ($fullyRefunded ? 'refunded' : 'partially_refunded'),
                'refund_status' => $hasOtherPendingRefund ? 'pending' : 'completed',
                'transaction_code' => $transactionCode,
                'amount' => $cumulative
            ]);
            $paymentId = (int)$this->db->lastInsertId();
            $this->insertRefundLedger($orderId, $paymentId, $requestId, $amount, $transactionCode, null);
            return;
        }

        $cumulative = min((float)$payment['final_amount'], max(0, (float)$payment['refunded_amount']) + max(0, $amount));
        $fullyRefunded = $cumulative >= (float)$payment['final_amount'] - 0.01;
        $stmt = $this->db->prepare("UPDATE payments SET payment_status = :payment_status, payment_state = :payment_state, refund_status = :refund_status,
                refund_transaction_code = :transaction_code, refunded_amount = :amount, refunded_at = NOW()
            WHERE id = :id");
        $stmt->execute([
            'payment_status' => $fullyRefunded && !$hasOtherPendingRefund ? 2 : 1,
            'payment_state' => $hasOtherPendingRefund ? 'refund_pending' : ($fullyRefunded ? 'refunded' : 'partially_refunded'),
            'refund_status' => $hasOtherPendingRefund ? 'pending' : 'completed',
            'transaction_code' => $transactionCode,
            'amount' => $cumulative,
            'id' => (int)$payment['id']
        ]);
        $this->insertRefundLedger($orderId, (int)$payment['id'], $requestId, $amount, $transactionCode, $payment);
    }

    private function insertRefundLedger(
        int $orderId,
        int $paymentId,
        int $requestId,
        float $amount,
        string $transactionCode,
        ?array $payment
    ): void {
        $provider = ($payment['payment_method'] ?? '') === 'paypal' ? 'paypal' : 'manual';
        $rate = (float)($payment['provider_exchange_rate'] ?? 0);
        $providerAmount = $provider === 'paypal' && $rate > 0 ? round($amount / $rate, 2) : null;
        $currency = $provider === 'paypal' ? strtoupper((string)($payment['provider_currency'] ?? 'USD')) : null;
        $stmt = $this->db->prepare("INSERT INTO payment_refunds
            (order_id,payment_id,after_sale_request_id,provider,provider_refund_id,merchant_reference,
             amount_vnd,provider_amount,provider_currency,status,refunded_at)
            VALUES (:order_id,:payment_id,:request_id,:provider,:provider_refund_id,:merchant_reference,
             :amount_vnd,:provider_amount,:currency,'completed',NOW())");
        $stmt->execute([
            'order_id' => $orderId, 'payment_id' => $paymentId, 'request_id' => $requestId,
            'provider' => $provider, 'provider_refund_id' => trim($transactionCode),
            'merchant_reference' => 'after-sale-' . $requestId, 'amount_vnd' => max(0, $amount),
            'provider_amount' => $providerAmount, 'currency' => $currency,
        ]);
    }

    private function adjustRevenueReports(array $request, int $quantity, float $refundAmount): void {
        $salesReportDate = date('Y-m-d', strtotime((string)($request['delivered_at'] ?: $request['order_created_at'])));
        $refundReportDate = date('Y-m-d');
        if ($this->tableHasColumn('product_sales_reports', 'quantity_sold')) {
            $stmt = $this->db->prepare("UPDATE product_sales_reports
                SET quantity_sold = GREATEST(0, quantity_sold - :quantity),
                    total_revenue = GREATEST(0, total_revenue - :amount)
                WHERE report_date = :report_date AND variant_id = :variant_id");
            $stmt->execute(['quantity' => $quantity, 'amount' => $refundAmount, 'report_date' => $salesReportDate, 'variant_id' => (int)$request['variant_id']]);
        }

        if ($this->tableHasColumn('daily_revenue_reports', 'refunded_amount')) {
            $stmt = $this->db->prepare("INSERT INTO daily_revenue_reports (report_date, total_orders, gross_revenue, total_discount, net_revenue, refunded_amount)
                VALUES (:report_date, 0, 0, 0, 0, :amount)
                ON DUPLICATE KEY UPDATE refunded_amount = refunded_amount + VALUES(refunded_amount), net_revenue = net_revenue - VALUES(refunded_amount)");
            $stmt->execute(['report_date' => $refundReportDate, 'amount' => $refundAmount]);
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
