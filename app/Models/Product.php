<?php

namespace App\Models;

use PDO;

class Product extends BaseModel {
    public function __construct() {
        parent::__construct();
    }

    // --- PRODUCT ---
    public function createProduct($data) {
        return $this->insert('product', $data);
    }

    public function getProduct($id) {
        return $this->getById('product', $id);
    }

    public function updateProduct($id, $data) {
        return $this->update('product', $id, $data);
    }

    public function deleteProduct($id) {
        return $this->softDelete('product', $id);
    }

    public function setProductStatus($id, $status) {
        return $this->updateProduct($id, ['status' => (int)$status]);
    }

    public function destroyProduct($id) {
        return $this->delete('product', $id);
    }

    public function getActiveProducts() {
        $stmt = $this->db->prepare("SELECT * FROM product WHERE status = 1 ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllProducts($filters = []) {
        $sql = "SELECT p.*, c.name AS category_name,
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image
                FROM product p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.name LIKE :keyword OR p.slug LIKE :keyword)";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $params['category_id'] = (int)$filters['category_id'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= " AND p.status = :status";
            $params['status'] = (int)$filters['status'];
        }

        if (!empty($filters['gender']) && $filters['gender'] !== 'all') {
            $sql .= " AND p.gender = :gender";
            $params['gender'] = $filters['gender'];
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductsByFilter($filters = []) {
        $sql = "SELECT p.*, p.base_price AS price, c.name AS category,
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image
                FROM product p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 1";
        $params = [];

        if (!empty($filters['gender']) && $filters['gender'] !== 'all') {
            $sql .= " AND p.gender = :gender";
            $params['gender'] = $filters['gender'];
        }

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $sql .= " AND c.name = :category";
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['price']) && $filters['price'] !== 'all') {
            if ($filters['price'] === 'lt3') {
                $sql .= " AND p.base_price < 3000000";
            } elseif ($filters['price'] === '3to5') {
                $sql .= " AND p.base_price >= 3000000 AND p.base_price <= 5000000";
            } elseif ($filters['price'] === 'gt5') {
                $sql .= " AND p.base_price > 5000000";
            }
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.name LIKE :keyword OR p.slug LIKE :keyword OR c.name LIKE :keyword)";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price-asc':
                    $sql .= " ORDER BY p.base_price ASC";
                    break;
                case 'price-desc':
                    $sql .= " ORDER BY p.base_price DESC";
                    break;
                case 'name-asc':
                    $sql .= " ORDER BY p.name ASC";
                    break;
                default:
                    $sql .= " ORDER BY p.id DESC";
                    break;
            }
        } else {
            $sql .= " ORDER BY p.id DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductWithImages($id) {
        $sql = "SELECT p.*, p.base_price AS price, c.name AS category
                FROM product p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id AND p.status = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $product['images'] = $this->getProductImages($id);
            $product['variants'] = $this->getProductVariants($id);
            $product['image'] = !empty($product['images']) ? $product['images'][0]['image_url'] : '';
        }

        return $product;
    }

    public function getProductForAdmin($id) {
        $sql = "SELECT p.*, c.name AS category_name
                FROM product p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRelatedProducts($productId, $categoryId, $gender, $limit = 4) {
        $sql = "SELECT p.*, p.base_price AS price, c.name AS category,
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image
                FROM product p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id != :id AND p.status = 1
                AND (p.category_id = :category_id OR p.gender = :gender)
                ORDER BY (p.category_id = :category_id) DESC, p.id DESC
                LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $productId,
            'category_id' => $categoryId,
            'gender' => $gender
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CATEGORIES ---
    public function createCategory($data) {
        return $this->insert('categories', $data);
    }

    public function getCategory($id) {
        return $this->getById('categories', $id);
    }

    public function updateCategory($id, $data) {
        return $this->update('categories', $id, $data);
    }

    public function deleteCategory($id) {
        return $this->softDelete('categories', $id);
    }

    public function getActiveCategories() {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCategories() {
        $stmt = $this->db->prepare("SELECT * FROM categories ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- PRODUCT_VARIANTS ---
    public function createProductVariant($data) {
        return $this->insert('product_variants', $data);
    }

    public function getProductVariant($id) {
        return $this->getById('product_variants', $id);
    }

    public function updateProductVariant($id, $data) {
        return $this->update('product_variants', $id, $data);
    }

    public function deleteProductVariant($id) {
        return $this->delete('product_variants', $id);
    }

    public function getProductVariants($productId) {
        $stmt = $this->db->prepare("SELECT * FROM product_variants WHERE product_id = :product_id ORDER BY size ASC, color ASC");
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- PRODUCT_IMAGES ---
    public function createProductImage($data) {
        return $this->insert('product_images', $data);
    }

    public function getProductImage($id) {
        return $this->getById('product_images', $id);
    }

    public function updateProductImage($id, $data) {
        return $this->update('product_images', $id, $data);
    }

    public function deleteProductImage($id) {
        return $this->delete('product_images', $id);
    }

    public function getProductImages($productId) {
        $stmt = $this->db->prepare("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, id ASC");
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setPrimaryImage($productId, $imageId) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id");
            $stmt->execute(['product_id' => $productId]);

            $stmt = $this->db->prepare("UPDATE product_images SET is_primary = 1 WHERE id = :id AND product_id = :product_id");
            $stmt->execute(['id' => $imageId, 'product_id' => $productId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // --- INVENTORY_LOGS ---
    public function createInventoryLog($data) {
        return $this->insert('inventory_logs', $data);
    }

    public function getInventoryLog($id) {
        return $this->getById('inventory_logs', $id);
    }

    public function updateStock($variantId, $quantityChanged, $reason) {
        return $this->createInventoryLog([
            'variant_id' => $variantId,
            'quantity_changed' => $quantityChanged,
            'reason' => $reason
        ]);
    }

    public function getInventoryLogsByVariant($variantId) {
        $stmt = $this->db->prepare("SELECT il.*, p.name AS product_name, pv.size, pv.color
                                    FROM inventory_logs il
                                    LEFT JOIN product_variants pv ON il.variant_id = pv.id
                                    LEFT JOIN product p ON pv.product_id = p.id
                                    WHERE il.variant_id = :variant_id
                                    ORDER BY il.id DESC");
        $stmt->execute(['variant_id' => $variantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInventoryLogs($limit = 100) {
        $stmt = $this->db->prepare("SELECT il.*, p.name AS product_name, pv.size, pv.color, pv.stock_quantity, c.name AS category_name
                                    FROM inventory_logs il
                                    LEFT JOIN product_variants pv ON il.variant_id = pv.id
                                    LEFT JOIN product p ON pv.product_id = p.id
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    ORDER BY il.id DESC
                                    LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInventoryOverview() {
        $stmt = $this->db->prepare("SELECT pv.*, p.name AS product_name, p.base_price, c.name AS category_name
                                    FROM product_variants pv
                                    LEFT JOIN product p ON pv.product_id = p.id
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    ORDER BY pv.stock_quantity ASC, p.name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
