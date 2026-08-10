<?php

namespace App\Services;

use App\Core\App;
use App\Models\SupportTicket;

class CustomerCareService {
    public function process(int $limit = 50): array {
        if (!MailService::isConfigured()) {
            return [
                'success' => false,
                'sent' => 0,
                'failed' => 0,
                'message' => 'SMTP chưa được cấu hình. Hàng đợi phản hồi CSKH vẫn được giữ nguyên.'
            ];
        }

        $tickets = new SupportTicket();
        $sent = 0;
        $failed = 0;
        foreach ($tickets->getPendingAutoReplies($limit) as $ticket) {
            try {
                MailService::sendHtml(
                    (string)$ticket['email'],
                    'PaceUp đã tiếp nhận yêu cầu hỗ trợ ' . $ticket['ticket_code'],
                    $this->buildHtml($ticket)
                );
                if ($tickets->markAutoReplySent((int)$ticket['id'])) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                $tickets->markAutoReplyFailed((int)$ticket['id'], $e->getMessage());
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'sent' => $sent,
            'failed' => $failed,
            'message' => "Đã xử lý {$sent} phản hồi CSKH, lỗi {$failed} phản hồi."
        ];
    }

    private function buildHtml(array $ticket): string {
        $name = htmlspecialchars((string)$ticket['name'], ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars((string)$ticket['ticket_code'], ENT_QUOTES, 'UTF-8');
        return '<!doctype html><html lang="vi"><body style="font-family:Arial,sans-serif;color:#111;line-height:1.6;">'
            . '<h2>PaceUp - Hỗ trợ khách hàng</h2><p>Chào ' . $name . ',</p>'
            . '<p>Chúng tôi đã tiếp nhận yêu cầu hỗ trợ <strong>' . $code . '</strong>. Nhân viên CSKH sẽ phản hồi trong giờ làm việc.</p>'
            . '<p>Trong lúc chờ, bạn có thể xem <a href="' . htmlspecialchars(App::url('faqs'), ENT_QUOTES, 'UTF-8') . '">Câu hỏi thường gặp</a> hoặc <a href="' . htmlspecialchars(App::url('tracking'), ENT_QUOTES, 'UTF-8') . '">tra cứu đơn hàng</a>.</p>'
            . '<p style="font-size:12px;color:#777;">Đây là email xác nhận tiếp nhận yêu cầu, không phải email quảng cáo.</p></body></html>';
    }
}
