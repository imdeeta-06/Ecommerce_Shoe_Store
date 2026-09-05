<?php

namespace App\Models;

use PDO;

class UserModel extends BaseModel {
    public function __construct() {
        parent::__construct();
        // Quản lý các bảng theo schema hiện có: user, user_addresses, password_reset_otp
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function loginThrottleStatus(string $email, string $ip): array {
        [$emailHash,$ipHash]=$this->loginAttemptHashes($email,$ip);
        $stmt=$this->db->prepare("SELECT
            SUM(email_hash=:email_hash) email_failures,
            SUM(ip_hash=:ip_hash) ip_failures,
            UNIX_TIMESTAMP(MIN(attempted_at)+INTERVAL 15 MINUTE)-UNIX_TIMESTAMP() retry_after
            FROM login_attempts
            WHERE was_successful=0 AND attempted_at>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)
              AND (email_hash=:email_hash OR ip_hash=:ip_hash)");
        $stmt->execute(['email_hash'=>$emailHash,'ip_hash'=>$ipHash]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC)?:[];
        $blocked=(int)($row['email_failures']??0)>=5 || (int)($row['ip_failures']??0)>=20;
        return ['blocked'=>$blocked,'retry_after'=>$blocked?max(1,(int)($row['retry_after']??900)):0];
    }

    public function recordLoginAttempt(string $email,string $ip,bool $successful): void {
        [$emailHash,$ipHash]=$this->loginAttemptHashes($email,$ip);
        $stmt=$this->db->prepare('INSERT INTO login_attempts(email_hash,ip_hash,was_successful) VALUES(?,?,?)');
        $stmt->execute([$emailHash,$ipHash,$successful?1:0]);
        if($successful){
            $this->db->prepare('DELETE FROM login_attempts WHERE was_successful=0 AND (email_hash=? OR ip_hash=?)')
                ->execute([$emailHash,$ipHash]);
        }
        if(random_int(1,100)===1){$this->db->exec('DELETE FROM login_attempts WHERE attempted_at<DATE_SUB(NOW(),INTERVAL 30 DAY)');}
    }

    private function loginAttemptHashes(string $email,string $ip): array {
        $pepper = trim((string)\App\Core\App::env('APP_SECURITY_KEY'));
        if ($pepper === '') {
            throw new \RuntimeException('Thiếu APP_SECURITY_KEY để bảo vệ giới hạn đăng nhập.');
        }
        return [hash_hmac('sha256',mb_strtolower(trim($email),'UTF-8'),$pepper),hash_hmac('sha256',trim($ip),$pepper)];
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO user (full_name, email, password, phone, role, status, google_id, email_verified)
            VALUES (?, ?, ?, ?, 'user', 1, ?, ?)
        ");

        $stmt->execute([
            $data['full_name'] ?? null,
            $data['email'] ?? null,
            $data['password'] ?? null,
            $data['phone'] ?? null,
            $data['google_id'] ?? null,
            isset($data['email_verified']) ? $data['email_verified'] : 0
        ]);

        return $this->db->lastInsertId();
    }

    public function createAdmin($data) {
        $columns = ['full_name', 'email', 'password', 'phone', 'role', 'status'];
        $values = [
            $data['full_name'] ?? null,
            $data['email'] ?? null,
            $data['password'] ?? null,
            $data['phone'] ?? null,
            'admin',
            1
        ];

        if ($this->hasColumn('display_name')) {
            array_splice($columns, 1, 0, 'display_name');
            array_splice($values, 1, 0, $data['display_name'] ?? null);
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $this->db->prepare("
            INSERT INTO user (" . implode(', ', $columns) . ")
            VALUES ($placeholders)
        ");
        $stmt->execute($values);

        return $this->db->lastInsertId();
    }

    public function updateProfile($id, $data) {
        $stmt = $this->db->prepare("UPDATE user SET full_name = ?, phone = ? WHERE id = ?");
        return $stmt->execute([
            $data['full_name'] ?? null,
            $data['phone'] ?? null,
            $id
        ]);
    }

    public function updateAvatar($id, $path) {
        $stmt = $this->db->prepare("UPDATE user SET avatar = ? WHERE id = ?");
        return $stmt->execute([$path, $id]);
    }

    public function updatePassword($id, $hash) {
        $stmt = $this->db->prepare("UPDATE user SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE user SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function updateRole($id, $role) {
        $stmt = $this->db->prepare("UPDATE user SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $id]);
    }

    public function getAll($filters = [], $page = 1, $limit = 20) {
        $where = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[] = "(full_name LIKE ? OR email LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if (isset($filters['role']) && $filters['role'] !== '') {
            $where[] = "role = ?";
            $params[] = $filters['role'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM user" . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, (int) $page);
        $limit = max(1, (int) $limit);
        $offset = ($page - 1) * $limit;

        $stmt = $this->db->prepare("SELECT * FROM user" . $whereSql . " ORDER BY id DESC LIMIT ? OFFSET ?");
        $index = 1;
        foreach ($params as $param) {
            $stmt->bindValue($index, $param);
            $index++;
        }
        $stmt->bindValue($index, $limit, PDO::PARAM_INT);
        $stmt->bindValue($index + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }

    public function getAddresses($userId) {
        $stmt = $this->db->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAddress($userId, $data) {
        $recipientName = trim((string)($data['recipient_name'] ?? ''));
        $recipientPhone = trim((string)($data['recipient_phone'] ?? ($data['phone'] ?? '')));
        $addressLine = trim((string)($data['address_line'] ?? ($data['address'] ?? '')));
        $area = trim((string)($data['ward_district_city'] ?? ($data['city'] ?? '')));

        if ($recipientName === '' || $recipientPhone === '' || $addressLine === '' || $area === '') {
            throw new \InvalidArgumentException('Thông tin địa chỉ nhận hàng chưa đầy đủ.');
        }

        $this->db->beginTransaction();
        try {
            if (!empty($data['is_default'])) {
                $stmtDefault = $this->db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                $stmtDefault->execute([(int)$userId]);
            }

            $stmt = $this->db->prepare("
                INSERT INTO user_addresses
                    (user_id, recipient_name, recipient_phone, address_line, ward_district_city, is_default, status)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                (int)$userId,
                $recipientName,
                $recipientPhone,
                $addressLine,
                $area,
                !empty($data['is_default']) ? 1 : 0
            ]);

            $addressId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $addressId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function setDefaultAddress($userId, $addressId) {
        try {
            $this->db->beginTransaction();
            $exists = $this->db->prepare('SELECT id FROM user_addresses WHERE id = ? AND user_id = ? AND status = 1 FOR UPDATE');
            $exists->execute([(int)$addressId, (int)$userId]);
            if (!$exists->fetchColumn()) {
                throw new \RuntimeException('Địa chỉ không hợp lệ hoặc đã ngừng sử dụng.');
            }
            $this->db->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?')->execute([(int)$userId]);
            $result = $this->db->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?')
                ->execute([(int)$addressId, (int)$userId]);
            $this->db->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function deleteAddress($addressId, $userId) {
        try {
            $this->db->beginTransaction();
            $current = $this->db->prepare('SELECT id, is_default FROM user_addresses WHERE id = ? AND user_id = ? FOR UPDATE');
            $current->execute([(int)$addressId, (int)$userId]);
            $address = $current->fetch(PDO::FETCH_ASSOC);
            if (!$address) {
                $this->db->rollBack();
                return false;
            }
            $stmt = $this->db->prepare('DELETE FROM user_addresses WHERE id = ? AND user_id = ?');
            $stmt->execute([(int)$addressId, (int)$userId]);
            if ((int)$address['is_default'] === 1) {
                $replacement = $this->db->prepare('SELECT id FROM user_addresses WHERE user_id = ? AND status = 1 ORDER BY created_at DESC, id DESC LIMIT 1 FOR UPDATE');
                $replacement->execute([(int)$userId]);
                $replacementId = (int)$replacement->fetchColumn();
                if ($replacementId > 0) {
                    $this->db->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = ?')->execute([$replacementId]);
                }
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function updateEmailVerified($id, $status) {
        $stmt = $this->db->prepare("UPDATE user SET email_verified = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function updateGoogleId($id, $googleId) {
        $stmt = $this->db->prepare("UPDATE user SET google_id = ? WHERE id = ?");
        return $stmt->execute([$googleId, $id]);
    }

    public function findByGoogleId($googleId) {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE google_id = ?");
        $stmt->execute([$googleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function invalidateOtp($userId, $purpose) {
        $stmt = $this->db->prepare("DELETE FROM auth_otps WHERE user_id = ? AND purpose = ?");
        return $stmt->execute([$userId, $purpose]);
    }

    public function createAuthOtp($userId, $purpose, $otpHash, $expiresAt) {
        $stmt = $this->db->prepare("
            REPLACE INTO auth_otps (user_id, purpose, otp_hash, expires_at, attempts) 
            VALUES (?, ?, ?, ?, 0)
        ");
        return $stmt->execute([$userId, $purpose, $otpHash, $expiresAt]);
    }

    public function otpRequestCooldownRemaining(int $userId, string $purpose, int $seconds = 60): int {
        $stmt = $this->db->prepare('SELECT UNIX_TIMESTAMP(created_at) FROM auth_otps WHERE user_id = ? AND purpose = ? LIMIT 1');
        $stmt->execute([$userId, $purpose]);
        $createdAt = (int)$stmt->fetchColumn();
        return $createdAt > 0 ? max(0, $seconds - (time() - $createdAt)) : 0;
    }

    public function findAuthOtp($userId, $purpose) {
        $stmt = $this->db->prepare("SELECT * FROM auth_otps WHERE user_id = ? AND purpose = ?");
        $stmt->execute([$userId, $purpose]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function incrementOtpAttempts($otpId) {
        $stmt = $this->db->prepare("UPDATE auth_otps SET attempts = attempts + 1 WHERE id = ?");
        return $stmt->execute([$otpId]);
    }

    public function deleteAuthOtp($otpId) {
        $stmt = $this->db->prepare("DELETE FROM auth_otps WHERE id = ?");
        return $stmt->execute([$otpId]);
    }

    private function hasColumn($column) {
        $stmt = $this->db->prepare("SHOW COLUMNS FROM user LIKE ?");
        $stmt->execute([$column]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
