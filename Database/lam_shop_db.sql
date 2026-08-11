-- ============================================================================
-- LAM SHOP - DATABASE ĐỘC LẬP CHO WEBSITE BÁN ĐỒ LAM / ĐỒ ĐI CHÙA
-- ============================================================================
-- Mục đích:
--   - Tạo database MỚI tên `lam_shop_db`.
--   - Không ALTER, DROP, USE hoặc ghi dữ liệu vào `paceup_db`.
--   - Dùng cho MariaDB 10.4+ hoặc MySQL 8+.
--
-- Cách dùng:
--   1. Backup database hiện tại trước khi thực hiện bất kỳ thay đổi nào.
--   2. Import RIÊNG file này trong phpMyAdmin/MySQL client.
--   3. Kiểm tra database `lam_shop_db` đã được tạo và có dữ liệu mẫu.
--   4. Chỉ đổi cấu hình PHP sang database mới sau khi đã xem xét và phê duyệt.
--   5. File này là schema + seed cho database mới, không phải migration để chạy
--      lại trên database đang vận hành có dữ liệu thật.
--
-- Lưu ý thiết kế:
--   - Không dùng trigger để tự trừ/cộng kho. Code ứng dụng phải thực hiện
--     cập nhật kho + ghi inventory_logs trong cùng một transaction.
--   - Không xóa cứng sản phẩm, biến thể, danh mục đã có giao dịch. Hãy đổi
--     trạng thái sang inactive/archived để bảo toàn lịch sử đơn hàng.
--   - Mỗi product phải có tối thiểu một product_variant, kể cả hàng một mẫu.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `lam_shop_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `lam_shop_db`;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================================================
-- 1. QUẢN LÝ PHIÊN BẢN SCHEMA VÀ NGƯỜI DÙNG
-- ============================================================================

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` VARCHAR(100) NOT NULL,
  `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `display_name` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `avatar` VARCHAR(500) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff', 'user') NOT NULL DEFAULT 'user' COMMENT 'Vai trò cơ bản; phân quyền chi tiết dùng user_roles.',
  `status` ENUM('active', 'blocked', 'inactive') NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_email_unique` (`email`),
  KEY `user_status_role_idx` (`status`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RBAC: đáp ứng yêu cầu kiểm soát quyền truy cập của chương An ninh.
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(100) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `group_name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_code_unique` (`code`),
  KEY `permissions_group_idx` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `role_permissions_role_fk`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `role_permissions_permission_fk`
    FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `assigned_by` INT UNSIGNED DEFAULT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `role_id`),
  KEY `user_roles_role_idx` (`role_id`),
  CONSTRAINT `user_roles_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_roles_role_fk`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_roles_assigned_by_fk`
    FOREIGN KEY (`assigned_by`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `email_attempted` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(1000) DEFAULT NULL,
  `result` ENUM('success', 'failed', 'blocked') NOT NULL,
  `failure_reason` VARCHAR(255) DEFAULT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `auth_login_attempts_email_time_idx` (`email_attempted`, `attempted_at`),
  KEY `auth_login_attempts_ip_time_idx` (`ip_address`, `attempted_at`),
  CONSTRAINT `auth_login_attempts_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `security_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `event_type` VARCHAR(100) NOT NULL COMMENT 'password_changed, payment_webhook_failed...',
  `severity` ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'info',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(1000) DEFAULT NULL,
  `metadata` LONGTEXT DEFAULT NULL COMMENT 'Chỉ lưu dữ liệu an toàn, không lưu password/OTP/số thẻ.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `security_events_type_time_idx` (`event_type`, `created_at`),
  KEY `security_events_user_time_idx` (`user_id`, `created_at`),
  CONSTRAINT `security_events_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lưu sự đồng ý có phiên bản để tiếp thị và xử lý dữ liệu cá nhân hợp pháp.
