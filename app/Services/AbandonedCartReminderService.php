<?php

namespace App\Services;

use App\Core\App;
use App\Models\Cart;

class AbandonedCartReminderService {
    public function process(int $limit = 50): array {
        if (!MailService::isConfigured()) {
            return [
                'success' => false,
                'sent' => 0,
                'failed' => 0,
                'message' => 'SMTP chưa được cấu hình. Hàng đợi vẫn được giữ nguyên để gửi sau.'
            ];
        }

        $cart = new Cart();
        $reminders = $cart->getAbandonedReminders($limit);
        $sent = 0;
        $failed = 0;

        foreach ($reminders as $reminder) {
            try {
                $items = $cart->getCartItemsForReminder((int)$reminder['user_id']);
                if (empty($items)) {
                    $cart->markReminderConverted((int)$reminder['user_id']);
                    continue;
                }

                $unsubscribeUrl = App::url('cart-reminder/unsubscribe?token=' . urlencode((string)$reminder['unsubscribe_token']));
                MailService::sendHtml(
                    (string)$reminder['email'],
                    'Bạn còn sản phẩm trong giỏ hàng PaceUp',
                    $this->buildHtml((string)($reminder['full_name'] ?? 'bạn'), $items, $unsubscribeUrl)
                );
                if ($cart->markReminderSent((int)$reminder['id'])) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                $cart->markReminderFailed((int)$reminder['id'], $e->getMessage());
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'sent' => $sent,
            'failed' => $failed,
            'message' => "Đã xử lý {$sent} email, lỗi {$failed} email."
        ];
    }

    private function buildHtml(string $name, array $items, string $unsubscribeUrl): string {
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr><td style="padding:10px 0;border-bottom:1px solid #eee;">'
                . htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8')
                . ' · Size ' . htmlspecialchars((string)($item['size'] ?? 'Mặc định'), ENT_QUOTES, 'UTF-8')
                . ' · Màu ' . htmlspecialchars((string)($item['color'] ?? 'Mặc định'), ENT_QUOTES, 'UTF-8')
                . '</td><td style="padding:10px 0;border-bottom:1px solid #eee;text-align:right;">'
                . number_format((float)$item['price'] * (int)$item['quantity'], 0, ',', '.') . ' ₫'
                . '</td></tr>';
        }

        return '<!doctype html><html lang="vi"><body style="font-family:Arial,sans-serif;color:#111;line-height:1.6;">'
            . '<h2>Chào ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</h2>'
            . '<p>Bạn vẫn còn sản phẩm trong giỏ hàng PaceUp. Nếu còn nhu cầu, bạn có thể quay lại hoàn tất đơn hàng:</p>'
            . '<table style="width:100%;max-width:640px;border-collapse:collapse;">' . $rows . '</table>'
            . '<p style="margin-top:24px;"><a href="' . htmlspecialchars(App::url('cart'), ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#111;color:#fff;padding:12px 20px;text-decoration:none;">Quay lại giỏ hàng</a></p>'
            . '<p style="font-size:12px;color:#777;">Nếu không muốn nhận email nhắc giỏ hàng, hãy <a href="' . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . '">hủy nhận email tại đây</a>.</p>'
            . '</body></html>';
    }
}
