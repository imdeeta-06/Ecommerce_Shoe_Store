<?php

namespace App\Models;

use PDO;

class Content extends BaseModel {
    public function __construct() {
        parent::__construct();
        // Xử lý logic cho các bảng: banner, posts, post_categories
    }

    // --- BANNER ---
    public function createBanner($data) { return $this->insert('banner', $data); }
    public function getBanner($id) { return $this->getById('banner', $id); }
    public function updateBanner($id, $data) { return $this->update('banner', $id, $data); }
    public function deleteBanner($id) { return $this->delete('banner', $id); } // Hard delete theo bảng yêu cầu
    public function softDeleteBanner($id) { return $this->softDelete('banner', $id); }

    public function getActiveBanners() {
        $stmt = $this->db->prepare("SELECT * FROM banner WHERE status = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- POSTS ---
    public function createPost($data) { return $this->insert('posts', $data); }
    public function getPost($id) { return $this->getById('posts', $id); }
    public function updatePost($id, $data) { return $this->update('posts', $id, $data); }
    public function deletePost($id) { return $this->delete('posts', $id); }

    public function getPostsByCategory($categoryId) {
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE category_id = :category_id");
        $stmt->execute(['category_id' => $categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- POST_CATEGORIES ---
    public function createPostCategory($data) { return $this->insert('post_categories', $data); }
    public function getPostCategory($id) { return $this->getById('post_categories', $id); }
    public function updatePostCategory($id, $data) { return $this->update('post_categories', $id, $data); }
    public function deletePostCategory($id) { return $this->delete('post_categories', $id); }
}
