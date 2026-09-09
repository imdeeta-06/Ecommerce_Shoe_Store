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
        // Dùng unix_socket nếu có (MAMP), ngược lại dùng host:port
        if (!empty($config['unix_socket'])) {
            $dsn = "mysql:unix_socket={$config['unix_socket']};dbname={$config['dbname']};charset={$config['charset']}";
        } else {
            $port = $config['port'] ?? 3306;
            $dsn = "mysql:host={$config['host']};port={$port};dbname={$config['dbname']};charset={$config['charset']}";
        }
        try {
            $this->connection = new PDO($dsn, $config['user'], $config['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->exec("SET time_zone = '+07:00'");
            $this->ensureEcommerceSchema();
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.');
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

        // Không chạy DDL/ALTER trong request của khách. Migration phải được
        // chạy chủ động từ CLI với RUN_SCHEMA_MIGRATIONS=1 để lỗi không bị che
        // khuất và schema không rơi vào trạng thái nửa cũ nửa mới.
        $runMigrations = PHP_SAPI === 'cli'
            && in_array(strtolower(trim((string)\App\Core\App::env('RUN_SCHEMA_MIGRATIONS'))), ['1', 'true', 'yes', 'on'], true);
        if (!$runMigrations) {
            self::$schemaReady = true;
            return;
        }

        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `version` VARCHAR(100) NOT NULL,
                `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $this->migrateAuthenticationSchema();
            $this->migrateBusinessIntegritySchema();
            $this->migrateTaxAndComplianceSchema();
            $this->migrateCommerceOperationsSchema();
            $this->migratePayPalSchema();
            $this->migrateProfitSnapshotSchema();
            $this->migrateVariantPresentationSchema();
            $this->cleanupLegacyEmptyCategories();
            $this->migrateInvoiceSequenceSchema();
            $this->migrateCriticalBusinessV9();
            $this->migrateHouseholdSalesInvoiceSchema();
            $this->migrateVatSalesSchema();
            $this->migrateCatalogSourceDisclosure();
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
            error_log('Database migration failed: ' . $e->getMessage());
            throw new \RuntimeException('Migration cơ sở dữ liệu thất bại. Không thể tiếp tục với schema chưa hoàn chỉnh.');
        }
    }

    private function migrateAuthenticationSchema(): void {
        if (!$this->tableExists('user')) {
            return;
        }

        $passwordColumn = $this->connection->prepare("SELECT IS_NULLABLE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user' AND COLUMN_NAME = 'password' LIMIT 1");
        $passwordColumn->execute();
        if (strtoupper((string)$passwordColumn->fetchColumn()) !== 'YES') {
            $this->connection->exec("ALTER TABLE `user` MODIFY `password` VARCHAR(255) NULL");
        }
        $this->addColumnIfMissing('user', 'email_verified', "TINYINT(1) NOT NULL DEFAULT 1 AFTER `password`");
        $this->addColumnIfMissing('user', 'google_id', "VARCHAR(255) NULL DEFAULT NULL AFTER `email_verified`");

        $index = $this->connection->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user' AND INDEX_NAME = 'user_google_id_unique'");
        $index->execute();
        if ((int)$index->fetchColumn() === 0) {
            $this->connection->exec("ALTER TABLE `user` ADD UNIQUE KEY `user_google_id_unique` (`google_id`)");
        }

        $this->connection->exec("CREATE TABLE IF NOT EXISTS `auth_otps` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `purpose` ENUM('email_verification','password_reset') NOT NULL,
            `otp_hash` VARCHAR(255) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `auth_otps_user_purpose_unique` (`user_id`,`purpose`),
            CONSTRAINT `auth_otps_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function migrateVariantPresentationSchema(): void {
        if (!$this->tableExists('product_variants')) {
            return;
        }

        $this->addColumnIfMissing(
            'product_variants',
            'image_url',
            "VARCHAR(500) NULL DEFAULT NULL AFTER `color`"
        );

        $migration = $this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1');
        $migration->execute(['version' => 'catalog_variant_images_v2']);
        if ($migration->fetchColumn()
            || !$this->tableExists('product')
            || !$this->tableExists('product_images')) {
            return;
        }

        // Chỉ chuẩn hóa bộ dữ liệu mẫu 150 sản phẩm đi kèm đồ án. Dữ liệu do
        // quản trị viên tạo sau này không bị một migration tự động sửa màu.
        $products = $this->connection->query("SELECT p.id, p.name, p.product_type,
                (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id
                    ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS image_url
            FROM product p
            WHERE p.id BETWEEN 1000 AND 1149
            ORDER BY p.id")->fetchAll(PDO::FETCH_ASSOC);
        if (count($products) !== 150) {
            return;
        }

        $variants = $this->connection->prepare('SELECT id, stock_quantity, status FROM product_variants WHERE product_id = ? ORDER BY id');
        $updateRegular = $this->connection->prepare('UPDATE product_variants SET color = ?, image_url = ? WHERE product_id = ?');
        $archive = $this->connection->prepare("UPDATE product_variants SET status = 0, stock_quantity = 0,
            color = CONCAT('Ngừng-', id), image_url = NULL WHERE product_id = ? AND id <> ?");
        $keep = $this->connection->prepare('UPDATE product_variants SET status = 1, stock_quantity = ?, color = ?, image_url = ? WHERE id = ?');

        $this->connection->beginTransaction();
        try {
            foreach ($products as $product) {
                $productId = (int)$product['id'];
                $variants->execute([$productId]);
                $rows = $variants->fetchAll(PDO::FETCH_ASSOC);
                if (!$rows) {
                    continue;
                }

                $color = $this->inferSeedVariantColor((string)$product['name']);
                $imageUrl = trim((string)($product['image_url'] ?? '')) ?: null;
                if (in_array($product['product_type'], ['apparel', 'beads'], true)) {
                    $updateRegular->execute([$color, $imageUrl, $productId]);
                    continue;
                }

                $active = array_values(array_filter($rows, static fn($row) => (int)$row['status'] === 1));
                $source = $active ?: $rows;
                $keepId = (int)$source[0]['id'];
                $totalStock = array_sum(array_map(static fn($row) => (int)$row['stock_quantity'], $active));
                $archive->execute([$productId, $keepId]);
                $keep->execute([$totalStock, $color, $imageUrl, $keepId]);
            }

            $this->connection->prepare('INSERT INTO schema_migrations (version) VALUES (?)')
                ->execute(['catalog_variant_images_v2']);
            $this->connection->commit();
        } catch (\Throwable $error) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $error;
        }
    }

    private function inferSeedVariantColor(string $name): string {
        $text = mb_strtolower($name, 'UTF-8');
        $shades = [
            'nâu đen' => 'Nâu đen', 'nâu đất' => 'Nâu đất',
            'vàng đất' => 'Vàng đất', 'vàng cam' => 'Vàng cam',
            'vàng bò' => 'Vàng bò', 'vàng nhạt' => 'Vàng nhạt',
            'trắng ngà' => 'Trắng ngà', 'xanh lục' => 'Xanh lục'
        ];
        foreach ($shades as $needle => $label) {
            if (mb_strpos($text, $needle) !== false) {
                return $label;
            }
        }

        $colors = [
            'lam' => 'Lam', 'xám' => 'Xám', 'nâu' => 'Nâu',
            'vàng' => 'Vàng', 'đỏ' => 'Đỏ', 'trắng' => 'Trắng',
            'tím' => 'Tím', 'hồng' => 'Hồng', 'be' => 'Be'
        ];
        foreach ($colors as $needle => $label) {
            if (preg_match('/(?:^|[\s,–-])' . preg_quote($needle, '/') . '(?:$|[\s,–-])/u', $text)) {
                return $label;
            }
        }
        if (mb_strpos($text, 'gỗ sưa đỏ') !== false) {
            return 'Đỏ';
        }
        if (mb_strpos($text, 'pha lê hồng') !== false) {
            return 'Hồng';
        }
        return 'Theo ảnh';
    }

    private function migrateBusinessIntegritySchema(): void {
        if ($this->tableExists('order_items')) {
            $this->addColumnIfMissing('order_items', 'discount_amount', "DECIMAL(12,2) NULL DEFAULT NULL AFTER `price_at_time`");
        }
        if (!$this->tableExists('after_sale_requests')) {
            return;
        }

        $this->addColumnIfMissing('after_sale_requests', 'replacement_variant_id', "INT UNSIGNED NULL DEFAULT NULL AFTER `sales_reversed_quantity`");
        $this->addColumnIfMissing('after_sale_requests', 'replacement_quantity', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER `replacement_variant_id`");
        $this->addColumnIfMissing('after_sale_requests', 'replacement_processed_quantity', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER `replacement_quantity`");
        $this->addColumnIfMissing('after_sale_requests', 'replacement_shipping_carrier', "VARCHAR(100) NULL DEFAULT NULL AFTER `replacement_processed_quantity`");
        $this->addColumnIfMissing('after_sale_requests', 'replacement_tracking_code', "VARCHAR(120) NULL DEFAULT NULL AFTER `replacement_shipping_carrier`");
        $this->addColumnIfMissing('after_sale_requests', 'replacement_shipped_at', "DATETIME NULL DEFAULT NULL AFTER `replacement_tracking_code`");
        if (!$this->indexExists('after_sale_requests', 'after_sale_replacement_variant_idx')) {
            $this->connection->exec("ALTER TABLE `after_sale_requests` ADD KEY `after_sale_replacement_variant_idx` (`replacement_variant_id`)");
        }
        $this->addForeignKeyIfMissing('after_sale_requests', 'replacement_variant_id', 'product_variants', 'id', 'after_sale_replacement_variant_fk', 'SET NULL');
    }

    private function migrateTaxAndComplianceSchema(): void {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS `product_sources` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `product_id` INT UNSIGNED NOT NULL,
            `source_site` VARCHAR(120) NOT NULL,
            `source_product_url` VARCHAR(1000) NOT NULL,
            `source_name` VARCHAR(500) NOT NULL,
            `usage_note` VARCHAR(500) DEFAULT NULL,
            `imported_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `product_source_url_unique` (`product_id`,`source_product_url`(191)),
            KEY `product_sources_product_idx` (`product_id`),
            CONSTRAINT `product_sources_product_fk` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        if ($this->tableExists('setting')) {
            $settings = [
                'store_name' => 'Liên Hoa - Đồ lam & Pháp phục',
                'store_address' => '123 Đường An Lạc, Phường Bến Thành, Thành phố Hồ Chí Minh (dữ liệu mô phỏng)',
                'store_phone' => '1800 2235',
                'store_email' => 'lienhoashop.pg@gmail.com',
            ];
            $stmt = $this->connection->prepare('INSERT INTO setting (key_name, value) VALUES (:key_name, :value) ON DUPLICATE KEY UPDATE value = VALUES(value)');
            foreach ($settings as $key => $value) {
                $stmt->execute(['key_name' => $key, 'value' => $value]);
            }
        }
        if ($this->tableExists('user')) {
            $this->connection->exec("UPDATE user SET full_name = 'Quản trị viên Liên Hoa', email = 'admin@lienhoa.local'
                WHERE email = 'admin@paceup.local'
                  AND NOT EXISTS (SELECT 1 FROM (SELECT email FROM user) existing_users WHERE existing_users.email = 'admin@lienhoa.local')");
            $this->connection->exec("UPDATE user SET email = 'customer@lienhoa.local'
                WHERE email = 'customer@paceup.local'
                  AND NOT EXISTS (SELECT 1 FROM (SELECT email FROM user) existing_users WHERE existing_users.email = 'customer@lienhoa.local')");
        }
        $this->connection->exec("CREATE TABLE IF NOT EXISTS `newsletter_subscriptions` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(255) NOT NULL,
            `status` ENUM('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
            `consent_version` VARCHAR(50) NOT NULL,
            `consent_text` VARCHAR(500) NOT NULL,
            `source` VARCHAR(100) NOT NULL DEFAULT 'footer',
            `consent_ip` VARCHAR(45) NULL DEFAULT NULL,
            `consented_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `unsubscribed_at` DATETIME NULL DEFAULT NULL,
            `unsubscribe_token` VARCHAR(100) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `newsletter_email_unique` (`email`),
            UNIQUE KEY `newsletter_token_unique` (`unsubscribe_token`),
            KEY `newsletter_status_idx` (`status`,`consented_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->connection->exec("CREATE TABLE IF NOT EXISTS `analytics_events` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `anonymous_session` CHAR(64) NOT NULL,
            `user_id` INT UNSIGNED NULL DEFAULT NULL,
            `event_type` VARCHAR(40) NOT NULL,
            `page_path` VARCHAR(500) NULL DEFAULT NULL,
            `product_id` INT UNSIGNED NULL DEFAULT NULL,
            `order_id` INT UNSIGNED NULL DEFAULT NULL,
            `source` VARCHAR(100) NULL DEFAULT NULL,
            `medium` VARCHAR(100) NULL DEFAULT NULL,
            `campaign` VARCHAR(150) NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `analytics_event_date_idx` (`event_type`,`created_at`),
            KEY `analytics_campaign_idx` (`campaign`,`created_at`),
            KEY `analytics_user_idx` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function migrateCommerceOperationsSchema(): void {
        $version = 'commerce_operations_v1';
        $check = $this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn()) {
            return;
        }

        if ($this->tableExists('product_variants')) {
            $this->addColumnIfMissing('product_variants', 'sku', "VARCHAR(80) NULL AFTER `product_id`");
            $this->addColumnIfMissing('product_variants', 'barcode', "VARCHAR(80) NULL AFTER `sku`");
            $this->addColumnIfMissing('product_variants', 'cost_price', "DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `price_modifier`");
            $this->addColumnIfMissing('product_variants', 'weight_grams', "INT UNSIGNED NOT NULL DEFAULT 500 AFTER `cost_price`");
            $this->addColumnIfMissing('product_variants', 'length_cm', "DECIMAL(8,2) NOT NULL DEFAULT 25.00 AFTER `weight_grams`");
            $this->addColumnIfMissing('product_variants', 'width_cm', "DECIMAL(8,2) NOT NULL DEFAULT 20.00 AFTER `length_cm`");
            $this->addColumnIfMissing('product_variants', 'height_cm', "DECIMAL(8,2) NOT NULL DEFAULT 5.00 AFTER `width_cm`");
            if (!$this->indexExists('product_variants', 'product_variant_sku_unique')) {
                $this->connection->exec('ALTER TABLE product_variants ADD UNIQUE KEY product_variant_sku_unique (sku)');
            }
            if (!$this->indexExists('product_variants', 'product_variant_barcode_unique')) {
                $this->connection->exec('ALTER TABLE product_variants ADD UNIQUE KEY product_variant_barcode_unique (barcode)');
            }
            $this->connection->exec("UPDATE product_variants pv JOIN product p ON p.id = pv.product_id SET
                pv.sku = COALESCE(NULLIF(pv.sku, ''), CONCAT('LH-', pv.product_id, '-', pv.id)),
                pv.barcode = COALESCE(NULLIF(pv.barcode, ''), CONCAT('893', LPAD(pv.id, 10, '0'))),
                pv.cost_price = IF(pv.cost_price > 0, pv.cost_price, ROUND((p.base_price + pv.price_modifier) * 0.55, -3))");
        }
        if ($this->tableExists('orders')) {
            $this->addColumnIfMissing('orders', 'shipping_province', "VARCHAR(100) NULL AFTER `shipping_address`");
            $this->addColumnIfMissing('orders', 'shipping_carrier_code', "VARCHAR(50) NULL AFTER `shipping_province`");
            $this->addColumnIfMissing('orders', 'shipping_weight_grams', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER `shipping_carrier_code`");
        }
        if ($this->tableExists('newsletter_subscriptions')) {
            $this->connection->exec("ALTER TABLE newsletter_subscriptions MODIFY status ENUM('pending','subscribed','unsubscribed') NOT NULL DEFAULT 'pending'");
            $this->addColumnIfMissing('newsletter_subscriptions', 'confirmation_token', "VARCHAR(100) NULL AFTER `unsubscribe_token`");
            $this->addColumnIfMissing('newsletter_subscriptions', 'confirmation_expires_at', "DATETIME NULL AFTER `confirmation_token`");
            $this->addColumnIfMissing('newsletter_subscriptions', 'confirmed_at', "DATETIME NULL AFTER `confirmation_expires_at`");
            if (!$this->indexExists('newsletter_subscriptions', 'newsletter_confirmation_token_unique')) {
                $this->connection->exec('ALTER TABLE newsletter_subscriptions ADD UNIQUE KEY newsletter_confirmation_token_unique (confirmation_token)');
            }
        }

        $this->connection->exec("CREATE TABLE IF NOT EXISTS shipping_rates (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, carrier_code VARCHAR(50) NOT NULL, carrier_name VARCHAR(120) NOT NULL,
            region_code ENUM('hcm','major_city','nationwide') NOT NULL, base_fee DECIMAL(12,2) NOT NULL,
            base_weight_grams INT UNSIGNED NOT NULL DEFAULT 1000, extra_fee_per_500g DECIMAL(12,2) NOT NULL DEFAULT 0,
            volumetric_divisor INT UNSIGNED NOT NULL DEFAULT 5000, free_shipping_threshold DECIMAL(12,2) NOT NULL DEFAULT 0,
            estimated_days VARCHAR(50) NOT NULL, status TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY(id),
            UNIQUE KEY shipping_rate_unique(carrier_code,region_code), KEY shipping_rate_active_idx(status,region_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS suppliers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, supplier_code VARCHAR(50) NOT NULL, name VARCHAR(180) NOT NULL,
            tax_code VARCHAR(30) NULL, contact_name VARCHAR(120) NULL, phone VARCHAR(30) NULL, email VARCHAR(255) NULL,
            address VARCHAR(500) NULL, payment_terms_days INT UNSIGNED NOT NULL DEFAULT 0, status TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), UNIQUE KEY supplier_code_unique(supplier_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, po_code VARCHAR(50) NOT NULL, supplier_id INT UNSIGNED NOT NULL,
            status ENUM('draft','ordered','partially_received','received','canceled') NOT NULL DEFAULT 'draft',
            ordered_at DATETIME NULL, received_at DATETIME NULL, expected_at DATE NULL, note TEXT NULL,
            subtotal DECIMAL(14,2) NOT NULL DEFAULT 0, tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(14,2) NOT NULL DEFAULT 0, created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), UNIQUE KEY purchase_order_code_unique(po_code),
            KEY purchase_order_supplier_idx(supplier_id,status,created_at),
            CONSTRAINT purchase_order_supplier_fk FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
            CONSTRAINT purchase_order_user_fk FOREIGN KEY(created_by) REFERENCES user(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, purchase_order_id INT UNSIGNED NOT NULL, variant_id INT UNSIGNED NOT NULL,
            quantity_ordered INT UNSIGNED NOT NULL, quantity_received INT UNSIGNED NOT NULL DEFAULT 0,
            unit_cost DECIMAL(12,2) NOT NULL, tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0, PRIMARY KEY(id),
            UNIQUE KEY purchase_order_variant_unique(purchase_order_id,variant_id),
            CONSTRAINT purchase_item_order_fk FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
            CONSTRAINT purchase_item_variant_fk FOREIGN KEY(variant_id) REFERENCES product_variants(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS supplier_payables (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, supplier_id INT UNSIGNED NOT NULL, purchase_order_id INT UNSIGNED NOT NULL,
            amount_due DECIMAL(14,2) NOT NULL, amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0, due_date DATE NULL,
            status ENUM('unpaid','partial','paid','void') NOT NULL DEFAULT 'unpaid', last_payment_reference VARCHAR(120) NULL,
            paid_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id),
            UNIQUE KEY supplier_payable_order_unique(purchase_order_id), KEY supplier_payable_supplier_idx(supplier_id,status,due_date),
            CONSTRAINT supplier_payable_supplier_fk FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
            CONSTRAINT supplier_payable_order_fk FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS electronic_invoices (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, order_id INT UNSIGNED NOT NULL, original_invoice_id INT UNSIGNED NULL,
            invoice_type ENUM('original','adjustment') NOT NULL DEFAULT 'original', invoice_series VARCHAR(30) NOT NULL,
            invoice_number INT UNSIGNED NOT NULL, status ENUM('issued','adjusted','canceled') NOT NULL DEFAULT 'issued',
            buyer_name VARCHAR(180) NOT NULL, buyer_tax_code VARCHAR(30) NULL, buyer_address VARCHAR(500) NULL,
            total_amount DECIMAL(14,2) NOT NULL, adjustment_reason VARCHAR(500) NULL, issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            canceled_at DATETIME NULL, created_by INT UNSIGNED NULL, PRIMARY KEY(id),
            UNIQUE KEY electronic_invoice_number_unique(invoice_series,invoice_number),
            KEY electronic_invoice_order_type_idx(order_id,invoice_type), KEY electronic_invoice_period_idx(status,issued_at),
            CONSTRAINT electronic_invoice_order_fk FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT,
            CONSTRAINT electronic_invoice_original_fk FOREIGN KEY(original_invoice_id) REFERENCES electronic_invoices(id) ON DELETE RESTRICT,
            CONSTRAINT electronic_invoice_user_fk FOREIGN KEY(created_by) REFERENCES user(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS electronic_invoice_events (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, invoice_id INT UNSIGNED NOT NULL,
            event_type ENUM('issued','adjusted','canceled') NOT NULL, reason VARCHAR(500) NULL, changed_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), KEY electronic_invoice_event_idx(invoice_id,created_at),
            CONSTRAINT electronic_invoice_event_invoice_fk FOREIGN KEY(invoice_id) REFERENCES electronic_invoices(id) ON DELETE CASCADE,
            CONSTRAINT electronic_invoice_event_user_fk FOREIGN KEY(changed_by) REFERENCES user(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS newsletter_campaigns (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(150) NOT NULL, subject VARCHAR(255) NOT NULL,
            body_html LONGTEXT NOT NULL, target_url VARCHAR(500) NULL, segment ENUM('all','customers','prospects') NOT NULL DEFAULT 'all',
            status ENUM('draft','queued','sending','sent','failed') NOT NULL DEFAULT 'draft', created_by INT UNSIGNED NULL,
            queued_at DATETIME NULL, sent_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY newsletter_campaign_status_idx(status,created_at),
            CONSTRAINT newsletter_campaign_user_fk FOREIGN KEY(created_by) REFERENCES user(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS newsletter_campaign_recipients (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, campaign_id INT UNSIGNED NOT NULL, subscription_id INT UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL, open_token CHAR(64) NOT NULL, click_token CHAR(64) NOT NULL,
            status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending', sent_at DATETIME NULL, opened_at DATETIME NULL,
            clicked_at DATETIME NULL, last_error TEXT NULL, PRIMARY KEY(id), UNIQUE KEY newsletter_recipient_unique(campaign_id,subscription_id),
            UNIQUE KEY newsletter_open_token_unique(open_token), UNIQUE KEY newsletter_click_token_unique(click_token),
            KEY newsletter_recipient_status_idx(campaign_id,status),
            CONSTRAINT newsletter_recipient_campaign_fk FOREIGN KEY(campaign_id) REFERENCES newsletter_campaigns(id) ON DELETE CASCADE,
            CONSTRAINT newsletter_recipient_subscription_fk FOREIGN KEY(subscription_id) REFERENCES newsletter_subscriptions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $rates = [
            ['standard','Giao hàng tiêu chuẩn','hcm',22000,1000,4000,5000,500000,'1–2 ngày'],
            ['standard','Giao hàng tiêu chuẩn','major_city',30000,1000,5000,5000,700000,'2–4 ngày'],
            ['standard','Giao hàng tiêu chuẩn','nationwide',38000,1000,7000,5000,900000,'3–6 ngày'],
            ['express','Giao hàng nhanh','hcm',35000,1000,6000,5000,1000000,'Trong ngày–1 ngày'],
            ['express','Giao hàng nhanh','major_city',48000,1000,8000,5000,1200000,'1–2 ngày'],
            ['express','Giao hàng nhanh','nationwide',65000,1000,10000,5000,1500000,'2–4 ngày']
        ];
        $rateStmt = $this->connection->prepare('INSERT IGNORE INTO shipping_rates (carrier_code,carrier_name,region_code,base_fee,base_weight_grams,extra_fee_per_500g,volumetric_divisor,free_shipping_threshold,estimated_days,status) VALUES (?,?,?,?,?,?,?,?,?,1)');
        foreach ($rates as $rate) $rateStmt->execute($rate);
        $this->connection->exec("INSERT IGNORE INTO suppliers (supplier_code,name,tax_code,contact_name,phone,email,address,payment_terms_days,status) VALUES
            ('NCC-LH-001','Xưởng may An Lạc (mô phỏng)','0311111111-DEMO','Nguyễn An','0901000001','xuongmay@example.test','Thành phố Hồ Chí Minh',30,1),
            ('NCC-LH-002','Hợp tác xã Pháp Duyên (mô phỏng)','0312222222-DEMO','Trần Tâm','0901000002','phapduyen@example.test','Tỉnh Đồng Nai',15,1)");
        $this->connection->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
    }

    private function migrateProfitSnapshotSchema(): void {
        $version = 'commerce_profit_snapshot_v1';
        $check = $this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn()) return;

        if ($this->tableExists('order_items')) {
            $this->addColumnIfMissing('order_items', 'unit_cost_snapshot', "DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `price_at_time`");
            if ($this->tableExists('product_variants')) {
                $this->connection->exec("UPDATE order_items oi
                    LEFT JOIN product_variants pv ON pv.id=oi.variant_id
                    SET oi.unit_cost_snapshot=COALESCE(pv.cost_price,0)
                    WHERE oi.unit_cost_snapshot=0");
            }
        }
        $this->connection->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version'=>$version]);
    }

    private function cleanupLegacyEmptyCategories(): void {
        $version='cleanup_legacy_categories_v1';
        $check=$this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version=:version LIMIT 1');
        $check->execute(['version'=>$version]);
        if($check->fetchColumn()) return;
        if($this->tableExists('categories') && $this->tableExists('product')){
            $this->connection->exec("DELETE c FROM categories c
                LEFT JOIN product p ON p.category_id=c.id
                WHERE p.id IS NULL AND c.name IN ('Running','Skateboarding','Lifestyle','Football','Basketball','Tennis','Training','Slide','Golf')");
        }
        $this->connection->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version'=>$version]);
    }

    private function migrateInvoiceSequenceSchema(): void {
        $version='commerce_invoice_sequence_v1';
        $check=$this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version=:version LIMIT 1');
        $check->execute(['version'=>$version]);
        if($check->fetchColumn()) return;
        $this->connection->exec("CREATE TABLE IF NOT EXISTS document_sequences (
            series VARCHAR(30) NOT NULL, current_number INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(series)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        if($this->tableExists('electronic_invoices')){
            $this->connection->exec("INSERT INTO document_sequences(series,current_number)
                SELECT invoice_series,MAX(invoice_number) FROM electronic_invoices GROUP BY invoice_series
                ON DUPLICATE KEY UPDATE current_number=GREATEST(current_number,VALUES(current_number))");
        }
        $this->connection->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version'=>$version]);
    }

    private function migrateCriticalBusinessV9(): void {
        $version = 'critical_business_v12';
        $check = $this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version=:version LIMIT 1');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn()) return;

        if ($this->tableExists('product_variants')) {
            $this->addColumnIfMissing('product_variants', 'reserved_quantity', "INT UNSIGNED NOT NULL DEFAULT 0 AFTER `stock_quantity`");
        }
        if ($this->tableExists('orders')) {
            $this->addColumnIfMissing('orders', 'reservation_status', "VARCHAR(20) NOT NULL DEFAULT 'none' AFTER `status`");
            $this->addColumnIfMissing('orders', 'reservation_expires_at', "DATETIME NULL AFTER `reservation_status`");
            $this->addColumnIfMissing('orders', 'stock_reserved_at', "DATETIME NULL AFTER `reservation_expires_at`");
            $this->addColumnIfMissing('orders', 'stock_reservation_closed_at', "DATETIME NULL AFTER `stock_reserved_at`");
            if (!$this->indexExists('orders', 'orders_reservation_expiry_idx')) {
                $this->connection->exec('ALTER TABLE orders ADD KEY orders_reservation_expiry_idx (status,reservation_status,reservation_expires_at)');
            }
        }
        if ($this->tableExists('product')) {
            $this->addColumnIfMissing('product', 'unit_name', "VARCHAR(30) NOT NULL DEFAULT 'Cái' AFTER `product_type`");
        }
        if ($this->tableExists('order_items')) {
            $this->addColumnIfMissing('order_items', 'unit_name_snapshot', "VARCHAR(30) NOT NULL DEFAULT 'Cái' AFTER `variant_color_snapshot`");
        }

        $this->connection->exec("CREATE TABLE IF NOT EXISTS payment_refunds (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, order_id INT UNSIGNED NOT NULL, payment_id INT UNSIGNED NOT NULL,
            after_sale_request_id INT UNSIGNED NULL, provider VARCHAR(30) NOT NULL DEFAULT 'manual',
            provider_refund_id VARCHAR(120) NULL, merchant_reference VARCHAR(120) NOT NULL,
            amount_vnd DECIMAL(14,2) NOT NULL, provider_amount DECIMAL(12,2) NULL, provider_currency CHAR(3) NULL,
            status ENUM('pending','completed','failed') NOT NULL DEFAULT 'completed', failure_reason VARCHAR(500) NULL,
            refunded_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), UNIQUE KEY payment_refund_reference_unique(merchant_reference),
            UNIQUE KEY payment_refund_provider_unique(provider_refund_id), KEY payment_refund_order_idx(order_id,created_at),
            CONSTRAINT payment_refund_order_fk FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE RESTRICT,
            CONSTRAINT payment_refund_payment_fk FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
            CONSTRAINT payment_refund_after_sale_fk FOREIGN KEY(after_sale_request_id) REFERENCES after_sale_requests(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->connection->exec("CREATE TABLE IF NOT EXISTS paypal_webhook_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, event_id VARCHAR(100) NOT NULL, event_type VARCHAR(100) NOT NULL,
            resource_id VARCHAR(120) NULL, verification_status VARCHAR(20) NOT NULL, processing_status VARCHAR(20) NOT NULL DEFAULT 'received',
            payload_json LONGTEXT NOT NULL, error_message VARCHAR(500) NULL, processed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id),
            UNIQUE KEY paypal_webhook_event_unique(event_id), KEY paypal_webhook_status_idx(processing_status,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->connection->exec("CREATE TABLE IF NOT EXISTS supplier_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, payable_id INT UNSIGNED NOT NULL, amount DECIMAL(14,2) NOT NULL,
            reference VARCHAR(120) NOT NULL, paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, created_by INT UNSIGNED NULL,
            PRIMARY KEY(id), UNIQUE KEY supplier_payment_reference_unique(reference), KEY supplier_payment_payable_idx(payable_id,paid_at),
            CONSTRAINT supplier_payment_payable_fk FOREIGN KEY(payable_id) REFERENCES supplier_payables(id) ON DELETE RESTRICT,
            CONSTRAINT supplier_payment_user_fk FOREIGN KEY(created_by) REFERENCES user(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->connection->exec("CREATE TABLE IF NOT EXISTS electronic_invoice_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, invoice_id INT UNSIGNED NOT NULL, order_item_id INT UNSIGNED NULL,
            item_name VARCHAR(255) NOT NULL, variant_description VARCHAR(255) NULL, unit_name VARCHAR(30) NOT NULL DEFAULT 'Cái',
            quantity DECIMAL(12,2) NOT NULL, unit_price DECIMAL(14,2) NOT NULL, discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(14,2) NOT NULL, PRIMARY KEY(id), KEY electronic_invoice_item_idx(invoice_id,id),
            CONSTRAINT electronic_invoice_item_invoice_fk FOREIGN KEY(invoice_id) REFERENCES electronic_invoices(id) ON DELETE CASCADE,
            CONSTRAINT electronic_invoice_item_order_item_fk FOREIGN KEY(order_item_id) REFERENCES order_items(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->connection->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, email_hash CHAR(64) NOT NULL, ip_hash CHAR(64) NOT NULL,
            was_successful TINYINT(1) NOT NULL DEFAULT 0, attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id), KEY login_attempt_lookup_idx(email_hash,ip_hash,attempted_at), KEY login_attempt_cleanup_idx(attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if ($this->tableExists('after_sale_requests') && !$this->indexExists('after_sale_requests', 'after_sale_refund_reference_unique')) {
            $this->connection->exec('ALTER TABLE after_sale_requests ADD UNIQUE KEY after_sale_refund_reference_unique (refund_transaction_code)');
        }
        if ($this->tableExists('analytics_events') && !$this->indexExists('analytics_events', 'analytics_purchase_order_unique')) {
            $this->connection->exec('ALTER TABLE analytics_events ADD UNIQUE KEY analytics_purchase_order_unique (event_type,order_id)');
        }

        // Chỉ chạy trong migration CLI: sửa snapshot sold/reserved của dữ liệu
        // cũ để danh sách bán chạy và tồn khả dụng không lệch với đơn hàng.
        $this->rebuildSalesCounters();

        $this->connection->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
    }

    private function migrateHouseholdSalesInvoiceSchema(): void {
        $version = 'household_sales_invoice_v11';
        $check = $this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version=:version LIMIT 1');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn()) return;

        $obsoleteColumns = [
            'product' => ['tax_category', 'tax_rate'],
            'orders' => ['tax_rate', 'taxable_amount', 'tax_amount', 'prices_include_tax'],
            'order_items' => ['tax_category_snapshot', 'tax_rate_snapshot', 'taxable_amount', 'tax_amount'],
            'electronic_invoices' => ['taxable_amount', 'tax_rate', 'tax_amount'],
            'electronic_invoice_items' => ['taxable_amount', 'tax_rate', 'tax_amount'],
        ];
        foreach ($obsoleteColumns as $table => $columns) {
            if (!$this->tableExists($table)) continue;
            foreach ($columns as $column) {
                if ($this->columnExists($table, $column)) {
                    $this->connection->exec("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
                }
            }
        }

        if ($this->tableExists('electronic_invoices')) {
            $this->connection->exec("UPDATE electronic_invoices
                SET invoice_series=CONCAT('2C',DATE_FORMAT(issued_at,'%y'),'DLI')
                WHERE invoice_series REGEXP '^LH[0-9]{2}E$'");
            $this->connection->exec("INSERT INTO document_sequences(series,current_number)
                SELECT invoice_series,MAX(invoice_number) FROM electronic_invoices GROUP BY invoice_series
                ON DUPLICATE KEY UPDATE current_number=GREATEST(current_number,VALUES(current_number))");
        }
        $this->connection->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
    }

    private function migrateVatSalesSchema(): void {
        $version = 'vat_sales_v14';
        $check = $this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version=:version LIMIT 1');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn()) return;

        if ($this->tableExists('product')) {
            $this->addColumnIfMissing('product', 'tax_category', "VARCHAR(30) NOT NULL DEFAULT 'standard_reduced' AFTER `unit_name`");
            $this->addColumnIfMissing('product', 'tax_rate', "DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER `tax_category`");
        }
        if ($this->tableExists('orders')) {
            $this->addColumnIfMissing('orders', 'taxable_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `shipping_fee`");
            $this->addColumnIfMissing('orders', 'tax_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `taxable_amount`");
            $this->addColumnIfMissing('orders', 'non_taxable_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `tax_amount`");
            $this->addColumnIfMissing('orders', 'shipping_tax_category', "VARCHAR(30) NOT NULL DEFAULT 'standard_reduced' AFTER `non_taxable_amount`");
            $this->addColumnIfMissing('orders', 'shipping_tax_rate', "DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER `shipping_tax_category`");
            $this->addColumnIfMissing('orders', 'shipping_tax_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `shipping_tax_rate`");
            $this->addColumnIfMissing('orders', 'prices_include_tax', "TINYINT(1) NOT NULL DEFAULT 1 AFTER `shipping_tax_amount`");
        }
        if ($this->tableExists('order_items')) {
            $this->addColumnIfMissing('order_items', 'tax_category_snapshot', "VARCHAR(30) NOT NULL DEFAULT 'standard_reduced' AFTER `unit_name_snapshot`");
            $this->addColumnIfMissing('order_items', 'tax_rate_snapshot', "DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER `tax_category_snapshot`");
            $this->addColumnIfMissing('order_items', 'taxable_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `tax_rate_snapshot`");
            $this->addColumnIfMissing('order_items', 'tax_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `taxable_amount`");
        }
        if ($this->tableExists('electronic_invoices')) {
            $this->addColumnIfMissing('electronic_invoices', 'taxable_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `buyer_address`");
            $this->addColumnIfMissing('electronic_invoices', 'tax_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `taxable_amount`");
            $this->addColumnIfMissing('electronic_invoices', 'non_taxable_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `tax_amount`");
        }
        if ($this->tableExists('electronic_invoice_items')) {
            $this->addColumnIfMissing('electronic_invoice_items', 'tax_category', "VARCHAR(30) NOT NULL DEFAULT 'standard_reduced' AFTER `unit_name`");
            $this->addColumnIfMissing('electronic_invoice_items', 'tax_rate', "DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER `tax_category`");
            $this->addColumnIfMissing('electronic_invoice_items', 'taxable_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `tax_rate`");
            $this->addColumnIfMissing('electronic_invoice_items', 'tax_amount', "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `taxable_amount`");
        }

        if ($this->tableExists('order_items') && $this->tableExists('product')) {
            $this->connection->exec("UPDATE order_items oi LEFT JOIN product p ON p.id=oi.product_id
                SET oi.tax_category_snapshot=COALESCE(p.tax_category,'standard_reduced'),
                    oi.tax_rate_snapshot=COALESCE(p.tax_rate,8),
                    oi.taxable_amount=CASE WHEN COALESCE(p.tax_category,'standard_reduced')='not_subject' THEN 0 ELSE ROUND(GREATEST(0,oi.price_at_time*oi.quantity-COALESCE(oi.discount_amount,0))/(1+COALESCE(p.tax_rate,8)/100),2) END,
                    oi.tax_amount=CASE WHEN COALESCE(p.tax_category,'standard_reduced')='not_subject' THEN 0 ELSE ROUND(GREATEST(0,oi.price_at_time*oi.quantity-COALESCE(oi.discount_amount,0))-ROUND(GREATEST(0,oi.price_at_time*oi.quantity-COALESCE(oi.discount_amount,0))/(1+COALESCE(p.tax_rate,8)/100),2),2) END");
        }
        if ($this->tableExists('orders') && $this->tableExists('order_items')) {
            $this->connection->exec("UPDATE orders o LEFT JOIN (
                    SELECT order_id,SUM(taxable_amount) taxable_amount,SUM(tax_amount) tax_amount,
                        SUM(CASE WHEN tax_category_snapshot='not_subject' THEN GREATEST(0,price_at_time*quantity-COALESCE(discount_amount,0)) ELSE 0 END) non_taxable_amount
                    FROM order_items GROUP BY order_id
                ) x ON x.order_id=o.id
                SET o.shipping_tax_category='standard_reduced',o.shipping_tax_rate=8,o.shipping_tax_amount=ROUND(o.shipping_fee-ROUND(o.shipping_fee/1.08,2),2),
                    o.taxable_amount=COALESCE(x.taxable_amount,0)+ROUND(o.shipping_fee/1.08,2),
                    o.tax_amount=COALESCE(x.tax_amount,0)+ROUND(o.shipping_fee-ROUND(o.shipping_fee/1.08,2),2),
                    o.non_taxable_amount=COALESCE(x.non_taxable_amount,0),o.prices_include_tax=1");
        }
        if ($this->tableExists('electronic_invoices')) {
            if ($this->tableExists('orders')) {
                $this->connection->exec("UPDATE electronic_invoices i JOIN orders o ON o.id=i.order_id
                    SET i.taxable_amount=o.taxable_amount,i.tax_amount=o.tax_amount,i.non_taxable_amount=o.non_taxable_amount
                    WHERE i.invoice_type='original'");
            }
            $this->connection->exec("UPDATE electronic_invoices SET invoice_series=CONCAT('1C',DATE_FORMAT(issued_at,'%y'),'TLI') WHERE invoice_series REGEXP '^2C[0-9]{2}D'");
            if ($this->tableExists('document_sequences')) {
                $this->connection->exec("INSERT INTO document_sequences(series,current_number)
                    SELECT invoice_series,MAX(invoice_number) FROM electronic_invoices GROUP BY invoice_series
                    ON DUPLICATE KEY UPDATE current_number=GREATEST(current_number,VALUES(current_number))");
            }
        }
        if ($this->tableExists('electronic_invoice_items')) {
            if ($this->tableExists('order_items')) {
                $this->connection->exec("UPDATE electronic_invoice_items ii JOIN order_items oi ON oi.id=ii.order_item_id
                    SET ii.tax_category=oi.tax_category_snapshot,ii.tax_rate=oi.tax_rate_snapshot,
                        ii.taxable_amount=oi.taxable_amount,ii.tax_amount=oi.tax_amount");
            }
            if ($this->tableExists('electronic_invoices') && $this->tableExists('orders')) {
                $this->connection->exec("UPDATE electronic_invoice_items ii
                    JOIN electronic_invoices i ON i.id=ii.invoice_id
                    JOIN orders o ON o.id=i.order_id
                    SET ii.tax_category=o.shipping_tax_category,ii.tax_rate=o.shipping_tax_rate,
                        ii.taxable_amount=GREATEST(0,ii.total_amount-o.shipping_tax_amount),ii.tax_amount=o.shipping_tax_amount
                    WHERE ii.order_item_id IS NULL AND ii.item_name='Phí giao hàng' AND i.invoice_type='original'");
            }
        }

        $this->connection->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
    }

    /**
     * Catalog 150 sản phẩm cũ không còn thông tin URL nguồn ban đầu. Không
     * được bịa quyền sử dụng; thay vào đó lưu bản ghi công khai rằng nguồn
     * chưa xác minh và chỉ được dùng trong phạm vi đồ án học tập.
     */
    private function migrateCatalogSourceDisclosure(): void {
        $version = 'catalog_source_disclosure_v13';
        $check = $this->connection->prepare('SELECT 1 FROM schema_migrations WHERE version=:version LIMIT 1');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn()) {
            return;
        }

        if ($this->tableExists('product') && $this->tableExists('product_sources')) {
            $this->connection->exec("INSERT INTO product_sources
                (product_id, source_site, source_product_url, source_name, usage_note)
                SELECT p.id,
                    'Catalog cũ của đồ án',
                    CONCAT('internal://legacy-catalog/', p.id),
                    p.name,
                    'Nguồn gốc và quyền sử dụng ảnh chưa được xác minh; chỉ dùng để minh họa trong đồ án học tập, không dùng cho kinh doanh thực tế.'
                FROM product p
                LEFT JOIN product_sources ps ON ps.product_id = p.id
                WHERE ps.id IS NULL");
        }

        $this->connection->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')
            ->execute(['version' => $version]);
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
        $this->addColumnIfMissing('orders', 'contract_version', "VARCHAR(30) NOT NULL DEFAULT 'v2.0-2026-08-27' AFTER `terms_accepted_at`");
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
        $this->addColumnIfMissing('payments', 'provider_order_id', "VARCHAR(64) NULL DEFAULT NULL AFTER `transaction_code`");
        $this->addColumnIfMissing('payments', 'provider_capture_id', "VARCHAR(64) NULL DEFAULT NULL AFTER `provider_order_id`");
        $this->addColumnIfMissing('payments', 'provider_currency', "CHAR(3) NULL DEFAULT NULL AFTER `provider_capture_id`");
        $this->addColumnIfMissing('payments', 'provider_amount', "DECIMAL(12,2) NULL DEFAULT NULL AFTER `provider_currency`");
        $this->addColumnIfMissing('payments', 'provider_exchange_rate', "DECIMAL(14,4) NULL DEFAULT NULL AFTER `provider_amount`");
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

    private function migratePayPalSchema(): void {
        if (!$this->tableExists('payments')) {
            return;
        }

        $this->addColumnIfMissing('payments', 'provider_order_id', "VARCHAR(64) NULL DEFAULT NULL AFTER `transaction_code`");
        $this->addColumnIfMissing('payments', 'provider_capture_id', "VARCHAR(64) NULL DEFAULT NULL AFTER `provider_order_id`");
        $this->addColumnIfMissing('payments', 'provider_currency', "CHAR(3) NULL DEFAULT NULL AFTER `provider_capture_id`");
        $this->addColumnIfMissing('payments', 'provider_amount', "DECIMAL(12,2) NULL DEFAULT NULL AFTER `provider_currency`");
        $this->addColumnIfMissing('payments', 'provider_exchange_rate', "DECIMAL(14,4) NULL DEFAULT NULL AFTER `provider_amount`");
        if (!$this->indexExists('payments', 'payments_provider_order_unique')) {
            $this->connection->exec('ALTER TABLE payments ADD UNIQUE KEY payments_provider_order_unique (provider_order_id)');
        }
        if (!$this->indexExists('payments', 'payments_provider_capture_unique')) {
            $this->connection->exec('ALTER TABLE payments ADD UNIQUE KEY payments_provider_capture_unique (provider_capture_id)');
        }
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

        $products = $this->connection->query("SELECT p.id
            FROM product p
            LEFT JOIN product_variants pv ON pv.product_id = p.id
            GROUP BY p.id
            HAVING COUNT(pv.id) = 0")->fetchAll();

        if (!$products) {
            return;
        }

        $insertVariant = $this->connection->prepare('INSERT INTO product_variants (product_id, size, color, stock_quantity, price_modifier) VALUES (:product_id, :size, :color, 0, 0)');
        foreach ($products as $product) {
            $insertVariant->execute([
                'product_id' => (int)$product['id'],
                'size' => 'Free Size',
                'color' => 'Mặc định'
            ]);
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
