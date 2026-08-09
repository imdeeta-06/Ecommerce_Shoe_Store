<?php

namespace App\Models;

use PDO;

class SupportTicket extends BaseModel {
    private const STATUSES = ['pending', 'in_progress', 'resolved', 'closed'];

    public function createTicket(array $data): int {
        $ticketCode = $data['ticket_code'] ?? $this->generateTicketCode();
        $id = $this->insert('support_tickets', [
            'ticket_code' => $ticketCode,
            'user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            'name' => trim((string)$data['name']),
            'email' => trim((string)$data['email']),
            'phone' => trim((string)($data['phone'] ?? '')) ?: null,
            'subject' => trim((string)$data['subject']),
            'message' => trim((string)$data['message']),
            'status' => 'pending',
            'auto_reply_status' => 'pending',
            'auto_reply_attempts' => 0
        ]);

        return (int)$id;
    }

    public function getTicketById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM support_tickets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        return $ticket ?: null;
    }

    public function getAdminTickets(string $status = '', int $limit = 100): array {
        $sql = 'SELECT * FROM support_tickets';
        $params = [];
        if (in_array($status, self::STATUSES, true)) {
            $sql .= ' WHERE status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status): bool {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE support_tickets SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function getPendingAutoReplies(int $limit = 50): array {
        $stmt = $this->db->prepare("SELECT * FROM support_tickets
            WHERE auto_reply_status IN ('pending', 'failed')
              AND auto_reply_attempts < 3
              AND email <> ''
              AND (auto_reply_next_attempt_at IS NULL OR auto_reply_next_attempt_at <= NOW())
            ORDER BY created_at ASC
            LIMIT :limit");
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAutoReplySent(int $id): bool {
        $stmt = $this->db->prepare("UPDATE support_tickets
            SET auto_reply_status = 'sent', auto_reply_sent_at = NOW(),
                auto_reply_attempts = auto_reply_attempts + 1,
                auto_reply_next_attempt_at = NULL, auto_reply_last_error = NULL
            WHERE id = :id AND auto_reply_status IN ('pending', 'failed')");
        return $stmt->execute(['id' => $id]) && $stmt->rowCount() === 1;
    }

    public function markAutoReplyFailed(int $id, string $error): bool {
        $stmt = $this->db->prepare("UPDATE support_tickets
            SET auto_reply_status = 'failed', auto_reply_attempts = auto_reply_attempts + 1,
                auto_reply_next_attempt_at = DATE_ADD(NOW(), INTERVAL 1 HOUR),
                auto_reply_last_error = :error
            WHERE id = :id AND auto_reply_attempts < 3");
        return $stmt->execute(['id' => $id, 'error' => trim($error)]) && $stmt->rowCount() === 1;
    }

    private function generateTicketCode(): string {
        do {
            $code = 'SUP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $stmt = $this->db->prepare('SELECT id FROM support_tickets WHERE ticket_code = :code LIMIT 1');
            $stmt->execute(['code' => $code]);
        } while ($stmt->fetchColumn());

        return $code;
    }
}
