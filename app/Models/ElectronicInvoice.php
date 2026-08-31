<?php

namespace App\Models;

use PDO;
use Throwable;

class ElectronicInvoice extends BaseModel {
    public function listForUser(int $userId): array {
        $stmt = $this->db->prepare("SELECT i.id,i.invoice_series,i.invoice_number,i.invoice_type,i.status,
                i.issued_at,i.total_amount,o.order_code
            FROM electronic_invoices i
            JOIN orders o ON o.id=i.order_id
            WHERE o.user_id=:user_id ORDER BY i.issued_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listForAdmin(string $month = ''): array {
        $sql = "SELECT i.*,o.order_code,o.shipping_email
            FROM electronic_invoices i JOIN orders o ON o.id=i.order_id";
        $params = [];
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $sql .= " WHERE DATE_FORMAT(i.issued_at,'%Y-%m')=:month";
            $params['month'] = $month;
        }
        $sql .= ' ORDER BY i.issued_at DESC,i.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function report(string $month): array {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
        $stmt = $this->db->prepare("SELECT COUNT(*) invoice_count,
                COALESCE(SUM(CASE WHEN status<>'canceled' THEN total_amount ELSE 0 END),0) total_amount,
                SUM(status='canceled') canceled_count
            FROM electronic_invoices WHERE DATE_FORMAT(issued_at,'%Y-%m')=:month");
        $stmt->execute(['month' => $month]);
        return array_merge(['month' => $month], $stmt->fetch(PDO::FETCH_ASSOC) ?: []);
    }

    public function issue(int $orderId, array $buyer, int $adminId): array {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT o.*,p.payment_state FROM orders o
                LEFT JOIN payments p ON p.order_id=o.id
                WHERE o.id=:id ORDER BY p.id DESC LIMIT 1 FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order || !in_array($order['status'], ['delivered','completed'], true)
                || ($order['payment_state'] ?? '') !== 'paid') {
                throw new \RuntimeException('Chỉ phát hành hóa đơn bán hàng cho đơn đã giao và đã thanh toán.');
            }
            $exists = $this->db->prepare("SELECT id FROM electronic_invoices
                WHERE order_id=:id AND invoice_type='original' LIMIT 1");
            $exists->execute(['id' => $orderId]);
            if ($exists->fetchColumn()) throw new \RuntimeException('Đơn hàng đã có hóa đơn bán hàng.');

            $series = $this->salesInvoiceSeries();
            $number = $this->nextNumber($series);
            $insert = $this->db->prepare("INSERT INTO electronic_invoices
                (order_id,invoice_type,invoice_series,invoice_number,status,buyer_name,buyer_tax_code,
                 buyer_address,total_amount,created_by)
                VALUES (:order_id,'original',:series,:number,'issued',:buyer_name,:buyer_tax_code,
                 :buyer_address,:total,:admin)");
            $insert->execute([
                'order_id' => $orderId,
                'series' => $series,
                'number' => $number,
                'buyer_name' => trim((string)($buyer['buyer_name'] ?? '')) ?: $order['shipping_name'],
                'buyer_tax_code' => trim((string)($buyer['buyer_tax_code'] ?? '')) ?: null,
                'buyer_address' => trim((string)($buyer['buyer_address'] ?? '')) ?: $order['shipping_address'],
                'total' => $order['final_amount'],
                'admin' => $adminId,
            ]);
            $invoiceId = (int)$this->db->lastInsertId();
            $this->createInvoiceItems($invoiceId, $order);
            $this->event($invoiceId, 'issued', 'Phát hành hóa đơn bán hàng', $adminId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Đã phát hành hóa đơn bán hàng.', 'id' => $invoiceId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function adjust(int $invoiceId, float $totalDelta, string $reason, int $adminId): array {
        try {
            if (trim($reason) === '' || abs($totalDelta) < 0.01) {
                throw new \RuntimeException('Vui lòng nhập lý do và tổng tiền điều chỉnh.');
            }
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT * FROM electronic_invoices
                WHERE id=:id AND invoice_type='original' FOR UPDATE");
            $stmt->execute(['id' => $invoiceId]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$original || $original['status'] === 'canceled') {
                throw new \RuntimeException('Hóa đơn gốc không còn cho phép điều chỉnh.');
            }
            $sum = $this->db->prepare("SELECT COALESCE(SUM(total_amount),0)
                FROM electronic_invoices WHERE original_invoice_id=:id AND status<>'canceled'");
            $sum->execute(['id' => $invoiceId]);
            $remainingTotal = (float)$original['total_amount'] + (float)$sum->fetchColumn() + $totalDelta;
            if ($remainingTotal < -0.01) {
                throw new \RuntimeException('Điều chỉnh âm vượt quá tổng tiền còn lại của hóa đơn gốc.');
            }

            $series = $this->salesInvoiceSeries();
            $number = $this->nextNumber($series);
            $insert = $this->db->prepare("INSERT INTO electronic_invoices
                (order_id,original_invoice_id,invoice_type,invoice_series,invoice_number,status,
                 buyer_name,buyer_tax_code,buyer_address,total_amount,adjustment_reason,created_by)
                VALUES (:order_id,:original_id,'adjustment',:series,:number,'issued',
                 :buyer_name,:buyer_tax_code,:buyer_address,:total,:reason,:admin)");
            $insert->execute([
                'order_id' => $original['order_id'],
                'original_id' => $invoiceId,
                'series' => $series,
                'number' => $number,
                'buyer_name' => $original['buyer_name'],
                'buyer_tax_code' => $original['buyer_tax_code'],
                'buyer_address' => $original['buyer_address'],
                'total' => $totalDelta,
                'reason' => trim($reason),
                'admin' => $adminId,
            ]);
            $adjustmentId = (int)$this->db->lastInsertId();
            $this->db->prepare("INSERT INTO electronic_invoice_items
                (invoice_id,item_name,unit_name,quantity,unit_price,discount_amount,total_amount)
                VALUES (:invoice_id,'Điều chỉnh tổng tiền hóa đơn','Lần',1,:unit_price,0,:total)")
                ->execute(['invoice_id' => $adjustmentId, 'unit_price' => $totalDelta, 'total' => $totalDelta]);
            $this->db->prepare("UPDATE electronic_invoices SET status='adjusted' WHERE id=:id")
                ->execute(['id' => $invoiceId]);
            $this->event($invoiceId, 'adjusted', trim($reason), $adminId);
            $this->event($adjustmentId, 'issued', 'Hóa đơn điều chỉnh cho #' . $invoiceId, $adminId);
            $this->db->commit();
            return ['success' => true, 'message' => 'Đã lập hóa đơn bán hàng điều chỉnh.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function cancel(int $invoiceId, string $reason, int $adminId): array {
        if (trim($reason) === '') return ['success' => false, 'message' => 'Phải nhập lý do hủy hóa đơn.'];
        try {
            $this->db->beginTransaction();
            $find = $this->db->prepare('SELECT * FROM electronic_invoices WHERE id=:id FOR UPDATE');
            $find->execute(['id' => $invoiceId]);
            $invoice = $find->fetch(PDO::FETCH_ASSOC);
            if (!$invoice || $invoice['status'] === 'canceled') {
                throw new \RuntimeException('Hóa đơn không còn cho phép hủy.');
            }
            $cleanReason = trim($reason);
            $this->db->prepare("UPDATE electronic_invoices
                SET status='canceled',canceled_at=NOW(),adjustment_reason=:reason WHERE id=:id")
                ->execute(['reason' => $cleanReason, 'id' => $invoiceId]);
            $this->event($invoiceId, 'canceled', $cleanReason, $adminId);

            if ($invoice['invoice_type'] === 'original') {
                $linked = $this->db->prepare("SELECT id FROM electronic_invoices
                    WHERE original_invoice_id=:id AND status<>'canceled' FOR UPDATE");
                $linked->execute(['id' => $invoiceId]);
                foreach ($linked->fetchAll(PDO::FETCH_COLUMN) as $linkedId) {
                    $this->db->prepare("UPDATE electronic_invoices
                        SET status='canceled',canceled_at=NOW(),adjustment_reason=:reason WHERE id=:id")
                        ->execute(['reason' => 'Hủy theo hóa đơn gốc: ' . $cleanReason, 'id' => (int)$linkedId]);
                    $this->event((int)$linkedId, 'canceled', 'Hủy theo hóa đơn gốc: ' . $cleanReason, $adminId);
                }
            } elseif (!empty($invoice['original_invoice_id'])) {
                $active = $this->db->prepare("SELECT COUNT(*) FROM electronic_invoices
                    WHERE original_invoice_id=:id AND status<>'canceled'");
                $active->execute(['id' => (int)$invoice['original_invoice_id']]);
                if ((int)$active->fetchColumn() === 0) {
                    $this->db->prepare("UPDATE electronic_invoices SET status='issued'
                        WHERE id=:id AND status='adjusted'")
                        ->execute(['id' => (int)$invoice['original_invoice_id']]);
                }
            }
            $this->db->commit();
            return ['success' => true, 'message' => 'Đã hủy hóa đơn và lưu lịch sử.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getForViewer(int $id, int $userId, bool $isAdmin): ?array {
        $stmt = $this->db->prepare("SELECT i.*, o.order_code, o.user_id, o.shipping_email,
            (SELECT payment_method FROM payments p WHERE p.order_id=o.id ORDER BY p.id DESC LIMIT 1) as payment_method
            FROM electronic_invoices i JOIN orders o ON o.id=i.order_id WHERE i.id=:id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (!$isAdmin && (int)$row['user_id'] !== $userId)) return null;
        $items = $this->db->prepare('SELECT * FROM electronic_invoice_items WHERE invoice_id=:id ORDER BY id');
        $items->execute(['id' => $id]);
        $row['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
        if (!$row['items'] && $row['invoice_type'] === 'original') {
            $order = $this->db->prepare('SELECT * FROM orders WHERE id=:id');
            $order->execute(['id' => (int)$row['order_id']]);
            $orderRow = $order->fetch(PDO::FETCH_ASSOC);
            if ($orderRow) {
                $this->createInvoiceItems($id, $orderRow);
                $items->execute(['id' => $id]);
                $row['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        return $row;
    }

    private function createInvoiceItems(int $invoiceId, array $order): void {
        $stmt = $this->db->prepare("SELECT oi.*,p.unit_name FROM order_items oi
            LEFT JOIN product p ON p.id=oi.product_id WHERE oi.order_id=:order_id ORDER BY oi.id");
        $stmt->execute(['order_id' => (int)$order['id']]);
        $insert = $this->db->prepare("INSERT INTO electronic_invoice_items
            (invoice_id,order_item_id,item_name,variant_description,unit_name,quantity,unit_price,
             discount_amount,total_amount)
            VALUES (:invoice_id,:order_item_id,:item_name,:variant,:unit_name,:quantity,:unit_price,
             :discount,:total)");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $gross = (float)$item['price_at_time'] * (int)$item['quantity'];
            $discount = max(0, (float)($item['discount_amount'] ?? 0));
            $insert->execute([
                'invoice_id' => $invoiceId,
                'order_item_id' => (int)$item['id'],
                'item_name' => $item['product_name_snapshot'],
                'variant' => trim(($item['variant_size_snapshot'] ?? '') . ' / ' . ($item['variant_color_snapshot'] ?? ''), ' /'),
                'unit_name' => trim((string)($item['unit_name_snapshot'] ?? $item['unit_name'] ?? 'Cái')) ?: 'Cái',
                'quantity' => (int)$item['quantity'],
                'unit_price' => $item['price_at_time'],
                'discount' => $discount,
                'total' => max(0, $gross - $discount),
            ]);
        }
        if ((float)$order['shipping_fee'] > 0) {
            $insert->execute([
                'invoice_id' => $invoiceId,
                'order_item_id' => null,
                'item_name' => 'Phí giao hàng',
                'variant' => null,
                'unit_name' => 'Lần',
                'quantity' => 1,
                'unit_price' => $order['shipping_fee'],
                'discount' => 0,
                'total' => $order['shipping_fee'],
            ]);
        }
    }

    private function salesInvoiceSeries(): string {
        $store = require __DIR__ . '/../../config/store.php';
        $suffix = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)($store['invoice']['series_suffix'] ?? 'LI')));
        $suffix = substr($suffix ?: 'LI', 0, 2);
        // Mẫu số 2: hóa đơn bán hàng; C: có mã; D: loại hóa đơn bán hàng.
        return '2C' . date('y') . 'D' . str_pad($suffix, 2, 'X');
    }

    private function nextNumber(string $series): int {
        $stmt = $this->db->prepare('INSERT INTO document_sequences(series,current_number)
            VALUES(:series,LAST_INSERT_ID(1))
            ON DUPLICATE KEY UPDATE current_number=LAST_INSERT_ID(current_number+1)');
        $stmt->execute(['series' => $series]);
        return (int)$this->db->lastInsertId();
    }

    private function event(int $invoiceId, string $type, string $reason, int $adminId): void {
        $stmt = $this->db->prepare('INSERT INTO electronic_invoice_events
            (invoice_id,event_type,reason,changed_by) VALUES(?,?,?,?)');
        $stmt->execute([$invoiceId, $type, $reason, $adminId ?: null]);
    }
}
