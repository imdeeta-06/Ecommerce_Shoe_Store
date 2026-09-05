<?php

namespace App\Models;

use PDO;
use App\Core\App;
use App\Services\MailService;

class Newsletter extends BaseModel {
    public function subscribe(string $email, string $ip, string $consentVersion): array {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Địa chỉ email không hợp lệ.'];
        }

        $token = bin2hex(random_bytes(32));
        $confirmationToken = bin2hex(random_bytes(32));
        $consentText = 'Tôi đồng ý nhận email sản phẩm, bài viết và khuyến mãi từ Liên Hoa; có thể hủy bất kỳ lúc nào.';
        $stmt = $this->db->prepare("INSERT INTO newsletter_subscriptions
            (email, status, consent_version, consent_text, source, consent_ip, consented_at, unsubscribed_at, unsubscribe_token, confirmation_token, confirmation_expires_at, confirmed_at)
            VALUES (:email, 'pending', :version, :text, 'footer', :ip, NOW(), NULL, :token, :confirmation_token, DATE_ADD(NOW(), INTERVAL 24 HOUR), NULL)
            ON DUPLICATE KEY UPDATE status = 'pending', consent_version = VALUES(consent_version),
                consent_text = VALUES(consent_text), source = VALUES(source), consent_ip = VALUES(consent_ip),
                consented_at = NOW(), unsubscribed_at = NULL, unsubscribe_token = VALUES(unsubscribe_token),
                confirmation_token = VALUES(confirmation_token), confirmation_expires_at = VALUES(confirmation_expires_at), confirmed_at = NULL");
        $stmt->execute([
            'email' => $email,
            'version' => substr($consentVersion, 0, 50),
            'text' => $consentText,
            'ip' => substr($ip, 0, 45) ?: null,
            'token' => $token,
            'confirmation_token' => $confirmationToken,
        ]);
        if (!MailService::isConfigured()) {
            return ['success' => true, 'message' => 'Đã lưu yêu cầu ở trạng thái chờ xác nhận. Cần cấu hình SMTP để gửi liên kết double opt-in.'];
        }
        $url = App::publicUrl('newsletter/confirm?token=' . urlencode($confirmationToken));
        try {
            MailService::sendHtml($email, 'Xác nhận đăng ký nhận tin Liên Hoa', '<h2>Xác nhận nhận tin</h2><p>Hãy bấm liên kết sau trong 24 giờ để hoàn tất đăng ký:</p><p><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">Xác nhận đăng ký</a></p>');
            return ['success' => true, 'message' => 'Vui lòng mở email và xác nhận đăng ký trong 24 giờ.'];
        } catch (\Throwable $e) {
            return ['success' => true, 'message' => 'Đã lưu yêu cầu chờ xác nhận nhưng chưa gửi được email: ' . $e->getMessage()];
        }
    }

    public function confirm(string $token): bool {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return false;
        $stmt = $this->db->prepare("UPDATE newsletter_subscriptions SET status='subscribed', confirmed_at=NOW(), confirmation_token=NULL, confirmation_expires_at=NULL
            WHERE confirmation_token=:token AND status='pending' AND confirmation_expires_at>=NOW()");
        $stmt->execute(['token'=>$token]);
        return $stmt->rowCount() === 1;
    }

    public function unsubscribe(string $token): bool {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE newsletter_subscriptions SET status = 'unsubscribed', unsubscribed_at = NOW(), confirmation_token=NULL, confirmation_expires_at=NULL WHERE unsubscribe_token = :token AND status IN ('pending','subscribed')");
        $stmt->execute(['token' => $token]);
        return $stmt->rowCount() > 0;
    }
}