CREATE TABLE IF NOT EXISTS `privacy_consents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL COMMENT 'Dùng khi khách chưa có tài khoản',
  `consent_type` ENUM('privacy_policy', 'email_marketing', 'sms_marketing', 'analytics', 'cookies') NOT NULL,
  `policy_version` VARCHAR(50) NOT NULL,
  `is_granted` TINYINT(1) NOT NULL,
  `source` VARCHAR(100) NOT NULL DEFAULT 'website',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(1000) DEFAULT NULL,
  `consented_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `privacy_consents_user_type_idx` (`user_id`, `consent_type`, `consented_at`),
  KEY `privacy_consents_email_type_idx` (`email`, `consent_type`, `consented_at`),
  CONSTRAINT `privacy_consents_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `privacy_consents_owner_check`
    CHECK (`user_id` IS NOT NULL OR `email` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `personal_data_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_code` VARCHAR(50) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `request_type` ENUM('access', 'correction', 'deletion', 'withdraw_consent') NOT NULL,
  `status` ENUM('pending', 'verified', 'processing', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
  `note` TEXT DEFAULT NULL,
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_data_requests_code_unique` (`request_code`),
  KEY `personal_data_requests_status_idx` (`status`, `requested_at`),
  CONSTRAINT `personal_data_requests_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(50) DEFAULT NULL COMMENT 'Ví dụ: Nhà riêng, Công ty',
  `recipient_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `province` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NOT NULL,
  `ward` VARCHAR(100) DEFAULT NULL,
  `address_line` VARCHAR(255) NOT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_addresses_user_idx` (`user_id`, `is_default`),
  CONSTRAINT `user_addresses_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_otp` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `otp_code` VARCHAR(20) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `is_used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `password_reset_email_idx` (`email`, `expires_at`),
  CONSTRAINT `password_reset_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. DANH MỤC, THUỘC TÍNH VÀ CATALOG SẢN PHẨM
-- ============================================================================

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(500) DEFAULT NULL,
  `meta_image_url` VARCHAR(500) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: hiển thị, 0: ẩn',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_status_idx` (`parent_id`, `status`, `sort_order`),
  CONSTRAINT `categories_parent_fk`
    FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `brands` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `website_url` VARCHAR(500) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_name_unique` (`name`),
  UNIQUE KEY `brands_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `catalog_attributes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(80) NOT NULL COMMENT 'Mã kỹ thuật, ví dụ: bead_size_mm',
  `name` VARCHAR(150) NOT NULL,
  `data_type` ENUM('select', 'text', 'number', 'boolean') NOT NULL,
  `unit` VARCHAR(30) DEFAULT NULL COMMENT 'mm, cm, gram... nếu có',
  `scope` ENUM('product', 'variant', 'both') NOT NULL DEFAULT 'both',
  `is_filterable` TINYINT(1) NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catalog_attributes_code_unique` (`code`),
  KEY `catalog_attributes_status_idx` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `catalog_attribute_options` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attribute_id` INT UNSIGNED NOT NULL,
  `value` VARCHAR(150) NOT NULL COMMENT 'Giá trị kỹ thuật không dấu hoặc chuẩn hóa',
  `label` VARCHAR(150) NOT NULL COMMENT 'Nhãn hiển thị cho khách',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attribute_options_unique` (`attribute_id`, `value`),
  KEY `attribute_options_attribute_idx` (`attribute_id`, `status`, `sort_order`),
  CONSTRAINT `attribute_options_attribute_fk`
    FOREIGN KEY (`attribute_id`) REFERENCES `catalog_attributes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `category_attribute_rules` (
  `category_id` INT UNSIGNED NOT NULL,
  `attribute_id` INT UNSIGNED NOT NULL,
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `is_variant_attribute` TINYINT(1) NOT NULL DEFAULT 0,
  `is_filterable` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`category_id`, `attribute_id`),
  KEY `category_attribute_rules_attribute_idx` (`attribute_id`),
  CONSTRAINT `category_attribute_rules_category_fk`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `category_attribute_rules_attribute_fk`
    FOREIGN KEY (`attribute_id`) REFERENCES `catalog_attributes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED DEFAULT NULL COMMENT 'Danh mục chính của sản phẩm',
  `brand_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(500) DEFAULT NULL,
  `meta_image_url` VARCHAR(500) DEFAULT NULL,
  `base_price` DECIMAL(12,2) NOT NULL,
  `compare_at_price` DECIMAL(12,2) DEFAULT NULL,
  `cost_price` DECIMAL(12,2) DEFAULT NULL COMMENT 'Giá vốn, chỉ admin xem',
  `product_type` VARCHAR(50) NOT NULL COMMENT 'apparel, bag, beads, accessory',
  `weight_grams` INT UNSIGNED DEFAULT NULL,
  `sold_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `reserved_quantity` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Tổng số đang giữ cho đơn hợp lệ',
  `returned_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('draft', 'active', 'inactive', 'archived') NOT NULL DEFAULT 'draft',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `published_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_slug_unique` (`slug`),
  KEY `product_catalog_idx` (`category_id`, `status`, `sort_order`),
  KEY `product_brand_idx` (`brand_id`, `status`),
  KEY `product_featured_idx` (`status`, `is_featured`, `sort_order`),
  FULLTEXT KEY `product_search_ft` (`name`, `short_description`, `description`),
  CONSTRAINT `product_category_fk`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `product_brand_fk`
    FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dùng khi một sản phẩm cần xuất hiện ở nhiều danh mục. V1 vẫn có thể chỉ dùng product.category_id.
CREATE TABLE IF NOT EXISTS `product_categories` (
  `product_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`, `category_id`),
  KEY `product_categories_category_idx` (`category_id`, `product_id`),
  CONSTRAINT `product_categories_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_categories_category_fk`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Đối tác cung cấp hàng và chứng từ nguồn gốc giúp kiểm soát hàng hóa hợp pháp.
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `tax_code` VARCHAR(50) DEFAULT NULL,
  `contact_name` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_code_unique` (`supplier_code`),
  KEY `suppliers_status_idx` (`status`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_suppliers` (
  `product_id` INT UNSIGNED NOT NULL,
  `supplier_id` INT UNSIGNED NOT NULL,
  `supplier_product_code` VARCHAR(100) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `last_cost_price` DECIMAL(12,2) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`, `supplier_id`),
  KEY `product_suppliers_supplier_idx` (`supplier_id`, `is_primary`),
  CONSTRAINT `product_suppliers_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_suppliers_supplier_fk`
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `sku` VARCHAR(100) NOT NULL,
  `barcode` VARCHAR(100) DEFAULT NULL,
  `variant_name` VARCHAR(255) NOT NULL COMMENT 'Ví dụ: Size M - Nâu; Hạt 10mm - 108 hạt',
  `variant_key` VARCHAR(255) NOT NULL COMMENT 'Tổ hợp chuẩn hóa, ví dụ: size=m|color=nau',
  `size` VARCHAR(50) DEFAULT NULL COMMENT 'Cột tương thích nhanh cho giao diện áo quần',
  `color` VARCHAR(100) DEFAULT NULL COMMENT 'Cột tương thích nhanh cho giao diện áo quần',
  `price_modifier` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `price_override` DECIMAL(12,2) DEFAULT NULL,
  `cost_price` DECIMAL(12,2) DEFAULT NULL,
  `stock_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `reserved_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `low_stock_threshold` INT UNSIGNED NOT NULL DEFAULT 5,
  `weight_grams` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  UNIQUE KEY `product_variants_product_key_unique` (`product_id`, `variant_key`),
  KEY `product_variants_catalog_idx` (`product_id`, `status`, `stock_quantity`),
  CONSTRAINT `product_variants_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_variants_reserved_check`
    CHECK (`reserved_quantity` <= `stock_quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL nghĩa là ảnh chung của product',
  `image_url` VARCHAR(500) NOT NULL,
  `alt_text` VARCHAR(255) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_images_product_idx` (`product_id`, `is_primary`, `sort_order`),
  KEY `product_images_variant_idx` (`variant_id`),
  CONSTRAINT `product_images_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_images_variant_fk`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thuộc tính dùng chung cho cả product, ví dụ: chất liệu, xuất xứ, hướng dẫn bảo quản.
CREATE TABLE IF NOT EXISTS `product_attribute_values` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `attribute_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED DEFAULT NULL,
  `value_text` TEXT DEFAULT NULL,
  `value_number` DECIMAL(12,2) DEFAULT NULL,
  `value_boolean` TINYINT(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_attribute_unique` (`product_id`, `attribute_id`),
  KEY `product_attribute_filter_idx` (`attribute_id`, `option_id`, `value_number`),
  CONSTRAINT `product_attribute_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_attribute_attribute_fk`
    FOREIGN KEY (`attribute_id`) REFERENCES `catalog_attributes` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `product_attribute_option_fk`
    FOREIGN KEY (`option_id`) REFERENCES `catalog_attribute_options` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thuộc tính thay đổi theo biến thể, ví dụ: size, màu, đường kính hạt, chiều dài.
CREATE TABLE IF NOT EXISTS `variant_attribute_values` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `variant_id` INT UNSIGNED NOT NULL,
  `attribute_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED DEFAULT NULL,
  `value_text` TEXT DEFAULT NULL,
  `value_number` DECIMAL(12,2) DEFAULT NULL,
  `value_boolean` TINYINT(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `variant_attribute_unique` (`variant_id`, `attribute_id`),
  KEY `variant_attribute_filter_idx` (`attribute_id`, `option_id`, `value_number`),
  CONSTRAINT `variant_attribute_variant_fk`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `variant_attribute_attribute_fk`
    FOREIGN KEY (`attribute_id`) REFERENCES `catalog_attributes` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `variant_attribute_option_fk`
    FOREIGN KEY (`option_id`) REFERENCES `catalog_attribute_options` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hạ tầng kho: V1 dùng một kho mặc định. Khi có nhiều kho, inventory_stocks
-- là nguồn dữ liệu tồn theo kho; product_variants.stock_quantity là tổng cache.
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `contact_name` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` VARCHAR(500) NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`),
  KEY `warehouses_status_idx` (`status`, `is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_stocks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED NOT NULL,
  `on_hand_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `reserved_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_stocks_warehouse_variant_unique` (`warehouse_id`, `variant_id`),
  KEY `inventory_stocks_variant_idx` (`variant_id`),
  CONSTRAINT `inventory_stocks_warehouse_fk`
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `inventory_stocks_variant_fk`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `inventory_stocks_reserved_check`
    CHECK (`reserved_quantity` <= `on_hand_quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_code` VARCHAR(50) NOT NULL,
  `supplier_id` INT UNSIGNED NOT NULL,
  `warehouse_id` INT UNSIGNED NOT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('draft', 'ordered', 'partially_received', 'received', 'canceled') NOT NULL DEFAULT 'draft',
  `expected_at` DATETIME DEFAULT NULL,
  `received_at` DATETIME DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `note` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_code_unique` (`purchase_code`),
  KEY `purchase_orders_supplier_status_idx` (`supplier_id`, `status`, `created_at`),
  KEY `purchase_orders_warehouse_status_idx` (`warehouse_id`, `status`),
  CONSTRAINT `purchase_orders_supplier_fk`
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `purchase_orders_warehouse_fk`
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `purchase_orders_user_fk`
    FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_items` INT UNSIGNED NOT NULL,
  `purchase_order_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED NOT NULL,
  `ordered_quantity` INT UNSIGNED NOT NULL,
  `received_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `unit_cost` DECIMAL(12,2) NOT NULL,
  `line_total` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_order_items_unique` (`purchase_order_id`, `variant_id`),
  KEY `purchase_order_items_variant_idx` (`variant_id`),
  CONSTRAINT `purchase_order_items_order_fk`
    FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `purchase_order_items_variant_fk`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `purchase_order_items_received_check`
    CHECK (`received_quantity` <= `ordered_quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_compliance_documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `document_type` ENUM('supplier_invoice', 'origin_certificate', 'quality_certificate', 'trademark_authorization', 'other') NOT NULL,
  `document_number` VARCHAR(100) DEFAULT NULL,
  `issued_at` DATE DEFAULT NULL,
  `expires_at` DATE DEFAULT NULL,
  `file_url` VARCHAR(500) NOT NULL,
  `verification_status` ENUM('pending', 'verified', 'rejected', 'expired') NOT NULL DEFAULT 'pending',
  `verified_by` INT UNSIGNED DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_compliance_product_idx` (`product_id`, `verification_status`),
  CONSTRAINT `product_compliance_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_compliance_verifier_fk`
    FOREIGN KEY (`verified_by`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. GIỎ HÀNG, YÊU THÍCH VÀ KHUYẾN MÃI
-- ============================================================================

CREATE TABLE IF NOT EXISTS `cart` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(100) DEFAULT NULL COMMENT 'Dành cho khách chưa đăng nhập',
  `variant_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_user_variant_unique` (`user_id`, `variant_id`),
  UNIQUE KEY `cart_session_variant_unique` (`session_id`, `variant_id`),
  KEY `cart_variant_idx` (`variant_id`),
  CONSTRAINT `cart_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cart_variant_fk`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cart_owner_check`
    CHECK ((`user_id` IS NOT NULL AND `session_id` IS NULL) OR (`user_id` IS NULL AND `session_id` IS NOT NULL)),
  CONSTRAINT `cart_quantity_check`
    CHECK (`quantity` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlist_user_product_unique` (`user_id`, `product_id`),
  KEY `wishlist_product_idx` (`product_id`),
  CONSTRAINT `wishlist_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wishlist_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `discount_type` ENUM('percent', 'fixed', 'free_shipping') NOT NULL,
  `discount_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(12,2) DEFAULT NULL,
  `min_order_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `usage_limit` INT UNSIGNED DEFAULT NULL COMMENT 'NULL nghĩa là không giới hạn',
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `per_user_limit` INT UNSIGNED DEFAULT NULL,
  `start_at` DATETIME DEFAULT NULL,
  `end_at` DATETIME DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`),
  KEY `coupons_validity_idx` (`status`, `start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu cho chiến dịch tiếp thị và đo lường phễu marketing.
CREATE TABLE IF NOT EXISTS `marketing_campaigns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(80) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `objective` ENUM('awareness', 'interest', 'consideration', 'conversion', 'retention') NOT NULL,
  `channel` ENUM('website', 'email', 'social', 'search_ads', 'display_ads', 'other') NOT NULL,
  `budget_amount` DECIMAL(12,2) DEFAULT NULL,
  `start_at` DATETIME DEFAULT NULL,
  `end_at` DATETIME DEFAULT NULL,
  `status` ENUM('draft', 'active', 'paused', 'completed', 'archived') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marketing_campaigns_code_unique` (`code`),
  KEY `marketing_campaigns_status_date_idx` (`status`, `start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_products` (
  `campaign_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`campaign_id`, `product_id`),
  KEY `campaign_products_product_idx` (`product_id`),
  CONSTRAINT `campaign_products_campaign_fk`
    FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `campaign_products_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_coupons` (
  `campaign_id` INT UNSIGNED NOT NULL,
  `coupon_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`campaign_id`, `coupon_id`),
  KEY `campaign_coupons_coupon_idx` (`coupon_id`),
  CONSTRAINT `campaign_coupons_campaign_fk`
    FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `campaign_coupons_coupon_fk`
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_segments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(80) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_segments_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_segment_members` (
  `segment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`segment_id`, `user_id`),
  KEY `customer_segment_members_user_idx` (`user_id`),
  CONSTRAINT `customer_segment_members_segment_fk`
    FOREIGN KEY (`segment_id`) REFERENCES `customer_segments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `customer_segment_members_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chính sách/điều khoản được version hóa để khách có thể xem lại bản đã chấp nhận.
CREATE TABLE IF NOT EXISTS `legal_documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_type` ENUM('terms', 'privacy', 'shipping', 'return_refund', 'warranty', 'payment', 'dispute_resolution') NOT NULL,
  `version` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `content_hash` CHAR(64) NOT NULL COMMENT 'SHA-256 của nội dung được công bố',
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `effective_at` DATETIME DEFAULT NULL,
  `published_at` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `legal_documents_version_unique` (`document_type`, `version`),
  KEY `legal_documents_status_idx` (`document_type`, `status`, `effective_at`),
  CONSTRAINT `legal_documents_author_fk`
    FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. ĐƠN HÀNG, THANH TOÁN VÀ HẬU MÃI
-- ============================================================================

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(50) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL cho đơn hàng khách vãng lai',
  `coupon_id` INT UNSIGNED DEFAULT NULL,
  `coupon_code_snapshot` VARCHAR(50) DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `final_amount` DECIMAL(12,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'VND',
  `shipping_name` VARCHAR(150) NOT NULL,
  `shipping_phone` VARCHAR(30) NOT NULL,
  `shipping_email` VARCHAR(255) DEFAULT NULL,
  `shipping_province` VARCHAR(100) NOT NULL,
  `shipping_district` VARCHAR(100) NOT NULL,
  `shipping_ward` VARCHAR(100) DEFAULT NULL,
  `shipping_address` VARCHAR(255) NOT NULL,
  `shipping_postal_code` VARCHAR(20) DEFAULT NULL,
  `shipping_carrier` VARCHAR(100) DEFAULT NULL COMMENT 'Tóm tắt; chi tiết nằm ở shipments.',
  `tracking_code` VARCHAR(100) DEFAULT NULL COMMENT 'Tóm tắt; chi tiết nằm ở shipments.',
  `shipping_status` ENUM('not_shipped', 'preparing', 'shipped', 'delivered', 'failed', 'returned') NOT NULL DEFAULT 'not_shipped' COMMENT 'Tóm tắt; chi tiết nằm ở shipments.',
  `customer_note` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'confirmed', 'preparing', 'shipping', 'delivered', 'completed', 'canceled') NOT NULL DEFAULT 'pending',
  `terms_accepted` TINYINT(1) NOT NULL DEFAULT 0,
  `terms_accepted_at` DATETIME DEFAULT NULL,
  `contract_version` VARCHAR(30) NOT NULL DEFAULT 'v1.0',
  `terms_accepted_ip` VARCHAR(45) DEFAULT NULL,
  `terms_accepted_user_agent` VARCHAR(1000) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` DATETIME DEFAULT NULL,
  `shipped_at` DATETIME DEFAULT NULL,
  `delivered_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `canceled_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_code_unique` (`order_code`),
  KEY `orders_user_created_idx` (`user_id`, `created_at`),
  KEY `orders_status_created_idx` (`status`, `created_at`),
  KEY `orders_coupon_idx` (`coupon_id`),
  CONSTRAINT `orders_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `orders_coupon_fk`
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `variant_id` INT UNSIGNED DEFAULT NULL,
  `product_name_snapshot` VARCHAR(255) NOT NULL,
  `variant_name_snapshot` VARCHAR(255) DEFAULT NULL,
  `variant_attributes_snapshot` LONGTEXT DEFAULT NULL COMMENT 'JSON/text thuộc tính tại thời điểm mua',
  `sku_snapshot` VARCHAR(100) DEFAULT NULL,
  `image_snapshot` VARCHAR(500) DEFAULT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `line_total` DECIMAL(12,2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_items_order_idx` (`order_id`),
  KEY `order_items_product_idx` (`product_id`),
  KEY `order_items_variant_idx` (`variant_id`),
  CONSTRAINT `order_items_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_items_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `order_items_variant_fk`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `order_items_quantity_check`
    CHECK (`quantity` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_status_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `changed_by` INT UNSIGNED DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_status_logs_order_idx` (`order_id`, `created_at`),
  KEY `order_status_logs_user_idx` (`changed_by`),
  CONSTRAINT `order_status_logs_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_status_logs_user_fk`
    FOREIGN KEY (`changed_by`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_legal_acceptances` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `legal_document_id` INT UNSIGNED DEFAULT NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `document_version_snapshot` VARCHAR(50) NOT NULL,
  `document_hash_snapshot` CHAR(64) NOT NULL,
  `accepted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_legal_acceptances_unique` (`order_id`, `document_type`, `document_version_snapshot`),
  KEY `order_legal_acceptances_document_idx` (`legal_document_id`),
  CONSTRAINT `order_legal_acceptances_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_legal_acceptances_document_fk`
    FOREIGN KEY (`legal_document_id`) REFERENCES `legal_documents` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lưu yêu cầu sửa thông tin hoặc hủy đơn để xử lý lỗi nhập sai trên môi trường mạng.
CREATE TABLE IF NOT EXISTS `order_change_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_code` VARCHAR(50) NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `request_type` ENUM('correct_information', 'cancel_order') NOT NULL,
  `requested_changes` LONGTEXT DEFAULT NULL COMMENT 'JSON/text thông tin trước và sau khi khách yêu cầu sửa',
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'completed', 'canceled') NOT NULL DEFAULT 'pending',
  `handled_by` INT UNSIGNED DEFAULT NULL,
  `resolution_note` TEXT DEFAULT NULL,
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `handled_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_change_requests_code_unique` (`request_code`),
  KEY `order_change_requests_order_idx` (`order_id`, `status`, `requested_at`),
  CONSTRAINT `order_change_requests_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `order_change_requests_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `order_change_requests_handler_fk`
    FOREIGN KEY (`handled_by`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipping_carriers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `website_url` VARCHAR(500) DEFAULT NULL,
  `tracking_url_template` VARCHAR(500) DEFAULT NULL COMMENT 'Ví dụ: https://.../{tracking_code}',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipping_carriers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_code` VARCHAR(50) NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `carrier_id` INT UNSIGNED DEFAULT NULL,
  `warehouse_id` INT UNSIGNED DEFAULT NULL,
  `tracking_code` VARCHAR(100) DEFAULT NULL,
  `shipping_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `recipient_name_snapshot` VARCHAR(150) NOT NULL,
  `recipient_phone_snapshot` VARCHAR(30) NOT NULL,
  `recipient_address_snapshot` VARCHAR(500) NOT NULL,
  `status` ENUM('pending', 'packed', 'picked_up', 'in_transit', 'delivered', 'delivery_failed', 'returned', 'canceled') NOT NULL DEFAULT 'pending',
  `shipped_at` DATETIME DEFAULT NULL,
  `delivered_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipments_code_unique` (`shipment_code`),
  UNIQUE KEY `shipments_tracking_unique` (`carrier_id`, `tracking_code`),
  KEY `shipments_order_status_idx` (`order_id`, `status`),
  CONSTRAINT `shipments_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `shipments_carrier_fk`
    FOREIGN KEY (`carrier_id`) REFERENCES `shipping_carriers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `shipments_warehouse_fk`
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shipment_status_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(30) NOT NULL,
  `location_note` VARCHAR(500) DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `occurred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `shipment_status_logs_shipment_idx` (`shipment_id`, `occurred_at`),
  CONSTRAINT `shipment_status_logs_shipment_fk`
    FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `payment_method` ENUM('cod', 'bank_transfer', 'momo', 'vnpay', 'zalopay', 'other') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_state` ENUM('pending', 'paid', 'failed', 'canceled', 'refund_pending', 'refunded') NOT NULL DEFAULT 'pending',
  `transaction_code` VARCHAR(120) DEFAULT NULL,
  `gateway_code` VARCHAR(50) DEFAULT NULL COMMENT 'Ví dụ: vnpay, momo, zalopay',
  `gateway_reference` VARCHAR(150) DEFAULT NULL COMMENT 'Mã tham chiếu do cổng thanh toán trả về',
  `gateway_response_hash` CHAR(64) DEFAULT NULL COMMENT 'Hash payload để đối soát, không lưu số thẻ/CVV/OTP.',
  `paid_at` DATETIME DEFAULT NULL,
  `failed_at` DATETIME DEFAULT NULL,
  `refunded_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `refund_transaction_code` VARCHAR(120) DEFAULT NULL,
  `refunded_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_transaction_unique` (`transaction_code`),
  UNIQUE KEY `payments_gateway_reference_unique` (`gateway_code`, `gateway_reference`),
  KEY `payments_order_idx` (`order_id`, `payment_state`),
  CONSTRAINT `payments_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Một payment có thể có nhiều lần thử, tránh ghi đè khi khách thanh toán lại.
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` INT UNSIGNED NOT NULL,
  `merchant_request_code` VARCHAR(120) NOT NULL,
  `provider_transaction_code` VARCHAR(150) DEFAULT NULL,
  `transaction_type` ENUM('authorize', 'capture', 'payment', 'refund') NOT NULL DEFAULT 'payment',
  `amount` DECIMAL(12,2) NOT NULL,
  `status` ENUM('initiated', 'pending', 'succeeded', 'failed', 'canceled') NOT NULL DEFAULT 'initiated',
  `failure_code` VARCHAR(100) DEFAULT NULL,
  `failure_message` VARCHAR(500) DEFAULT NULL,
  `response_hash` CHAR(64) DEFAULT NULL COMMENT 'Hash dữ liệu phản hồi, không lưu dữ liệu thẻ/OTP.',
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_transactions_request_unique` (`merchant_request_code`),
  UNIQUE KEY `payment_transactions_provider_unique` (`provider_transaction_code`),
  KEY `payment_transactions_payment_idx` (`payment_id`, `status`, `requested_at`),
  CONSTRAINT `payment_transactions_payment_fk`
    FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_webhook_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gateway_code` VARCHAR(50) NOT NULL,
  `gateway_event_id` VARCHAR(150) NOT NULL,
  `payment_transaction_id` INT UNSIGNED DEFAULT NULL,
  `payload_hash` CHAR(64) NOT NULL,
  `signature_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `processing_status` ENUM('received', 'processed', 'ignored', 'failed') NOT NULL DEFAULT 'received',
  `processing_error` TEXT DEFAULT NULL,
  `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_webhook_events_unique` (`gateway_code`, `gateway_event_id`),
  KEY `payment_webhook_events_transaction_idx` (`payment_transaction_id`, `processing_status`),
  CONSTRAINT `payment_webhook_events_transaction_fk`
    FOREIGN KEY (`payment_transaction_id`) REFERENCES `payment_transactions` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(100) NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `buyer_name` VARCHAR(255) DEFAULT NULL,
  `buyer_tax_code` VARCHAR(50) DEFAULT NULL,
  `buyer_email` VARCHAR(255) DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `status` ENUM('draft', 'issued', 'canceled') NOT NULL DEFAULT 'draft',
  `issued_at` DATETIME DEFAULT NULL,
  `invoice_url` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_number_unique` (`invoice_number`),
  UNIQUE KEY `invoices_order_unique` (`order_id`),
  CONSTRAINT `invoices_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupon_usages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupon_usages_coupon_order_unique` (`coupon_id`, `order_id`),
  KEY `coupon_usages_user_idx` (`coupon_id`, `user_id`, `used_at`),
  CONSTRAINT `coupon_usages_coupon_fk`
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `coupon_usages_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `coupon_usages_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `after_sale_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_code` VARCHAR(50) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `order_item_id` INT UNSIGNED NOT NULL,
  `request_type` ENUM('return', 'refund', 'exchange') NOT NULL,
  `reason` TEXT NOT NULL,
  `requested_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `approved_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `restockable` TINYINT(1) NOT NULL DEFAULT 1,
  `inventory_processed_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `refund_status` ENUM('not_requested', 'pending', 'processed', 'failed') NOT NULL DEFAULT 'not_requested',
  `refund_transaction_code` VARCHAR(120) DEFAULT NULL,
  `status` ENUM('requested', 'approved', 'rejected', 'returning', 'received', 'refunded', 'completed', 'canceled') NOT NULL DEFAULT 'requested',
  `resolution_note` TEXT DEFAULT NULL,
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at` DATETIME DEFAULT NULL,
  `received_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `after_sale_requests_code_unique` (`request_code`),
  KEY `after_sale_user_idx` (`user_id`, `requested_at`),
  KEY `after_sale_order_idx` (`order_id`, `order_item_id`),
  KEY `after_sale_status_idx` (`status`, `requested_at`),
  CONSTRAINT `after_sale_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `after_sale_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `after_sale_order_item_fk`
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `after_sale_evidence` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED NOT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `after_sale_evidence_request_idx` (`request_id`),
  CONSTRAINT `after_sale_evidence_request_fk`
    FOREIGN KEY (`request_id`) REFERENCES `after_sale_requests` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. KHO, ĐÁNH GIÁ VÀ BÁO CÁO
-- ============================================================================

CREATE TABLE IF NOT EXISTS `inventory_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_id` INT UNSIGNED DEFAULT NULL,
  `variant_id` INT UNSIGNED DEFAULT NULL,
  `movement_type` ENUM('initial', 'purchase', 'sale', 'reserve', 'release', 'return', 'adjustment', 'damage') NOT NULL,
  `quantity_changed` INT NOT NULL COMMENT 'Dương: tăng kho, âm: giảm kho',
  `quantity_before` INT UNSIGNED DEFAULT NULL,
  `quantity_after` INT UNSIGNED DEFAULT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'order, return, manual...',
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `reason` VARCHAR(500) DEFAULT NULL,
  `changed_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_logs_warehouse_created_idx` (`warehouse_id`, `created_at`),
  KEY `inventory_logs_variant_created_idx` (`variant_id`, `created_at`),
  KEY `inventory_logs_reference_idx` (`reference_type`, `reference_id`),
  KEY `inventory_logs_changed_by_idx` (`changed_by`),
  CONSTRAINT `inventory_logs_variant_fk`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `inventory_logs_warehouse_fk`
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `inventory_logs_changed_by_fk`
    FOREIGN KEY (`changed_by`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `order_item_id` INT UNSIGNED DEFAULT NULL COMMENT 'Chỉ review khi đã mua hàng',
  `rating` TINYINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'hidden') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_order_item_unique` (`order_item_id`),
  KEY `reviews_product_status_idx` (`product_id`, `status`, `created_at`),
  KEY `reviews_user_idx` (`user_id`, `created_at`),
  CONSTRAINT `reviews_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `reviews_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `reviews_order_item_fk`
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `reviews_rating_check`
    CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `review_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `review_id` INT UNSIGNED NOT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `review_images_review_idx` (`review_id`, `sort_order`),
  CONSTRAINT `review_images_review_fk`
    FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_revenue_reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_date` DATE NOT NULL,
  `total_orders` INT UNSIGNED NOT NULL DEFAULT 0,
  `gross_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `shipping_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `refunded_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `net_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_revenue_reports_date_unique` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_sales_reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_date` DATE NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `variant_id` INT UNSIGNED NOT NULL,
  `quantity_sold` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_sales_reports_unique` (`report_date`, `product_id`, `variant_id`),
  KEY `product_sales_reports_product_idx` (`product_id`, `report_date`),
  CONSTRAINT `product_sales_reports_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `product_sales_reports_variant_fk`
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_sales_recognition` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `recognized_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_sales_recognition_order_unique` (`order_id`),
  CONSTRAINT `order_sales_recognition_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. NỘI DUNG, HỖ TRỢ KHÁCH HÀNG, THÔNG BÁO VÀ NHẬT KÝ
-- ============================================================================

CREATE TABLE IF NOT EXISTS `post_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `author_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `excerpt` VARCHAR(500) DEFAULT NULL,
  `content` LONGTEXT NOT NULL,
  `thumbnail` VARCHAR(500) DEFAULT NULL,
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(500) DEFAULT NULL,
  `meta_image_url` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_category_status_idx` (`category_id`, `status`, `published_at`),
  FULLTEXT KEY `posts_search_ft` (`title`, `excerpt`, `content`),
  CONSTRAINT `posts_category_fk`
    FOREIGN KEY (`category_id`) REFERENCES `post_categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `posts_author_fk`
    FOREIGN KEY (`author_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `banner` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) DEFAULT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `alt_text` VARCHAR(255) DEFAULT NULL,
  `link_url` VARCHAR(500) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `start_at` DATETIME DEFAULT NULL,
  `end_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `banner_display_idx` (`status`, `start_at`, `end_at`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `setting` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key_name` VARCHAR(100) NOT NULL,
  `value` LONGTEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key_unique` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--  thông tin đơn vị bán hàng và giấy phép để công bố minh bạch trên website.
CREATE TABLE IF NOT EXISTS `merchant_profiles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `legal_name` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(255) NOT NULL,
  `business_registration_number` VARCHAR(100) DEFAULT NULL,
  `tax_code` VARCHAR(50) DEFAULT NULL,
  `legal_representative` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `address` VARCHAR(500) NOT NULL,
  -- Dùng để website nhúng Google Maps theo vị trí cửa hàng được cấu hình.
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `google_maps_url` VARCHAR(500) DEFAULT NULL,
  `website_url` VARCHAR(500) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `merchant_documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `merchant_id` INT UNSIGNED NOT NULL,
  `document_type` ENUM('business_license', 'tax_registration', 'website_notification', 'other') NOT NULL,
  `document_number` VARCHAR(100) DEFAULT NULL,
  `issued_at` DATE DEFAULT NULL,
  `expires_at` DATE DEFAULT NULL,
  `file_url` VARCHAR(500) NOT NULL,
  `status` ENUM('active', 'expired', 'archived') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `merchant_documents_merchant_idx` (`merchant_id`, `document_type`, `status`),
  CONSTRAINT `merchant_documents_merchant_fk`
    FOREIGN KEY (`merchant_id`) REFERENCES `merchant_profiles` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chỉ ghi marketing_events khi người dùng đã đồng ý analytics/cookies phù hợp.
-- visitor_key là mã ngẫu nhiên hoặc hash, không lưu dữ liệu thẻ/OTP/mật khẩu.
CREATE TABLE IF NOT EXISTS `marketing_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_name` ENUM('page_view', 'product_view', 'search', 'add_to_cart', 'begin_checkout', 'purchase', 'wishlist_add', 'review_submit') NOT NULL,
  `visitor_key` VARCHAR(128) DEFAULT NULL,
  `session_id` VARCHAR(100) DEFAULT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `campaign_id` INT UNSIGNED DEFAULT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `utm_source` VARCHAR(100) DEFAULT NULL,
  `utm_medium` VARCHAR(100) DEFAULT NULL,
  `utm_campaign` VARCHAR(100) DEFAULT NULL,
  `event_value` DECIMAL(12,2) DEFAULT NULL COMMENT 'Ví dụ doanh thu purchase',
  `occurred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `marketing_events_funnel_idx` (`event_name`, `occurred_at`),
  KEY `marketing_events_user_idx` (`user_id`, `occurred_at`),
  KEY `marketing_events_campaign_idx` (`campaign_id`, `occurred_at`),
  KEY `marketing_events_product_idx` (`product_id`, `occurred_at`),
  CONSTRAINT `marketing_events_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `marketing_events_campaign_fk`
    FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `marketing_events_product_fk`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `marketing_events_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_code` VARCHAR(40) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('pending', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
  `auto_reply_status` ENUM('pending', 'sent', 'failed', 'skipped') NOT NULL DEFAULT 'pending',
  `auto_reply_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `auto_reply_next_attempt_at` DATETIME DEFAULT NULL,
  `auto_reply_sent_at` DATETIME DEFAULT NULL,
  `auto_reply_last_error` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `support_tickets_code_unique` (`ticket_code`),
  KEY `support_tickets_status_idx` (`status`, `created_at`),
  KEY `support_tickets_reply_idx` (`auto_reply_status`, `auto_reply_next_attempt_at`),
  CONSTRAINT `support_tickets_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `recipient_email` VARCHAR(255) NOT NULL,
  `notification_type` VARCHAR(60) NOT NULL COMMENT 'order_created, order_confirmed...',
  `subject` VARCHAR(255) NOT NULL,
  `body_html` LONGTEXT NOT NULL,
  `status` ENUM('pending', 'sent', 'failed', 'skipped') NOT NULL DEFAULT 'pending',
  `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `next_attempt_at` DATETIME DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `last_error` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_notifications_unique` (`order_id`, `notification_type`),
  KEY `order_notifications_queue_idx` (`status`, `next_attempt_at`, `attempt_count`),
  CONSTRAINT `order_notifications_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_notifications_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart_reminders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `unsubscribe_token` VARCHAR(100) NOT NULL,
  `status` ENUM('pending', 'sent', 'failed', 'converted', 'unsubscribed') NOT NULL DEFAULT 'pending',
  `last_seen_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME DEFAULT NULL,
  `converted_at` DATETIME DEFAULT NULL,
  `unsubscribed_at` DATETIME DEFAULT NULL,
  `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `next_attempt_at` DATETIME DEFAULT NULL,
  `last_error` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cart_reminders_user_unique` (`user_id`),
  UNIQUE KEY `cart_reminders_token_unique` (`unsubscribe_token`),
  KEY `cart_reminders_queue_idx` (`status`, `next_attempt_at`, `attempt_count`),
  CONSTRAINT `cart_reminders_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(150) NOT NULL,
  `metadata` LONGTEXT DEFAULT NULL COMMENT 'JSON/text phục vụ audit',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `logs_user_created_idx` (`user_id`, `created_at`),
  CONSTRAINT `logs_user_fk`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `custom_notes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(50) NOT NULL COMMENT 'order, user, product...',
  `entity_id` INT UNSIGNED NOT NULL,
  `note_content` TEXT NOT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `custom_notes_entity_idx` (`entity_type`, `entity_id`),
  CONSTRAINT `custom_notes_user_fk`
    FOREIGN KEY (`created_by`) REFERENCES `user` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. DỮ LIỆU MẪU: DANH MỤC VÀ THUỘC TÍNH
-- Các ID cố định chỉ dùng để tạo một database demo mới, không import vào
-- database cũ vì có thể trùng ID.
-- ============================================================================

INSERT INTO `roles` (`id`, `code`, `name`, `description`, `status`) VALUES
  (1, 'admin', 'Quản trị viên', 'Toàn quyền quản trị hệ thống.', 1),
  (2, 'staff', 'Nhân viên vận hành', 'Quản lý đơn, kho và nội dung theo quyền được cấp.', 1),
  (3, 'customer', 'Khách hàng', 'Mua hàng và quản lý dữ liệu cá nhân của chính mình.', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `status` = VALUES(`status`);

INSERT INTO `permissions` (`id`, `code`, `name`, `group_name`, `description`) VALUES
  (1, 'catalog.manage', 'Quản lý danh mục và sản phẩm', 'catalog', NULL),
  (2, 'inventory.manage', 'Quản lý tồn kho', 'inventory', NULL),
  (3, 'orders.manage', 'Quản lý đơn hàng', 'orders', NULL),
  (4, 'payments.view', 'Xem và đối soát thanh toán', 'payments', NULL),
  (5, 'customers.manage', 'Quản lý khách hàng', 'customers', NULL),
  (6, 'promotions.manage', 'Quản lý khuyến mãi', 'marketing', NULL),
  (7, 'content.manage', 'Quản lý bài viết và banner', 'content', NULL),
  (8, 'reports.view', 'Xem báo cáo', 'reports', NULL),
  (9, 'settings.manage', 'Quản lý cấu hình và thông tin pháp lý', 'settings', NULL),
  (10, 'security.manage', 'Xem nhật ký và cấu hình an ninh', 'security', NULL),
  (11, 'suppliers.manage', 'Quản lý nhà cung cấp và phiếu nhập', 'supply', NULL),
  (12, 'after_sales.manage', 'Quản lý đổi trả và hoàn tiền', 'after_sales', NULL)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `group_name` = VALUES(`group_name`), `description` = VALUES(`description`);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES
  (2, 1), (2, 2), (2, 3), (2, 4), (2, 6), (2, 7), (2, 8), (2, 11), (2, 12);

-- Không seed tài khoản admin/password để tránh có thông tin đăng nhập mặc định.
-- Khi triển khai, tạo admin bằng script riêng và gán user_roles tương ứng.

INSERT INTO `warehouses` (`id`, `code`, `name`, `address`, `status`, `is_default`) VALUES
  (1, 'KHO-MAC-DINH', 'Kho mặc định', 'Cập nhật địa chỉ kho thật trước khi vận hành.', 'active', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `address` = VALUES(`address`), `status` = VALUES(`status`), `is_default` = VALUES(`is_default`);

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `status`, `sort_order`) VALUES
  (1, NULL, 'Quần áo', 'quan-ao', 'Các sản phẩm quần áo mặc đi chùa, ngồi thiền và dành cho Tăng - Ni.', 1, 10),
  (2, 1, 'Quần áo Tăng - Ni', 'quan-ao-tang-ni', 'Trang phục dành cho Tăng - Ni.', 1, 11),
  (3, 1, 'Đồ lam đi chùa', 'do-lam-di-chua', 'Trang phục lam lịch sự, trang nhã khi đi chùa.', 1, 12),
  (4, 1, 'Quần áo ngồi thiền', 'quan-ao-ngoi-thien', 'Trang phục thoải mái phục vụ việc ngồi thiền.', 1, 13),
  (5, NULL, 'Túi và phụ kiện', 'tui-va-phu-kien', 'Túi, chuỗi hạt và phụ kiện đi chùa.', 1, 20),
  (6, 5, 'Túi đeo đi chùa', 'tui-deo-di-chua', 'Túi vải và túi đeo tiện dụng khi đi chùa.', 1, 21),
  (7, 5, 'Vòng tay - chuỗi hạt', 'vong-tay-chuoi-hat', 'Vòng tay, chuỗi hạt và các sản phẩm liên quan.', 1, 22),
  (8, 5, 'Phụ kiện đi chùa', 'phu-kien-di-chua', 'Các phụ kiện phù hợp khi đi chùa.', 1, 23)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`), `description` = VALUES(`description`),
  `status` = VALUES(`status`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `catalog_attributes` (`id`, `code`, `name`, `data_type`, `unit`, `scope`, `is_filterable`, `status`, `sort_order`) VALUES
  (1, 'size', 'Kích cỡ', 'select', NULL, 'variant', 1, 1, 10),
  (2, 'color', 'Màu sắc', 'select', NULL, 'variant', 1, 1, 20),
  (3, 'material', 'Chất liệu', 'select', NULL, 'both', 1, 1, 30),
  (4, 'target_user', 'Đối tượng sử dụng', 'select', NULL, 'product', 1, 1, 40),
  (5, 'bead_size_mm', 'Đường kính hạt', 'number', 'mm', 'variant', 1, 1, 50),
  (6, 'bead_count', 'Số lượng hạt', 'number', 'hạt', 'variant', 1, 1, 60),
  (7, 'length_cm', 'Chiều dài', 'number', 'cm', 'variant', 1, 1, 70),
  (8, 'dimensions', 'Kích thước', 'text', NULL, 'product', 1, 1, 80),
  (9, 'origin', 'Xuất xứ', 'text', NULL, 'product', 1, 1, 90),
  (10, 'style', 'Kiểu dáng', 'select', NULL, 'product', 1, 1, 100),
  (11, 'usage', 'Công dụng', 'text', NULL, 'product', 0, 1, 110),
  (12, 'care_instruction', 'Hướng dẫn bảo quản', 'text', NULL, 'product', 0, 1, 120)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`), `data_type` = VALUES(`data_type`), `unit` = VALUES(`unit`),
  `scope` = VALUES(`scope`), `is_filterable` = VALUES(`is_filterable`),
  `status` = VALUES(`status`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `catalog_attribute_options` (`id`, `attribute_id`, `value`, `label`, `sort_order`, `status`) VALUES
  (1, 1, 's', 'S', 10, 1),
  (2, 1, 'm', 'M', 20, 1),
  (3, 1, 'l', 'L', 30, 1),
  (4, 1, 'xl', 'XL', 40, 1),
  (5, 1, '2xl', '2XL', 50, 1),
  (6, 1, 'free-size', 'Free size', 60, 1),
  (11, 2, 'nau', 'Nâu', 10, 1),
  (12, 2, 'xam', 'Xám', 20, 1),
  (13, 2, 'trang', 'Trắng', 30, 1),
  (14, 2, 'den', 'Đen', 40, 1),
  (15, 2, 'vang-nhat', 'Vàng nhạt', 50, 1),
  (20, 3, 'cotton', 'Cotton', 10, 1),
  (21, 3, 'linen', 'Linen', 20, 1),
  (22, 3, 'go-tram-huong', 'Gỗ trầm hương', 30, 1),
  (23, 3, 'go-dan-huong', 'Gỗ đàn hương', 40, 1),
  (24, 3, 'da-tu-nhien', 'Đá tự nhiên', 50, 1),
  (25, 3, 'canvas', 'Canvas', 60, 1),
  (26, 3, 'vai-bo', 'Vải bố', 70, 1),
  (30, 4, 'tang', 'Tăng', 10, 1),
  (31, 4, 'ni', 'Ni', 20, 1),
  (32, 4, 'phat-tu', 'Phật tử', 30, 1),
  (33, 4, 'unisex', 'Dùng chung', 40, 1),
  (40, 10, 'co-tron', 'Cổ tròn', 10, 1),
  (41, 10, 'co-tau', 'Cổ tàu', 20, 1),
  (42, 10, 'tay-dai', 'Tay dài', 30, 1),
  (43, 10, 'bo-thien', 'Bộ thiền', 40, 1)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`), `sort_order` = VALUES(`sort_order`), `status` = VALUES(`status`);

-- Thuộc tính bắt buộc/được lọc theo từng danh mục hiển thị.
INSERT IGNORE INTO `category_attribute_rules`
  (`category_id`, `attribute_id`, `is_required`, `is_variant_attribute`, `is_filterable`, `sort_order`) VALUES
  (2, 1, 1, 1, 1, 10), (2, 2, 1, 1, 1, 20), (2, 3, 1, 0, 1, 30),
  (2, 4, 1, 0, 1, 40), (2, 10, 0, 0, 1, 50), (2, 12, 0, 0, 0, 60),
  (3, 1, 1, 1, 1, 10), (3, 2, 1, 1, 1, 20), (3, 3, 1, 0, 1, 30),
  (3, 4, 1, 0, 1, 40), (3, 10, 0, 0, 1, 50), (3, 12, 0, 0, 0, 60),
  (4, 1, 1, 1, 1, 10), (4, 2, 1, 1, 1, 20), (4, 3, 1, 0, 1, 30),
  (4, 10, 1, 0, 1, 40), (4, 12, 0, 0, 0, 50),
  (6, 2, 1, 1, 1, 10), (6, 3, 1, 0, 1, 20), (6, 8, 1, 0, 1, 30),
  (6, 9, 0, 0, 1, 40), (6, 11, 0, 0, 0, 50),
  (7, 3, 1, 0, 1, 10), (7, 5, 1, 1, 1, 20), (7, 6, 1, 1, 1, 30),
  (7, 7, 1, 1, 1, 40), (7, 9, 0, 0, 1, 50), (7, 12, 0, 0, 0, 60),
  (8, 2, 0, 1, 1, 10), (8, 3, 1, 0, 1, 20), (8, 11, 1, 0, 0, 30),
  (8, 12, 0, 0, 0, 40);

-- ============================================================================
-- 8. DỮ LIỆU MẪU: SẢN PHẨM VÀ BIẾN THỂ
-- image_url bên dưới là đường dẫn mẫu. Cần upload ảnh thật trước khi hiển thị.
-- ============================================================================

INSERT INTO `product`
  (`id`, `category_id`, `name`, `slug`, `short_description`, `description`, `base_price`, `product_type`, `weight_grams`, `status`, `is_featured`, `sort_order`, `published_at`) VALUES
  (1, 3, 'Áo lam cổ tròn vải cotton', 'ao-lam-co-tron-vai-cotton', 'Áo lam cổ tròn chất liệu cotton, phù hợp mặc đi chùa.', 'Mẫu dữ liệu minh họa. Khi đưa vào bán cần thay thế bằng mô tả sản phẩm thực tế.', 280000.00, 'apparel', 350, 'active', 1, 10, CURRENT_TIMESTAMP),
  (2, 2, 'Áo tràng vải linen', 'ao-trang-vai-linen', 'Áo tràng chất liệu linen nhẹ, kiểu dáng trang nhã.', 'Mẫu dữ liệu minh họa. Cần xác nhận thông tin chất liệu và size thực tế trước khi bán.', 420000.00, 'apparel', 450, 'active', 1, 20, CURRENT_TIMESTAMP),
  (3, 4, 'Bộ quần áo ngồi thiền', 'bo-quan-ao-ngoi-thien', 'Bộ trang phục thoải mái dành cho ngồi thiền.', 'Mẫu dữ liệu minh họa.', 520000.00, 'apparel', 600, 'active', 1, 30, CURRENT_TIMESTAMP),
  (4, 6, 'Túi vải đeo đi chùa', 'tui-vai-deo-di-chua', 'Túi vải canvas gọn nhẹ để đựng vật dụng cá nhân.', 'Mẫu dữ liệu minh họa.', 180000.00, 'bag', 250, 'active', 1, 40, CURRENT_TIMESTAMP),
  (5, 7, 'Chuỗi hạt gỗ trầm hương 108 hạt', 'chuoi-hat-go-tram-huong-108-hat', 'Chuỗi hạt với nhiều lựa chọn đường kính hạt.', 'Mẫu dữ liệu minh họa. Không mô tả hoặc cam kết nguồn gốc/chất liệu nếu chưa được xác thực.', 650000.00, 'beads', 80, 'active', 1, 50, CURRENT_TIMESTAMP),
  (6, 8, 'Khăn choàng đi chùa', 'khan-choang-di-chua', 'Khăn choàng vải bố nhẹ, thiết kế tối giản.', 'Mẫu dữ liệu minh họa.', 120000.00, 'accessory', 120, 'active', 0, 60, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`), `short_description` = VALUES(`short_description`),
  `description` = VALUES(`description`), `base_price` = VALUES(`base_price`),
  `status` = VALUES(`status`), `is_featured` = VALUES(`is_featured`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `product_categories` (`product_id`, `category_id`, `is_primary`) VALUES
  (1, 3, 1), (2, 2, 1), (3, 4, 1), (4, 6, 1), (5, 7, 1), (6, 8, 1)
ON DUPLICATE KEY UPDATE `is_primary` = VALUES(`is_primary`);

INSERT INTO `product_variants`
  (`id`, `product_id`, `sku`, `variant_name`, `variant_key`, `size`, `color`, `price_modifier`, `stock_quantity`, `reserved_quantity`, `low_stock_threshold`, `status`) VALUES
  (1, 1, 'ALAM-CT-NAU-S', 'Size S - Nâu', 'size=s|color=nau', 'S', 'Nâu', 0.00, 20, 0, 5, 'active'),
  (2, 1, 'ALAM-CT-NAU-M', 'Size M - Nâu', 'size=m|color=nau', 'M', 'Nâu', 0.00, 25, 0, 5, 'active'),
  (3, 1, 'ALAM-CT-NAU-L', 'Size L - Nâu', 'size=l|color=nau', 'L', 'Nâu', 0.00, 18, 0, 5, 'active'),
  (4, 2, 'ATRANG-LINEN-FREE-NAU', 'Free size - Nâu', 'size=free-size|color=nau', 'Free size', 'Nâu', 0.00, 12, 0, 3, 'active'),
  (5, 3, 'BOTHIEN-XAM-M', 'Size M - Xám', 'size=m|color=xam', 'M', 'Xám', 0.00, 10, 0, 3, 'active'),
  (6, 3, 'BOTHIEN-XAM-L', 'Size L - Xám', 'size=l|color=xam', 'L', 'Xám', 0.00, 10, 0, 3, 'active'),
  (7, 4, 'TUI-CANVAS-NAU', 'Màu nâu', 'color=nau', NULL, 'Nâu', 0.00, 30, 0, 5, 'active'),
  (8, 4, 'TUI-CANVAS-DEN', 'Màu đen', 'color=den', NULL, 'Đen', 0.00, 25, 0, 5, 'active'),
  (9, 5, 'CHUOI-TRAM-8MM-108', 'Hạt 8mm - 108 hạt', 'bead-size=8|bead-count=108|length=70', NULL, NULL, 0.00, 15, 0, 3, 'active'),
  (10, 5, 'CHUOI-TRAM-10MM-108', 'Hạt 10mm - 108 hạt', 'bead-size=10|bead-count=108|length=75', NULL, NULL, 100000.00, 12, 0, 3, 'active'),
  (11, 5, 'CHUOI-TRAM-12MM-108', 'Hạt 12mm - 108 hạt', 'bead-size=12|bead-count=108|length=80', NULL, NULL, 200000.00, 8, 0, 3, 'active'),
  (12, 6, 'KHAN-CHOANG-DEFAULT', 'Mặc định', 'default', NULL, NULL, 0.00, 35, 0, 5, 'active')
ON DUPLICATE KEY UPDATE
  `variant_name` = VALUES(`variant_name`), `size` = VALUES(`size`), `color` = VALUES(`color`),
  `price_modifier` = VALUES(`price_modifier`), `stock_quantity` = VALUES(`stock_quantity`),
  `low_stock_threshold` = VALUES(`low_stock_threshold`), `status` = VALUES(`status`);

INSERT INTO `inventory_stocks` (`warehouse_id`, `variant_id`, `on_hand_quantity`, `reserved_quantity`)
SELECT 1, `id`, `stock_quantity`, `reserved_quantity`
FROM `product_variants`
WHERE `id` BETWEEN 1 AND 12
ON DUPLICATE KEY UPDATE
  `on_hand_quantity` = VALUES(`on_hand_quantity`),
  `reserved_quantity` = VALUES(`reserved_quantity`);

INSERT INTO `product_images` (`id`, `product_id`, `variant_id`, `image_url`, `alt_text`, `is_primary`, `sort_order`) VALUES
  (1, 1, NULL, 'public/uploads/products/demo/ao-lam-co-tron-nau.jpg', 'Áo lam cổ tròn màu nâu', 1, 10),
  (2, 2, NULL, 'public/uploads/products/demo/ao-trang-linen-nau.jpg', 'Áo tràng vải linen', 1, 10),
  (3, 3, NULL, 'public/uploads/products/demo/bo-ngoi-thien-xam.jpg', 'Bộ quần áo ngồi thiền màu xám', 1, 10),
  (4, 4, NULL, 'public/uploads/products/demo/tui-vai-di-chua.jpg', 'Túi vải đeo đi chùa', 1, 10),
  (5, 5, NULL, 'public/uploads/products/demo/chuoi-hat-go-tram.jpg', 'Chuỗi hạt gỗ trầm hương 108 hạt', 1, 10),
  (6, 6, NULL, 'public/uploads/products/demo/khan-choang-di-chua.jpg', 'Khăn choàng đi chùa', 1, 10)
ON DUPLICATE KEY UPDATE
  `image_url` = VALUES(`image_url`), `alt_text` = VALUES(`alt_text`),
  `is_primary` = VALUES(`is_primary`), `sort_order` = VALUES(`sort_order`);

-- Giá trị thuộc tính ở cấp product.
INSERT INTO `product_attribute_values` (`product_id`, `attribute_id`, `option_id`, `value_text`, `value_number`) VALUES
  (1, 3, 20, NULL, NULL), (1, 4, 32, NULL, NULL), (1, 10, 40, NULL, NULL),
  (1, 12, NULL, 'Giặt nhẹ, phơi nơi thoáng mát.', NULL),
  (2, 3, 21, NULL, NULL), (2, 4, 33, NULL, NULL), (2, 10, 41, NULL, NULL),
  (2, 12, NULL, 'Giặt nhẹ, tránh dùng chất tẩy mạnh.', NULL),
  (3, 3, 20, NULL, NULL), (3, 10, 43, NULL, NULL),
  (3, 12, NULL, 'Giặt nhẹ, phơi nơi thoáng mát.', NULL),
  (4, 3, 25, NULL, NULL), (4, 8, NULL, '28 x 24 x 8 cm', NULL),
  (4, 11, NULL, 'Đựng ví, điện thoại, sổ tay và vật dụng cá nhân.', NULL),
  (5, 3, 22, NULL, NULL), (5, 9, NULL, 'Việt Nam', NULL),
  (5, 12, NULL, 'Bảo quản nơi khô ráo, tránh ngâm nước lâu.', NULL),
  (6, 3, 26, NULL, NULL), (6, 11, NULL, 'Giữ ấm nhẹ và che nắng.', NULL)
ON DUPLICATE KEY UPDATE
  `option_id` = VALUES(`option_id`), `value_text` = VALUES(`value_text`),
  `value_number` = VALUES(`value_number`);

-- Giá trị thuộc tính ở cấp variant.
INSERT INTO `variant_attribute_values` (`variant_id`, `attribute_id`, `option_id`, `value_text`, `value_number`) VALUES
  (1, 1, 1, NULL, NULL), (1, 2, 11, NULL, NULL),
  (2, 1, 2, NULL, NULL), (2, 2, 11, NULL, NULL),
  (3, 1, 3, NULL, NULL), (3, 2, 11, NULL, NULL),
  (4, 1, 6, NULL, NULL), (4, 2, 11, NULL, NULL),
  (5, 1, 2, NULL, NULL), (5, 2, 12, NULL, NULL),
  (6, 1, 3, NULL, NULL), (6, 2, 12, NULL, NULL),
  (7, 2, 11, NULL, NULL), (8, 2, 14, NULL, NULL),
  (9, 5, NULL, NULL, 8.00), (9, 6, NULL, NULL, 108.00), (9, 7, NULL, NULL, 70.00),
  (10, 5, NULL, NULL, 10.00), (10, 6, NULL, NULL, 108.00), (10, 7, NULL, NULL, 75.00),
  (11, 5, NULL, NULL, 12.00), (11, 6, NULL, NULL, 108.00), (11, 7, NULL, NULL, 80.00)
ON DUPLICATE KEY UPDATE
  `option_id` = VALUES(`option_id`), `value_text` = VALUES(`value_text`),
  `value_number` = VALUES(`value_number`);

INSERT INTO `post_categories` (`id`, `name`, `slug`, `status`) VALUES
  (1, 'Hướng dẫn', 'huong-dan', 1),
  (2, 'Kiến thức sản phẩm', 'kien-thuc-san-pham', 1),
  (3, 'Tin cửa hàng', 'tin-cua-hang', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `status` = VALUES(`status`);

INSERT INTO `setting` (`key_name`, `value`) VALUES
  ('store_name', 'Lam Shop'),
  ('currency', 'VND'),
  ('storefront_category_mode', 'show_child_categories'),
  ('inventory_policy', 'application_transaction_no_database_trigger')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES
  ('lam_shop_db_v1'),
  ('lam_shop_db_v2_ecommerce_course'),
  ('lam_shop_db_v3_store_map');
