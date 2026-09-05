<?php

namespace App\Models;

use PDO;

class Analytics extends BaseModel {
    private const EVENTS = ['page_view', 'product_view', 'add_to_cart', 'checkout_started', 'purchase'];

    public function record(array $data, ?int $userId, string $sessionId): bool {
        $event = trim((string)($data['event_type'] ?? ''));
        if (!in_array($event, self::EVENTS, true) || empty($data['consent'])) {
            return false;
        }
        $productId = !empty($data['product_id']) ? (int)$data['product_id'] : null;
        $orderId = !empty($data['order_id']) ? (int)$data['order_id'] : null;
        if (in_array($event, ['checkout_started', 'purchase'], true) && !$userId) {
            return false;
        }
        if ($event === 'purchase') {
            $verify = $this->db->prepare("SELECT 1 FROM orders o JOIN payments p ON p.order_id=o.id
                WHERE o.id=:order_id AND o.user_id=:user_id AND o.status<>'canceled' AND p.payment_state='paid' LIMIT 1");
            $verify->execute(['order_id' => $orderId, 'user_id' => $userId]);
            if (!$orderId || !$verify->fetchColumn()) return false;
        }
        if (in_array($event, ['product_view', 'add_to_cart'], true)) {
            $verify = $this->db->prepare('SELECT 1 FROM product WHERE id = :product_id AND status = 1 LIMIT 1');
            $verify->execute(['product_id' => $productId]);
            if (!$productId || !$verify->fetchColumn()) return false;
        }
        $path = parse_url((string)($data['page_path'] ?? ''), PHP_URL_PATH) ?: '/';
        $stmt = $this->db->prepare('INSERT IGNORE INTO analytics_events
            (anonymous_session, user_id, event_type, page_path, product_id, order_id, source, medium, campaign)
            VALUES (:session, :user_id, :event_type, :page_path, :product_id, :order_id, :source, :medium, :campaign)');
        $stmt->execute([
            'session' => hash('sha256', $sessionId),
            'user_id' => $userId,
            'event_type' => $event,
            'page_path' => substr($path, 0, 500),
            'product_id' => $productId,
            'order_id' => $orderId,
            'source' => substr(trim((string)($data['source'] ?? '')), 0, 100) ?: null,
            'medium' => substr(trim((string)($data['medium'] ?? '')), 0, 100) ?: null,
            'campaign' => substr(trim((string)($data['campaign'] ?? '')), 0, 150) ?: null,
        ]);
        return $stmt->rowCount() === 1;
    }

    public function summary(int $days = 30): array {
        $days = max(1, min(365, $days));
        $stmt = $this->db->prepare("SELECT event_type, COUNT(*) total, COUNT(DISTINCT anonymous_session) sessions
            FROM analytics_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY) GROUP BY event_type");
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $events = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $events[$row['event_type']] = $row;
        $totalStmt = $this->db->prepare('SELECT COUNT(DISTINCT anonymous_session) FROM analytics_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)');
        $totalStmt->bindValue(':days', $days, PDO::PARAM_INT);
        $totalStmt->execute();
        $totalSessions = (int)$totalStmt->fetchColumn();

        $campaignStmt = $this->db->prepare("SELECT COALESCE(NULLIF(campaign,''),'(không gắn chiến dịch)') campaign,
            COUNT(DISTINCT anonymous_session) sessions,
            COUNT(DISTINCT CASE WHEN event_type = 'checkout_started' THEN anonymous_session END) checkouts,
            COUNT(DISTINCT CASE WHEN event_type = 'purchase' THEN anonymous_session END) purchases
            FROM analytics_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY campaign ORDER BY purchases DESC, sessions DESC LIMIT 20");
        $campaignStmt->bindValue(':days',$days,PDO::PARAM_INT);
        $campaignStmt->execute();
        $campaigns=$campaignStmt->fetchAll(PDO::FETCH_ASSOC);
        return ['events' => $events, 'campaigns' => $campaigns, 'total_sessions' => $totalSessions];
    }
}
