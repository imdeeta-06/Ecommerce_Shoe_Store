<?php

namespace App\Models;

use PDO;

class User extends BaseModel {
    public function __construct() {
        parent::__construct();
        // Xử lý logic cho các bảng: user, user_addresses, password_reset_otp, reviews
    }

    // --- USER ---
    public function createUser($data) { return $this->insert('user', $data); }
    public function getUser($id) { return $this->getById('user', $id); }
    public function updateUser($id, $data) { return $this->update('user', $id, $data); }
    public function deleteUser($id) { return $this->softDelete('user', $id); }

    // Logic Cập nhật Avatar
    public function updateAvatar($id, $avatarUrl) {
        $user = $this->getUser($id);
        $oldAvatar = $user['avatar'] ?? null;
        
        $success = $this->update('user', $id, ['avatar' => $avatarUrl]);
        
        return [
            'success' => !!$success,
            'old_avatar' => $oldAvatar // Trả về ảnh cũ để Controller tiện việc xóa file rác (unlink)
        ];
    }

    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- USER_ADDRESSES ---
    public function createUserAddress($data) { return $this->insert('user_addresses', $data); }
    public function getUserAddress($id) { return $this->getById('user_addresses', $id); }
    public function updateUserAddress($id, $data) { return $this->update('user_addresses', $id, $data); }
    public function deleteUserAddress($id) { return $this->softDelete('user_addresses', $id); }

    public function getUserAddresses($userId) {
        $stmt = $this->db->prepare("SELECT * FROM user_addresses WHERE user_id = :user_id AND status = 1");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- REVIEWS ---
    public function createReview($data) { return $this->insert('reviews', $data); }
    public function getReview($id) { return $this->getById('reviews', $id); }
    
    // Cập nhật trạng thái kiểm duyệt (Duyệt/Ẩn)
    public function moderateReview($id, $status) {
        return $this->update('reviews', $id, ['status' => $status]);
    }

    public function getReviewsByProduct($productId) {
        $stmt = $this->db->prepare("SELECT * FROM reviews WHERE product_id = :product_id AND status = 1");
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- PASSWORD_RESET_OTP ---
    public function createOtp($data) { return $this->insert('password_reset_otp', $data); }
    public function getOtp($id) { return $this->getById('password_reset_otp', $id); }
    public function updateOtp($id, $data) { return $this->update('password_reset_otp', $id, $data); }
    public function deleteOtp($id) { return $this->delete('password_reset_otp', $id); }

    public function getValidOtp($email, $otpCode) {
        $stmt = $this->db->prepare("SELECT * FROM password_reset_otp WHERE email = :email AND otp_code = :otp_code AND is_used = 0 AND expires_at > NOW()");
        $stmt->execute(['email' => $email, 'otp_code' => $otpCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
