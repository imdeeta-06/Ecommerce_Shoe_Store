<?php

namespace App\Services;

use App\Core\App;
use App\Models\Database;
use PDO;

class OrderNotificationService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function queueOrderCreated(int $orderId): bool {
        $order = $this->getOrder($orderId);
        if (!$order) {
            return false;
        }

        $recipient = $this->recipient($order);
        if ($recipient === '') {
            return false;
        }

        $orderCode = (string)$order['order_code'];
        return $this->queue(
            $order,
            'order_created',
            'PaceUp đã tiếp nhận đơn hàng ' . $orderCode,
            $this->buildCreatedHtml($order),
            $recipient
        );
    }

    public function queueStatusChanged(int $orderId, string $status): bool {
        $order = $this->getOrder($orderId);
        if (!$order) {
            return false;
        }

        $recipient = $this->recipient($order);
        if ($recipient === '') {
            return false;
        }

        $labels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'preparing' => 'Đang chuẩn bị',
            'shipping' => 'Đang giao',
            'delivered' => 'Giao thành công',
            'completed' => 'Hoàn thành',
            'canceled' => 'Đã hủy'
        ];
        $label = $labels[$status] ?? $status;
        return $this->queue(
            $order,
            'status_' . $status,
            'Cập nhật đơn hàng ' . $order['order_code'] . ': ' . $label,
            $this->buildStatusHtml($order, $label),
            $recipient
        );
    }

    public function process(int $limit = 50): array {
        if (!MailService::isConfigured()) {
            return [
                'success' => false,
                'sent' => 0,
                'failed' => 0,
                'message' => 'SMTP chưa được cấu hình. Hàng đợi thông báo đơn hàng vẫn được giữ nguyên.'
            ];
        }

        $stmt = $this->db->prepare("SELECT * FROM order_notifications
            WHERE status IN ('pending', 'failed')
              AND attempt_count < 3
              AND (next_attempt_at IS NULL OR next_attempt_at <= NOW())
            ORDER BY created_at ASC
            LIMIT :limit");
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sent = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                MailService::sendHtml(
                    (string)$notification['recipient_email'],
                    (string)$notification['subject'],
                    (string)$notification['body_html']
                );
                if ($this->markSent((int)$notification['id'])) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                $this->markFailed((int)$notification['id'], $e->getMessage());
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'sent' => $sent,
            'failed' => $failed,
            'message' => "Đã xử lý {$sent} thông báo đơn hàng, lỗi {$failed} thông báo."
        ];
    }

    public function getQueue(int $limit = 100): array {
        $stmt = $this->db->prepare("SELECT n.*, o.order_code
            FROM order_notifications n
            LEFT JOIN orders o ON o.id = n.order_id
            ORDER BY n.created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function queue(array $order, string $type, string $subject, string $body, string $recipient): bool {
        $stmt = $this->db->prepare("INSERT IGNORE INTO order_notifications
            (order_id, user_id, recipient_email, notification_type, subject, body_html, status)
            VALUES (:order_id, :user_id, :recipient_email, :notification_type, :subject, :body_html, 'pending')");
        $stmt->execute([
            'order_id' => (int)$order['id'],
            'user_id' => !empty($order['user_id']) ? (int)$order['user_id'] : null,
            'recipient_email' => $recipient,
            'notification_type' => $type,
            'subject' => $subject,
            'body_html' => $body
        ]);
        return $stmt->rowCount() === 1;
    }

    private function getOrder(int $orderId): ?array {
        $stmt = $this->db->prepare("SELECT o.*, u.email AS user_email, u.full_name AS user_name
            FROM orders o LEFT JOIN user u ON u.id = o.user_id
            WHERE o.id = :id LIMIT 1");
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return null;
        }

        $items = $this->db->prepare("SELECT product_name_snapshot, variant_size_snapshot,
                variant_color_snapshot, quantity, price_at_time
            FROM order_items WHERE order_id = :order_id ORDER BY id ASC");
        $items->execute(['order_id' => $orderId]);
        $order['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
        return $order;
    }

    private function recipient(array $order): string {
        $email = trim((string)($order['shipping_email'] ?? ''));
        if ($email === '') {
            $email = trim((string)($order['user_email'] ?? ''));
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    private function buildCreatedHtml(array $order): string {
        $name = htmlspecialchars((string)($order['shipping_name'] ?? $order['user_name'] ?? 'bạn'), ENT_QUOTES, 'UTF-8');
        $rows = $this->buildItemRows($order['items'] ?? []);
        return $this->layout(
            'Chào ' . $name . ', PaceUp đã tiếp nhận đơn hàng <strong>' . htmlspecialchars((string)$order['order_code'], ENT_QUOTES, 'UTF-8') . '</strong>.',
            $rows,
            'Trạng thái hiện tại: Chờ xác nhận. Nhân viên PaceUp sẽ kiểm tra tồn kho và liên hệ khi đơn được xác nhận.',
            $order
        );
    }

    private function buildStatusHtml(array $order, string $label): string {
        $rows = $this->buildItemRows($order['items'] ?? []);
        return $this->layout(
            'Đơn hàng <strong>' . htmlspecialchars((string)$order['order_code'], ENT_QUOTES, 'UTF-8') . '</strong> vừa được cập nhật.',
            $rows,
            'Trạng thái mới: <strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong>.',
            $order
        );
    }

    private function layout(string $intro, string $rows, string $statusText, array $order): string {
        $trackingUrl = App::url('tracking?order_code=' . urlencode((string)$order['order_code']) . '&phone=' . urlencode((string)$order['shipping_phone']));
        return '<!doctype html><html lang="vi"><body style="font-family:Arial,sans-serif;color:#111;line-height:1.6;">'
            . '<h2>PaceUp</h2><p>' . $intro . '</p><p>' . $statusText . '</p>'
            . '<table style="width:100%;max-width:680px;border-collapse:collapse;">' . $rows . '</table>'
            . '<p style="margin-top:24px;"><strong>Tổng tiền:</strong> ' . number_format((float)$order['final_amount'], 0, ',', '.') . ' ₫</p>'
            . '<p><a href="' . htmlspecialchars($trackingUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#111;color:#fff;padding:12px 20px;text-decoration:none;">Tra cứu đơn hàng</a></p>'
            . '<p style="font-size:12px;color:#777;">Đây là email giao dịch liên quan đến đơn hàng của bạn.</p></body></html>';
    }

    private function buildItemRows(array $items): string {
        $rows = '';
        foreach ($items as $item) {
            $name = htmlspecialchars((string)($item['product_name_snapshot'] ?? 'Sản phẩm'), ENT_QUOTES, 'UTF-8');
            $size = htmlspecialchars((string)($item['variant_size_snapshot'] ?? 'Mặc định'), ENT_QUOTES, 'UTF-8');
            $color = htmlspecialchars((string)($item['variant_color_snapshot'] ?? 'Mặc định'), ENT_QUOTES, 'UTF-8');
            $rows .= '<tr><td style="padding:10px 0;border-bottom:1px solid #eee;">' . $name . ' · Size ' . $size . ' · Màu ' . $color . '</td>'
                . '<td style="padding:10px 0;border-bottom:1px solid #eee;text-align:right;">SL ' . (int)$item['quantity'] . ' · ' . number_format((float)$item['price_at_time'] * (int)$item['quantity'], 0, ',', '.') . ' ₫</td></tr>';
        }
        return $rows;
    }

    private function markSent(int $id): bool {
        $stmt = $this->db->prepare("UPDATE order_notifications SET status = 'sent', sent_at = NOW(),
                attempt_count = attempt_count + 1, next_attempt_at = NULL, last_error = NULL
            WHERE id = :id AND status IN ('pending', 'failed')");
        return $stmt->execute(['id' => $id]) && $stmt->rowCount() === 1;
    }

    private function markFailed(int $id, string $error): bool {
        $stmt = $this->db->prepare("UPDATE order_notifications SET status = 'failed',
                attempt_count = attempt_count + 1, next_attempt_at = DATE_ADD(NOW(), INTERVAL 1 HOUR),
                last_error = :error WHERE id = :id AND attempt_count < 3");
        return $stmt->execute(['id' => $id, 'error' => trim($error)]);
    }
}
