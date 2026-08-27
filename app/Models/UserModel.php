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
        if (!empty($data['is_default'])) {
            $stmtDefault = $this->db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $stmtDefault->execute([$userId]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO user_addresses (user_id, address_line, ward_district_city, is_default)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $data['address_line'] ?? ($data['address'] ?? null),
            $data['ward_district_city'] ?? ($data['city'] ?? null),
            !empty($data['is_default']) ? 1 : 0
        ]);

        return $this->db->lastInsertId();
    }

    public function setDefaultAddress($userId, $addressId) {
        $stmtClear = $this->db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $stmtClear->execute([$userId]);

        $stmtSet = $this->db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        return $stmtSet->execute([$addressId, $userId]);
    }

    public function deleteAddress($addressId, $userId) {
        $stmt = $this->db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        return $stmt->execute([$addressId, $userId]);
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
