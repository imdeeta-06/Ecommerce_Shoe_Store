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
        $statusVal = ($status == 1 || $status === '1' || $status === 'active') ? 1 : 0;
        return $this->updateProduct($id, ['status' => $statusVal]);
    }

    public function destroyProduct($id) {
        $id = (int)$id;
        if ($id <= 0 || !$this->getProduct($id)) {
            throw new \RuntimeException('Sản phẩm không tồn tại.');
        }

        if ($this->productHasOrderItems($id)) {
            throw new \RuntimeException('Sản phẩm đã phát sinh đơn hàng, không thể xóa vĩnh viễn. Vui lòng ẩn sản phẩm để giữ đúng lịch sử đơn hàng.');
        }

        $variantIds = $this->getProductVariantIds($id);

        $this->db->beginTransaction();
        try {
            $this->deleteRelatedProductRows($id, $variantIds);
            $deleted = parent::delete('product', $id);
            $this->db->commit();

            return $deleted;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getActiveProducts() {
        $stmt = $this->db->prepare("SELECT * FROM product WHERE status = 1 ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveProductsForInventory(): array {
        $stmt = $this->db->prepare("SELECT p.id, p.name, c.name AS category_name
                                    FROM product p
                                    LEFT JOIN categories c ON c.id = p.category_id
                                    WHERE p.status = 1
                                    ORDER BY p.name ASC, p.id DESC");
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
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductsByFilter($filters = [], ?int $limit = null, int $offset = 0) {
        $this->ensureDefaultVariants();
        $sql = "SELECT p.*, p.base_price AS price, p.old_price AS compare_at_price, c.name AS category,
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image
                FROM product p
                LEFT JOIN categories c ON p.category_id = c.id";
        $params = [];
        $sql .= $this->catalogFilterWhere($filters, $params);

        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price-asc':
                    $sql .= " ORDER BY p.base_price ASC, p.id DESC";
                    break;
                case 'price-desc':
                    $sql .= " ORDER BY p.base_price DESC, p.id DESC";
                    break;
                case 'name-asc':
                    $sql .= " ORDER BY p.name ASC, p.id DESC";
                    break;
                default:
                    $sql .= " ORDER BY p.id DESC";
                    break;
            }
        } else {
            $sql .= " ORDER BY p.id DESC";
        }

        if ($limit !== null) {
            $sql .= ' LIMIT :catalog_limit OFFSET :catalog_offset';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }
        if ($limit !== null) {
            $stmt->bindValue(':catalog_limit', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':catalog_offset', max(0, $offset), PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductsCountByFilter(array $filters = []): int {
        $this->ensureDefaultVariants();
        $params = [];
        $sql = "SELECT COUNT(*) FROM product p LEFT JOIN categories c ON p.category_id=c.id";
        $sql .= $this->catalogFilterWhere($filters, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function catalogFilterWhere(array $filters, array &$params): string {
        $where = " WHERE p.status=1 AND (p.category_id IS NULL OR c.status=1)
            AND EXISTS (SELECT 1 FROM product_variants pv_available WHERE pv_available.product_id=p.id AND pv_available.status=1)";
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $where .= ' AND c.name=:category';
            $params['category'] = (string)$filters['category'];
        }
        if (!empty($filters['price']) && $filters['price'] !== 'all') {
            if ($filters['price'] === 'lt500k') $where .= ' AND p.base_price<500000';
            elseif ($filters['price'] === '500kto1m5') $where .= ' AND p.base_price>=500000 AND p.base_price<=1500000';
            elseif ($filters['price'] === 'gt1m5') $where .= ' AND p.base_price>1500000';
        }
        if (!empty($filters['keyword'])) {
            $where .= ' AND (p.name LIKE :keyword OR p.slug LIKE :keyword OR c.name LIKE :keyword)';
            $params['keyword'] = '%' . (string)$filters['keyword'] . '%';
        }
        return $where;
    }

    public function getProductWithImages($id) {
        $sql = "SELECT p.*, p.base_price AS price, p.old_price AS compare_at_price, c.name AS category
                FROM product p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id AND p.status = 1 AND (p.category_id IS NULL OR c.status = 1)
                AND EXISTS (SELECT 1 FROM product_variants pv_available WHERE pv_available.product_id = p.id AND pv_available.status = 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $product['images'] = $this->getProductImages($id);
            $product['variants'] = $this->getProductVariants($id, true);
            $product['image'] = !empty($product['images']) ? $product['images'][0]['image_url'] : '';
        }

        return $product;
    }

    public function getFeaturedProducts($limit = 8) {
        return $this->getMarketingProducts('p.is_featured DESC, p.id DESC', $limit, 'p.is_featured = 1');
    }

    public function getBestSellingProducts($limit = 8) {
        return $this->getMarketingProducts('p.sold_count DESC, p.id DESC', $limit, '1=1');
    }

    public function getDiscountedProducts($limit = 8) {
        if (!$this->tableHasColumn('product', 'old_price')) {
            return [];
        }
        return $this->getMarketingProducts('p.id DESC', $limit, 'p.old_price IS NOT NULL AND p.old_price > p.base_price');
    }

    public function getProductReviews($productId, $limit = 20) {
        $stmt = $this->db->prepare("SELECT r.*, u.display_name, u.full_name
            FROM reviews r
            LEFT JOIN user u ON u.id = r.user_id
            WHERE r.product_id = :product_id AND r.status = 1
            ORDER BY r.created_at DESC, r.id DESC
            LIMIT :limit");
        $stmt->bindValue(':product_id', (int)$productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function productSlugExists($slug, $excludeId = null) {
        $sql = "SELECT id FROM product WHERE slug = :slug";
        $params = ['slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = (int)$excludeId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        $sql = "SELECT p.*, p.base_price AS price, p.old_price AS compare_at_price, c.name AS category,
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image
                FROM product p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id != :id AND p.status = 1 AND (p.category_id IS NULL OR c.status = 1)
                AND EXISTS (SELECT 1 FROM product_variants pv_available WHERE pv_available.product_id = p.id AND pv_available.status = 1)
                AND p.category_id = :category_id
                ORDER BY p.is_featured DESC, p.id DESC
                LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $productId,
            'category_id' => $categoryId
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

    public function getCategoriesWithCounts() {
        $sql = "SELECT c.id, c.name, COUNT(p.id) AS product_count
                FROM categories c
                LEFT JOIN product p ON p.category_id = c.id AND p.status = 1
                    AND EXISTS (SELECT 1 FROM product_variants pv_available WHERE pv_available.product_id=p.id AND pv_available.status=1)
                WHERE c.status = 1
                GROUP BY c.id
                ORDER BY c.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveProductsCount() {
        return $this->getProductsCountByFilter();
    }

    public function getAllCategories() {
        $stmt = $this->db->prepare("SELECT * FROM categories ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function categorySlugExists($slug, $excludeId = null) {
        $sql = "SELECT id FROM categories WHERE slug = :slug";
        $params = ['slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = (int)$excludeId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    // --- PRODUCT_VARIANTS ---
    public function ensureDefaultVariant($productId): void {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return;
        }

        $stmt = $this->db->prepare('SELECT 1 FROM product_variants WHERE product_id = :product_id LIMIT 1');
        $stmt->execute(['product_id' => $productId]);
        if (!$stmt->fetchColumn()) {
            $insert = $this->db->prepare("INSERT INTO product_variants (product_id, size, color, stock_quantity, price_modifier) VALUES (:product_id, 'Mặc định', 'Mặc định', 0, 0)");
            $insert->execute(['product_id' => $productId]);
        }
    }

    private function ensureDefaultVariants(): void {
        $products = $this->db->query('SELECT p.id FROM product p LEFT JOIN product_variants pv ON pv.product_id = p.id GROUP BY p.id HAVING COUNT(pv.id) = 0')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($products as $productId) {
            $this->ensureDefaultVariant($productId);
        }
    }

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

    public function getProductVariants($productId, bool $activeOnly = false) {
        $select = $activeOnly
            ? 'product_variants.*, product_variants.stock_quantity AS physical_stock_quantity, GREATEST(0, product_variants.stock_quantity-COALESCE(product_variants.reserved_quantity,0)) AS stock_quantity'
            : 'product_variants.*';
        $sql = "SELECT {$select} FROM product_variants WHERE product_id = :product_id";
        if ($activeOnly) {
            $sql .= " AND status = 1";
        }
        $sql .= " ORDER BY
            CASE
                WHEN UPPER(TRIM(size)) = 'S' THEN 10
                WHEN UPPER(TRIM(size)) = 'M' THEN 20
                WHEN UPPER(TRIM(size)) = 'L' THEN 30
                WHEN UPPER(TRIM(size)) = 'XL' THEN 40
                WHEN UPPER(TRIM(size)) = 'XXL' THEN 50
                WHEN TRIM(size) REGEXP '^[0-9]+' THEN 60 + CAST(TRIM(size) AS UNSIGNED)
                WHEN LOWER(TRIM(size)) IN ('free size', 'freesize') THEN 100
                WHEN LOWER(TRIM(size)) = 'mặc định' THEN 110
                ELSE 90
            END,
            color ASC,
            id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function productVariantExists($productId, $size, $color, $excludeId = null) {
        $sql = "SELECT id FROM product_variants
                WHERE product_id = :product_id AND size = :size AND color = :color";
        $params = [
            'product_id' => (int)$productId,
            'size' => $size,
            'color' => $color
        ];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = (int)$excludeId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    public function productVariantHasOrderItems($variantId): bool {
        if (!$this->tableExists('order_items')) {
            return false;
        }

        $conditions = [];
        $params = ['variant_id' => (int)$variantId];
        if ($this->tableHasColumn('order_items', 'variant_id')) {
            $conditions[] = 'variant_id = :variant_id';
        }
        if ($this->tableHasColumn('order_items', 'product_id')) {
            $reference = $this->referencedTable('order_items', 'product_id');
            if ($reference === 'product_variants' || $reference === null) {
                $conditions[] = 'product_id = :legacy_variant_id';
                $params['legacy_variant_id'] = (int)$variantId;
            }
        }
        if (empty($conditions)) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT 1 FROM order_items WHERE ' . implode(' OR ', $conditions) . ' LIMIT 1');
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
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
        $image = $this->getProductImage($imageId);
        if (!$image || (int)$image['product_id'] !== (int)$productId) {
            throw new \RuntimeException('Ảnh không thuộc sản phẩm này.');
        }

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
        $ownsTransaction = !$this->db->inTransaction();
        try {
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }

            $stmt = $this->db->prepare('SELECT stock_quantity,reserved_quantity FROM product_variants WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => (int)$variantId]);
            $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$inventory) {
                throw new \RuntimeException('Không tìm thấy phân loại sản phẩm.');
            }

            $resultingStock = (int)$inventory['stock_quantity'] + (int)$quantityChanged;
            if ($resultingStock < 0) {
                throw new \RuntimeException('Không thể xuất kho vượt quá số lượng tồn hiện tại.');
            }
            if ($resultingStock < (int)($inventory['reserved_quantity'] ?? 0)) {
                throw new \RuntimeException('Không thể giảm tồn kho thấp hơn số lượng đang giữ cho các đơn chờ xử lý.');
            }

            $result = $this->createInventoryLog([
                'variant_id' => (int)$variantId,
                'quantity_changed' => (int)$quantityChanged,
                'reason' => trim((string)$reason) !== '' ? trim((string)$reason) : 'Điều chỉnh tồn kho'
            ]);
            if (!$this->triggerExists('trg_after_insert_inventory_log')) {
                $stmt = $this->db->prepare('UPDATE product_variants SET stock_quantity = stock_quantity + :quantity WHERE id = :id');
                $stmt->execute(['quantity' => (int)$quantityChanged, 'id' => (int)$variantId]);
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return $result;
        } catch (\Throwable $error) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
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

    public function getInventoryOverview($limit = 200) {
        $stmt = $this->db->prepare("SELECT pv.*, p.name AS product_name, p.base_price, c.name AS category_name
                                    FROM product_variants pv
                                    LEFT JOIN product p ON pv.product_id = p.id
                                    LEFT JOIN categories c ON p.category_id = c.id
                                    ORDER BY pv.stock_quantity ASC, p.name ASC
                                    LIMIT :limit");
        $stmt->bindValue(':limit', max(1, (int)$limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMarketingProducts(string $orderBy, int $limit, string $extraWhere = '1=1'): array {
        $stmt = $this->db->prepare("SELECT p.*, p.base_price AS price, p.old_price AS compare_at_price, c.name AS category,
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image
                FROM product p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.status = 1 AND ({$extraWhere}) AND (p.category_id IS NULL OR c.status = 1)
                AND EXISTS (SELECT 1 FROM product_variants pv_available WHERE pv_available.product_id = p.id AND pv_available.status = 1)
                ORDER BY {$orderBy}
                LIMIT :limit");
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function productHasOrderItems($productId) {
        $productId = (int)$productId;

        if (!$this->tableExists('order_items')) {
            return false;
        }

        if ($this->tableHasColumn('order_items', 'variant_id')) {
            $stmt = $this->db->prepare("
                SELECT 1
                FROM order_items oi
                JOIN product_variants pv ON oi.variant_id = pv.id
                WHERE pv.product_id = :product_id
                LIMIT 1
            ");
            $stmt->execute(['product_id' => $productId]);
            if ($stmt->fetchColumn()) {
                return true;
            }
        }

        if ($this->tableHasColumn('order_items', 'product_id')) {
            $reference = $this->referencedTable('order_items', 'product_id');

            if ($reference === 'product' || $reference === null) {
                $stmt = $this->db->prepare("SELECT 1 FROM order_items WHERE product_id = :product_id LIMIT 1");
                $stmt->execute(['product_id' => $productId]);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            }

            if ($reference === 'product_variants' || $reference === null) {
                $stmt = $this->db->prepare("
                    SELECT 1
                    FROM order_items oi
                    JOIN product_variants pv ON oi.product_id = pv.id
                    WHERE pv.product_id = :product_id
                    LIMIT 1
                ");
                $stmt->execute(['product_id' => $productId]);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getProductVariantIds($productId) {
        if (!$this->tableExists('product_variants') || !$this->tableHasColumn('product_variants', 'product_id')) {
            return [];
        }

        $stmt = $this->db->prepare("SELECT id FROM product_variants WHERE product_id = :product_id");
        $stmt->execute(['product_id' => (int)$productId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function deleteRelatedProductRows($productId, array $variantIds) {
        $this->deleteByColumn('inventory_logs', 'variant_id', $variantIds);
        $this->deleteByColumn('product_sales_reports', 'variant_id', $variantIds);
        $this->deleteCartRows($productId, $variantIds);
        $this->deleteByColumn('wishlist', 'product_id', $productId);
        $this->deleteByColumn('reviews', 'product_id', $productId);
        $this->deleteByColumn('product_sales_reports', 'product_id', $productId);
        $this->deleteByColumn('product_images', 'product_id', $productId);
        $this->deleteByColumn('product_variants', 'product_id', $productId);
    }

    private function deleteCartRows($productId, array $variantIds) {
        if (!$this->tableExists('cart')) {
            return;
        }

        if ($this->tableHasColumn('cart', 'product_id')) {
            $reference = $this->referencedTable('cart', 'product_id');

            if ($reference === 'product' || $reference === null) {
                $this->deleteByColumn('cart', 'product_id', $productId);
            }

            if ($reference === 'product_variants' || $reference === null) {
                $this->deleteByColumn('cart', 'product_id', $variantIds);
            }
        }

        if ($this->tableHasColumn('cart', 'variant_id')) {
            $this->deleteByColumn('cart', 'variant_id', $variantIds);
        }
    }

    private function deleteByColumn($table, $column, $values) {
        if (!$this->tableHasColumn($table, $column)) {
            return;
        }

        $values = is_array($values) ? array_values(array_filter(array_map('intval', $values))) : [(int)$values];
        if (empty($values)) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $stmt = $this->db->prepare("DELETE FROM `{$table}` WHERE `{$column}` IN ({$placeholders})");
        $stmt->execute($values);
    }

    private function tableExists($table) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
        ");
        $stmt->execute(['table_name' => $table]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function tableHasColumn($table, $column) {
        if (!$this->tableExists($table)) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
        ");
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function referencedTable($table, $column) {
        if (!$this->tableHasColumn($table, $column)) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column
        ]);

        $tableName = $stmt->fetchColumn();
        return $tableName ? strtolower((string)$tableName) : null;
    }

    private function triggerExists($triggerName): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = :trigger_name');
        $stmt->execute(['trigger_name' => $triggerName]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
