<?php

namespace App\Models;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;
    private static $schemaReady = false;

    private function __construct() {
        $config = require __DIR__ . '/../../config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        try {
            $this->connection = new PDO($dsn, $config['user'], $config['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->ensureEcommerceSchema();
        } catch (PDOException $e) {
            die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    /**
     * The project has been developed against a few slightly different SQL
     * dumps.  Keep one small, idempotent upgrade path so a fresh request does
     * not silently run with the old product_id-as-variant schema.
     */
    private function ensureEcommerceSchema(): void {
        if (self::$schemaReady) {
            return;
        }

        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `version` VARCHAR(100) NOT NULL,
                `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $stmt = $this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1');
            $stmt->execute(['version' => 'ecommerce_business_v8']);
            if ($stmt->fetchColumn()) {
                self::$schemaReady = true;
                return;
            }

            $this->migrateVariantColumns();
            $this->migrateOrderColumns();
            $this->migrateProductColumns();
            $this->migratePaymentColumns();
            $this->migrateCouponColumns();
            $this->migrateReviewColumns();
            $this->migrateReportColumns();
            $this->createAfterSaleTables();
            $this->migrateAfterSaleColumns();
            $this->createAfterSaleEvidenceTable();
            $this->createOrderSalesRecognitionTable();
            $this->createMarketingTables();
            $this->migrateCartReminderColumns();
            $this->migrateElectronicContractColumns();
            $this->createOrderNotificationsTable();
            $this->createSupportTicketsTable();
            $this->normalizeOrderStatusData();
            $this->dropBrokenTriggers();
            $this->seedDefaultProductVariants();
            $this->rebuildSalesCounters();
            $this->connection->prepare('INSERT INTO schema_migrations (version) VALUES (:version)')->execute(['version' => 'ecommerce_business_v8']);
            self::$schemaReady = true;
        } catch (PDOException $e) {
            // A restricted DB account should not make the storefront crash.
            // The application still reports the concrete business error when
            // a feature needs a column that could not be upgraded.
        }
    }

    private function migrateVariantColumns(): void {
        if ($this->tableExists('cart')) {
            if (!$this->columnExists('cart', 'variant_id')) {
                $this->connection->exec("ALTER TABLE `cart` ADD `variant_id` INT(11) NULL DEFAULT NULL AFTER `session_id`");
            }

            if ($this->columnExists('cart', 'product_id')) {
                $rows = $this->connection->query("SELECT id, product_id FROM cart WHERE variant_id IS NULL AND product_id IS NOT NULL")->fetchAll();
                $findVariantById = $this->connection->prepare("SELECT id FROM product_variants WHERE id = ? LIMIT 1");
                $findVariantByProduct = $this->connection->prepare("SELECT id FROM product_variants WHERE product_id = ? ORDER BY id ASC LIMIT 1");
                $setVariant = $this->connection->prepare("UPDATE cart SET variant_id = ? WHERE id = ?");

                foreach ($rows as $row) {
                    $legacyId = (int)$row['product_id'];
                    $findVariantById->execute([$legacyId]);
                    $variantId = $findVariantById->fetchColumn();

                    if (!$variantId) {
                        $findVariantByProduct->execute([$legacyId]);
                        $variantId = $findVariantByProduct->fetchColumn();
                    }

                    if ($variantId) {
                        $setVariant->execute([(int)$variantId, (int)$row['id']]);
                    }
                }
            }

            $this->connection->exec("DELETE FROM cart WHERE variant_id IS NULL");
            $this->dropForeignKeysForColumn('cart', 'product_id');
            if ($this->columnExists('cart', 'product_id')) {
                $this->connection->exec("ALTER TABLE `cart` DROP COLUMN `product_id`");
            }
            $this->addForeignKeyIfMissing('cart', 'variant_id', 'product_variants', 'id', 'cart_variant_fk', 'CASCADE');
        }

        if ($this->tableExists('order_items')) {
            if (!$this->columnExists('order_items', 'variant_id')) {
                $this->connection->exec("ALTER TABLE `order_items` ADD `variant_id` INT(11) NULL DEFAULT NULL AFTER `order_id`");
            }

            $this->connection->exec("UPDATE order_items oi JOIN product_variants pv ON pv.id = oi.product_id SET oi.variant_id = pv.id WHERE oi.variant_id IS NULL AND oi.product_id IS NOT NULL");
            $this->addColumnIfMissing('order_items', 'product_name_snapshot', "VARCHAR(255) NULL DEFAULT NULL AFTER `price_at_time`");
            $this->addColumnIfMissing('order_items', 'variant_size_snapshot', "VARCHAR(50) NULL DEFAULT NULL AFTER `product_name_snapshot`");
            $this->addColumnIfMissing('order_items', 'variant_color_snapshot', "VARCHAR(50) NULL DEFAULT NULL AFTER `variant_size_snapshot`");
            $this->addForeignKeyIfMissing('order_items', 'variant_id', 'product_variants', 'id', 'order_items_variant_fk', 'SET NULL');
        }
    }

    private function migrateOrderColumns(): void {
        if (!$this->tableExists('orders')) {
            return;
        }

        $this->connection->exec("ALTER TABLE `orders` MODIFY `status` ENUM('pending','confirmed','preparing','shipping','delivered','completed','canceled') DEFAULT 'pending'");
        $this->addColumnIfMissing('orders', 'shipping_fee', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `final_amount`");
        $this->addColumnIfMissing('orders', 'shipping_carrier', "VARCHAR(100) NULL DEFAULT NULL AFTER `shipping_address`");
        $this->addColumnIfMissing('orders', 'tracking_code', "VARCHAR(100) NULL DEFAULT NULL AFTER `shipping_carrier`");
        $this->addColumnIfMissing('orders', 'shipping_status', "VARCHAR(30) NOT NULL DEFAULT 'not_shipped' AFTER `tracking_code`");
        $this->addColumnIfMissing('orders', 'customer_note', "TEXT NULL DEFAULT NULL AFTER `shipping_email`");
        $this->addColumnIfMissing('orders', 'shipped_at', "DATETIME NULL DEFAULT NULL AFTER `customer_note`");
        $this->addColumnIfMissing('orders', 'delivered_at', "DATETIME NULL DEFAULT NULL AFTER `shipped_at`");
        $this->addColumnIfMissing('orders', 'completed_at', "DATETIME NULL DEFAULT NULL AFTER `delivered_at`");
    }

    private function migrateElectronicContractColumns(): void {
        if (!$this->tableExists('orders')) {
            return;
        }

        // This is an auditable acceptance record, not a claim of a digital
        // signature. It records exactly which terms the buyer accepted at
        // checkout and when the order was created.
        $this->addColumnIfMissing('orders', 'terms_accepted', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `customer_note`");
        $this->addColumnIfMissing('orders', 'terms_accepted_at', "DATETIME NULL DEFAULT NULL AFTER `terms_accepted`");
        $this->addColumnIfMissing('orders', 'contract_version', "VARCHAR(30) NOT NULL DEFAULT 'v1.0' AFTER `terms_accepted_at`");
        $this->addColumnIfMissing('orders', 'terms_accepted_ip', "VARCHAR(45) NULL DEFAULT NULL AFTER `contract_version`");
        $this->addColumnIfMissing('orders', 'terms_accepted_user_agent', "VARCHAR(1000) NULL DEFAULT NULL AFTER `terms_accepted_ip`");
    }

    private function createOrderNotificationsTable(): void {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS `order_notifications` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL,
            `user_id` INT(11) NULL DEFAULT NULL,
            `recipient_email` VARCHAR(255) NOT NULL,
            `notification_type` VARCHAR(60) NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `body_html` LONGTEXT NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
            `attempt_count` INT(11) NOT NULL DEFAULT 0,
            `next_attempt_at` DATETIME NULL DEFAULT NULL,
            `sent_at` DATETIME NULL DEFAULT NULL,
            `last_error` TEXT NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_notification_unique` (`order_id`, `notification_type`),
            KEY `order_notification_queue_idx` (`status`, `next_attempt_at`, `attempt_count`),
            KEY `order_notification_order_idx` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function createSupportTicketsTable(): void {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS `support_tickets` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `ticket_code` VARCHAR(40) NOT NULL,
            `user_id` INT(11) NULL DEFAULT NULL,
            `name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(30) NULL DEFAULT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
            `auto_reply_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
            `auto_reply_attempts` INT(11) NOT NULL DEFAULT 0,
            `auto_reply_next_attempt_at` DATETIME NULL DEFAULT NULL,
            `auto_reply_sent_at` DATETIME NULL DEFAULT NULL,
            `auto_reply_last_error` TEXT NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `support_ticket_code_unique` (`ticket_code`),
            KEY `support_ticket_status_idx` (`status`, `created_at`),
            KEY `support_ticket_reply_idx` (`auto_reply_status`, `auto_reply_next_attempt_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function migrateProductColumns(): void {
        if (!$this->tableExists('product')) {
            return;
        }

        $this->addColumnIfMissing('product', 'reserved_quantity', "INT(11) NOT NULL DEFAULT 0 AFTER `sold_count`");
        $this->addColumnIfMissing('product', 'returned_count', "INT(11) NOT NULL DEFAULT 0 AFTER `reserved_quantity`");
        $this->addColumnIfMissing('product', 'is_featured', "TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`");

        $featured = (int)$this->connection->query('SELECT COUNT(*) FROM product WHERE is_featured = 1 AND status = 1')->fetchColumn();
        if ($featured === 0) {
            $this->connection->exec('UPDATE product SET is_featured = 1 WHERE status = 1 ORDER BY id DESC LIMIT 6');
        }
    }

    private function migratePaymentColumns(): void {
        if (!$this->tableExists('payments')) {
            return;
        }

        $this->addColumnIfMissing('payments', 'payment_state', "VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER `payment_status`");
        $this->addColumnIfMissing('payments', 'transaction_code', "VARCHAR(120) NULL DEFAULT NULL AFTER `payment_state`");
        $this->addColumnIfMissing('payments', 'paid_at', "DATETIME NULL DEFAULT NULL AFTER `transaction_code`");
        $this->addColumnIfMissing('payments', 'failed_at', "DATETIME NULL DEFAULT NULL AFTER `paid_at`");
        $this->addColumnIfMissing('payments', 'refund_status', "VARCHAR(30) NOT NULL DEFAULT 'not_requested' AFTER `failed_at`");
        $this->addColumnIfMissing('payments', 'refund_transaction_code', "VARCHAR(120) NULL DEFAULT NULL AFTER `refund_status`");
        $this->addColumnIfMissing('payments', 'refunded_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `refund_transaction_code`");
        $this->addColumnIfMissing('payments', 'refunded_at', "DATETIME NULL DEFAULT NULL AFTER `refunded_amount`");

        $this->connection->exec("UPDATE payments SET payment_state = CASE
            WHEN payment_status = 1 THEN 'paid'
            ELSE 'pending'
        END WHERE payment_state IS NULL OR payment_state = '' OR payment_state = 'pending'");
        $this->connection->exec("UPDATE payments pay JOIN orders o ON o.id = pay.order_id
            SET pay.payment_status = 1, pay.payment_state = 'paid', pay.paid_at = COALESCE(pay.paid_at, o.delivered_at, NOW())
            WHERE o.status IN ('delivered', 'completed') AND pay.payment_state NOT IN ('refunded', 'refund_pending')");
        $this->connection->exec("UPDATE payments pay JOIN orders o ON o.id = pay.order_id
            SET pay.payment_state = 'canceled'
            WHERE o.status = 'canceled' AND pay.payment_state = 'pending'");
    }

    private function migrateReportColumns(): void {
        if ($this->tableExists('daily_revenue_reports')) {
            $this->addColumnIfMissing('daily_revenue_reports', 'refunded_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `net_revenue`");
        }
    }

    private function migrateAfterSaleColumns(): void {
        if (!$this->tableExists('after_sale_requests')) {
            return;
        }

        $this->addColumnIfMissing('after_sale_requests', 'requested_quantity', "INT(11) NOT NULL DEFAULT 1 AFTER `reason`");
        $this->addColumnIfMissing('after_sale_requests', 'approved_quantity', "INT(11) NOT NULL DEFAULT 0 AFTER `requested_quantity`");
        $this->addColumnIfMissing('after_sale_requests', 'return_deadline', "DATETIME NULL DEFAULT NULL AFTER `approved_quantity`");
        $this->addColumnIfMissing('after_sale_requests', 'restockable', "TINYINT(1) NOT NULL DEFAULT 1 AFTER `return_deadline`");
        $this->addColumnIfMissing('after_sale_requests', 'inventory_processed_quantity', "INT(11) NOT NULL DEFAULT 0 AFTER `restockable`");
        $this->addColumnIfMissing('after_sale_requests', 'sales_reversed_quantity', "INT(11) NOT NULL DEFAULT 0 AFTER `inventory_processed_quantity`");
        $this->addColumnIfMissing('after_sale_requests', 'refund_status', "VARCHAR(30) NOT NULL DEFAULT 'not_requested' AFTER `refund_amount`");
        $this->addColumnIfMissing('after_sale_requests', 'refund_transaction_code', "VARCHAR(120) NULL DEFAULT NULL AFTER `refund_status`");
        $this->addColumnIfMissing('after_sale_requests', 'refund_processed_at', "DATETIME NULL DEFAULT NULL AFTER `refund_transaction_code`");
        $this->addColumnIfMissing('after_sale_requests', 'approved_at', "DATETIME NULL DEFAULT NULL AFTER `refund_processed_at`");
        $this->addColumnIfMissing('after_sale_requests', 'received_at', "DATETIME NULL DEFAULT NULL AFTER `approved_at`");
        $this->addColumnIfMissing('after_sale_requests', 'completed_at', "DATETIME NULL DEFAULT NULL AFTER `received_at`");
    }

    private function createAfterSaleEvidenceTable(): void {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS `after_sale_evidence` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `request_id` INT(11) NOT NULL,
            `image_url` VARCHAR(500) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `after_sale_evidence_request_idx` (`request_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function createOrderSalesRecognitionTable(): void {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS `order_sales_recognition` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL,
            `recognized_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_sales_recognition_order_unique` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function migrateCartReminderColumns(): void {
        if (!$this->tableExists('cart_reminders')) {
            return;
        }

        $this->addColumnIfMissing('cart_reminders', 'unsubscribe_token', "VARCHAR(100) NULL DEFAULT NULL AFTER `user_id`");
        $this->addColumnIfMissing('cart_reminders', 'unsubscribed_at', "DATETIME NULL DEFAULT NULL AFTER `unsubscribe_token`");
        $this->addColumnIfMissing('cart_reminders', 'attempt_count', "INT(11) NOT NULL DEFAULT 0 AFTER `sent_at`");
        $this->addColumnIfMissing('cart_reminders', 'next_attempt_at', "DATETIME NULL DEFAULT NULL AFTER `attempt_count`");
        $this->addColumnIfMissing('cart_reminders', 'last_error', "TEXT NULL DEFAULT NULL AFTER `next_attempt_at`");
        $this->connection->exec("UPDATE cart_reminders SET unsubscribe_token = LOWER(HEX(RANDOM_BYTES(24))) WHERE unsubscribe_token IS NULL OR unsubscribe_token = ''");
        if (!$this->indexExists('cart_reminders', 'cart_reminder_token_unique')) {
            $this->connection->exec("ALTER TABLE `cart_reminders` ADD UNIQUE KEY `cart_reminder_token_unique` (`unsubscribe_token`)");
        }
    }

    private function rebuildSalesCounters(): void {
        if (!$this->tableExists('product') || !$this->tableExists('order_items') || !$this->tableExists('orders')) {
            return;
        }

        $this->connection->exec("UPDATE product SET sold_count = 0, reserved_quantity = 0, returned_count = 0");
        $this->connection->exec("UPDATE product p
            LEFT JOIN (
                SELECT pv.product_id, SUM(oi.quantity) AS quantity
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                JOIN product_variants pv ON pv.id = oi.variant_id
                WHERE o.status IN ('delivered', 'completed')
                GROUP BY pv.product_id
            ) sold ON sold.product_id = p.id
            LEFT JOIN (
                SELECT pv.product_id, SUM(r.sales_reversed_quantity) AS quantity
                FROM after_sale_requests r
                JOIN order_items oi ON oi.id = r.order_item_id
                JOIN product_variants pv ON pv.id = oi.variant_id
                WHERE r.sales_reversed_quantity > 0
                GROUP BY pv.product_id
            ) reversed ON reversed.product_id = p.id
            SET p.sold_count = GREATEST(0, COALESCE(sold.quantity, 0) - COALESCE(reversed.quantity, 0))");
        $this->connection->exec("UPDATE product p
            LEFT JOIN (
                SELECT pv.product_id, SUM(oi.quantity) AS quantity
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                JOIN product_variants pv ON pv.id = oi.variant_id
                WHERE o.status IN ('confirmed', 'preparing', 'shipping')
                GROUP BY pv.product_id
            ) reserved ON reserved.product_id = p.id
            SET p.reserved_quantity = COALESCE(reserved.quantity, 0)");

        if ($this->tableExists('after_sale_requests')) {
            $this->connection->exec("UPDATE product p
                LEFT JOIN (
                    SELECT pv.product_id, SUM(r.approved_quantity) AS quantity
                    FROM after_sale_requests r
                    JOIN order_items oi ON oi.id = r.order_item_id
                    JOIN product_variants pv ON pv.id = oi.variant_id
                    WHERE r.status IN ('received', 'refunded', 'completed')
                      AND r.request_type IN ('return', 'exchange', 'refund')
                    GROUP BY pv.product_id
                ) returned ON returned.product_id = p.id
                SET p.returned_count = COALESCE(returned.quantity, 0)");
        }
    }

    private function normalizeOrderStatusData(): void {
        if (!$this->tableExists('orders') || !$this->columnExists('orders', 'shipping_status')) {
            return;
        }

        // Older data allowed the two status fields to drift apart.  The order
        // workflow is now the source of truth, so repair existing rows once
        // during the idempotent schema upgrade as well.
        $this->connection->exec("UPDATE orders SET shipping_status = CASE status
            WHEN 'pending' THEN 'not_shipped'
            WHEN 'confirmed' THEN 'not_shipped'
            WHEN 'preparing' THEN 'packing'
            WHEN 'shipping' THEN 'in_transit'
            WHEN 'delivered' THEN 'delivered'
            WHEN 'completed' THEN 'delivered'
            WHEN 'canceled' THEN 'canceled'
            ELSE 'not_shipped'
        END");
    }

    private function migrateCouponColumns(): void {
        if (!$this->tableExists('coupons')) {
            return;
        }

        $this->addColumnIfMissing('coupons', 'usage_limit_per_user', "INT(11) NOT NULL DEFAULT 1 AFTER `usage_limit`");
        $this->addColumnIfMissing('coupons', 'category_id', "INT(11) NULL DEFAULT NULL AFTER `expiry_date`");
        $this->addColumnIfMissing('coupons', 'product_id', "INT(11) NULL DEFAULT NULL AFTER `category_id`");
        $this->addColumnIfMissing('coupons', 'status', "TINYINT(4) NOT NULL DEFAULT 1 AFTER `product_id`");

        $this->connection->exec("CREATE TABLE IF NOT EXISTS `coupon_usages` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `coupon_id` INT(11) NOT NULL,
            `user_id` INT(11) NOT NULL,
            `order_id` INT(11) NOT NULL,
            `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `coupon_order_unique` (`coupon_id`, `order_id`),
            KEY `coupon_user_idx` (`coupon_id`, `user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->addForeignKeyIfMissing('coupon_usages', 'coupon_id', 'coupons', 'id', 'coupon_usage_coupon_fk', 'CASCADE');
        $this->addForeignKeyIfMissing('coupon_usages', 'user_id', 'user', 'id', 'coupon_usage_user_fk', 'CASCADE');
        $this->addForeignKeyIfMissing('coupon_usages', 'order_id', 'orders', 'id', 'coupon_usage_order_fk', 'CASCADE');
    }

    private function migrateReviewColumns(): void {
        if (!$this->tableExists('reviews')) {
            return;
        }

        $this->addColumnIfMissing('reviews', 'order_id', "INT(11) NULL DEFAULT NULL AFTER `product_id`");
        $this->addColumnIfMissing('reviews', 'order_item_id', "INT(11) NULL DEFAULT NULL AFTER `order_id`");
        $this->addColumnIfMissing('reviews', 'created_at', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    private function createAfterSaleTables(): void {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS `after_sale_requests` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `order_id` INT(11) NOT NULL,
            `order_item_id` INT(11) NOT NULL,
            `request_type` VARCHAR(30) NOT NULL,
            `reason` TEXT NOT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
            `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `resolution_note` TEXT NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `after_sale_user_idx` (`user_id`),
            KEY `after_sale_order_idx` (`order_id`, `order_item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function createMarketingTables(): void {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS `cart_reminders` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
            `last_seen_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `sent_at` DATETIME NULL DEFAULT NULL,
            `converted_at` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `cart_reminder_user_unique` (`user_id`),
            KEY `cart_reminder_status_idx` (`status`, `last_seen_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Older product imports did not contain any product_variants rows. That
     * made every product detail page render an empty size/color selector and
     * made the cart button unusable. Seed a small, explicit starter catalog
     * only for products that have no variants yet; products already managed by
     * an admin are left untouched.
     */
    private function seedDefaultProductVariants(): void {
        if (!$this->tableExists('product') || !$this->tableExists('product_variants')) {
            return;
        }

        $products = $this->connection->query("SELECT p.id, p.gender, p.type
            FROM product p
            LEFT JOIN product_variants pv ON pv.product_id = p.id
            GROUP BY p.id, p.gender, p.type
            HAVING COUNT(pv.id) = 0")->fetchAll();

        if (!$products) {
            return;
        }

        $insertVariant = $this->connection->prepare('INSERT INTO product_variants (product_id, size, color, stock_quantity, price_modifier) VALUES (:product_id, :size, :color, 0, 0)');
        $insertStockLog = $this->tableExists('inventory_logs')
            ? $this->connection->prepare("INSERT INTO inventory_logs (variant_id, quantity_changed, reason) VALUES (:variant_id, :quantity_changed, 'Tồn đầu kỳ khi đồng bộ phân loại sản phẩm')")
            : null;

        foreach ($products as $product) {
            $type = strtolower((string)($product['type'] ?? ''));
            $isDefaultOnly = strpos($type, 'plush') !== false || strpos($type, 'phụ kiện') !== false;
            $sizes = $isDefaultOnly
                ? ['Mặc định']
                : ((($product['gender'] ?? '') === 'women')
                    ? ['EU 36', 'EU 37', 'EU 38', 'EU 39', 'EU 40', 'EU 41']
                    : ['EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']);
            $colors = $isDefaultOnly ? ['Mặc định'] : ['Black', 'White'];

            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    $insertVariant->execute([
                        'product_id' => (int)$product['id'],
                        'size' => $size,
                        'color' => $color
                    ]);
                    $variantId = (int)$this->connection->lastInsertId();

                    if ($insertStockLog && !$isDefaultOnly) {
                        $insertStockLog->execute([
                            'variant_id' => $variantId,
                            'quantity_changed' => 10
                        ]);
                    }
                }
            }
        }
    }

    private function dropBrokenTriggers(): void {
        // The old trigger referenced order_items.variant_id before that column
        // existed and could double-refund stock. Order owns this transition now.
        $this->connection->exec("DROP TRIGGER IF EXISTS `trg_after_order_canceled`");
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void {
        if (!$this->columnExists($table, $column)) {
            $this->connection->exec("ALTER TABLE `{$table}` ADD `{$column}` {$definition}");
        }
    }

    private function tableExists(string $table): bool {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function indexExists(string $table, string $index): bool {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function dropForeignKeysForColumn(string $table, string $column): void {
        $stmt = $this->connection->prepare("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL");
        $stmt->execute([$table, $column]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $constraint) {
            $this->connection->exec("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function addForeignKeyIfMissing(string $table, string $column, string $referencedTable, string $referencedColumn, string $constraint, string $onDelete): void {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?");
        $stmt->execute([$table, $column, $referencedTable]);
        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }

        $this->connection->exec("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`{$column}`) REFERENCES `{$referencedTable}` (`{$referencedColumn}`) ON DELETE {$onDelete}");
    }
}
