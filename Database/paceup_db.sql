-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th7 05, 2026 lúc 03:19 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `paceup_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banner`
--

CREATE TABLE `banner` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Dành cho Khách hàng đã đăng nhập',
  `session_id` varchar(100) DEFAULT NULL COMMENT 'Dành cho Guest',
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `session_id`, `product_id`, `quantity`) VALUES
(49, 1, NULL, 136, 1),
(50, 1, NULL, 122, 1),
(51, 1, NULL, 121, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `status` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `status`) VALUES
(100, 'Running', 'running', 1),
(101, 'Skateboarding', 'skateboarding', 1),
(102, 'Lifestyle', 'lifestyle', 1),
(103, 'Football', 'football', 1),
(104, 'Basketball', 'basketball', 1),
(105, 'Tennis', 'tennis', 1),
(106, 'Training', 'training', 1),
(107, 'Slide', 'slide', 1),
(108, 'Golf', 'golf', 1),
(109, 'Speeding', 'speed', 0),
(110, 'Plushie', 'plushie', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `min_order_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `usage_limit` int(11) NOT NULL DEFAULT 0,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `expiry_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_percent`, `max_discount`, `min_order_amount`, `usage_limit`, `used_count`, `start_date`, `expiry_date`) VALUES
(1, 'Summer', 30.00, NULL, 100000.00, 20, 0, '2026-07-05 00:00:00', '2026-07-06 00:00:00'),
(2, 'Paceup', NULL, 300000.00, 0.00, 100, 0, '2026-07-05 00:00:00', '2026-07-06 00:00:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `custom_notes`
--

CREATE TABLE `custom_notes` (
  `id` int(11) NOT NULL,
  `entity_type` varchar(50) NOT NULL COMMENT 'VD: Order, User',
  `entity_id` int(11) NOT NULL,
  `note_content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `daily_revenue_reports`
--

CREATE TABLE `daily_revenue_reports` (
  `id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `total_orders` int(11) DEFAULT 0,
  `gross_revenue` decimal(10,2) DEFAULT 0.00,
  `total_discount` decimal(10,2) DEFAULT 0.00,
  `net_revenue` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `daily_revenue_reports`
--

INSERT INTO `daily_revenue_reports` (`id`, `report_date`, `total_orders`, `gross_revenue`, `total_discount`, `net_revenue`, `created_at`) VALUES
(1, '2026-07-05', 1, 9000000.00, 0.00, 9000000.00, '2026-07-05 19:26:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity_changed` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_logs`
--

INSERT INTO `inventory_logs` (`id`, `variant_id`, `quantity_changed`, `reason`) VALUES
(1, NULL, -2, 'Khách mua hàng, Order ID: 1'),
(2, NULL, -1, 'Khách mua hàng, Order ID: 2'),
(3, NULL, -2, 'Khách mua hàng, Order ID: 3'),
(4, 1, 450, 'Manual inventory update'),
(5, NULL, -1, 'Khách mua hàng, Order ID: 4'),
(6, 2, 100, 'update'),
(7, NULL, -1, 'Khách mua hàng, Order ID: 5'),
(8, NULL, -1, 'Khách mua hàng, Order ID: 6'),
(9, NULL, -1, 'Khách mua hàng, Order ID: 7'),
(10, NULL, -1, 'Khách mua hàng, Order ID: 7'),
(11, NULL, -1, 'Khách mua hàng, Order ID: 7');

--
-- Bẫy `inventory_logs`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_inventory_log` AFTER INSERT ON `inventory_logs` FOR EACH ROW BEGIN
    UPDATE Product_variants
    SET stock_quantity = stock_quantity + NEW.quantity_changed
    WHERE id = NEW.variant_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `action`, `created_at`) VALUES
(1, 1, 'logout | Đăng xuất', '2026-07-02 18:05:15'),
(2, 1, 'login | Đăng nhập thành công', '2026-07-02 18:08:04'),
(3, 1, 'logout | Đăng xuất', '2026-07-02 18:13:47'),
(4, 1, 'login | Đăng nhập thành công', '2026-07-02 18:13:54'),
(5, 1, 'logout | Đăng xuất', '2026-07-02 18:13:58'),
(6, 1, 'login | Đăng nhập thành công', '2026-07-02 18:14:14'),
(7, 1, 'logout | Đăng xuất', '2026-07-02 18:14:27'),
(8, 2, 'login | Đăng nhập thành công', '2026-07-02 18:14:35'),
(9, 2, 'logout | Đăng xuất', '2026-07-02 18:14:53'),
(10, 1, 'login | Đăng nhập thành công', '2026-07-02 18:14:56'),
(11, 1, 'logout | Đăng xuất', '2026-07-02 18:15:45'),
(12, NULL, 'register | Đăng ký tài khoản mới', '2026-07-02 19:03:17'),
(13, 1, 'login | Đăng nhập thành công', '2026-07-02 19:03:27'),
(14, 1, 'logout | Đăng xuất', '2026-07-02 19:03:31'),
(15, NULL, 'login | Đăng nhập thành công', '2026-07-02 19:03:42'),
(16, NULL, 'logout | Đăng xuất', '2026-07-02 19:48:54'),
(17, 1, 'login | Đăng nhập thành công', '2026-07-02 19:48:57'),
(18, 1, 'logout | Đăng xuất', '2026-07-02 20:03:29'),
(19, 2, 'login | Đăng nhập thành công', '2026-07-02 20:04:47'),
(20, 2, 'logout | Đăng xuất', '2026-07-02 20:06:00'),
(21, 1, 'login | Đăng nhập thành công', '2026-07-02 20:06:06'),
(22, 1, 'logout | Đăng xuất', '2026-07-02 20:06:09'),
(23, 2, 'login | Đăng nhập thành công', '2026-07-02 20:06:51'),
(24, 2, 'logout | Đăng xuất', '2026-07-02 20:06:54'),
(25, 2, 'login | Đăng nhập thành công', '2026-07-02 20:10:46'),
(26, 2, 'upload_avatar | Cap nhat anh dai dien', '2026-07-02 20:11:37'),
(27, 2, 'logout | Đăng xuất', '2026-07-02 20:14:37'),
(28, 1, 'login | Đăng nhập thành công', '2026-07-02 20:14:39'),
(29, 1, 'logout | Đăng xuất', '2026-07-02 20:15:16'),
(30, 2, 'login | Đăng nhập thành công', '2026-07-02 20:15:22'),
(31, 2, 'logout | Đăng xuất', '2026-07-02 20:19:30'),
(32, 1, 'login | Đăng nhập thành công', '2026-07-02 20:19:37'),
(33, 1, 'logout | Đăng xuất', '2026-07-02 20:20:34'),
(34, 2, 'login | Đăng nhập thành công', '2026-07-02 20:20:46'),
(35, 2, 'upload_avatar | Cap nhat anh dai dien', '2026-07-02 20:22:50'),
(36, 2, 'logout | Đăng xuất', '2026-07-02 20:25:24'),
(37, 1, 'login | Đăng nhập thành công', '2026-07-02 20:25:26'),
(38, 1, 'logout | Đăng xuất', '2026-07-02 20:30:32'),
(39, 1, 'login | Đăng nhập thành công', '2026-07-02 20:31:47'),
(40, 1, 'logout | Đăng xuất', '2026-07-02 20:31:57'),
(41, 1, 'login | Đăng nhập thành công', '2026-07-03 09:55:44'),
(42, 1, 'login | Đăng nhập thành công', '2026-07-04 10:32:31');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `coupon_id` int(11) DEFAULT NULL,
  `final_amount` decimal(10,2) NOT NULL,
  `shipping_name` varchar(100) NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `status` enum('pending','confirmed','shipping','delivered','canceled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `shipping_email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `total_amount`, `coupon_id`, `final_amount`, `shipping_name`, `shipping_phone`, `shipping_address`, `status`, `created_at`, `shipping_email`) VALUES
(1, 'ORD-20260704-79C103', 1, 6800000.00, NULL, 6800000.00, '_06 Deeta', '12345', '123 qdad', 'pending', '2026-07-04 13:02:33', 'dinhthuyanha@gmail.com'),
(2, 'ORD-20260704-D74553', 2, 3300000.00, NULL, 3300000.00, '1231', '12345', '123 qdad', 'pending', '2026-07-04 13:14:11', 'dinhthuyanha@gmail.com'),
(3, 'ORD-20260704-813DB8', 5, 9200000.00, NULL, 9200000.00, '_06 Deeta', '0123456789', '123 qdad', 'pending', '2026-07-04 20:19:32', 'dinhthuyanha@gmail.com'),
(4, 'ORD-20260705-2A542B', 1, 3400000.00, NULL, 3400000.00, 'duy', '0242512151', 'SigmaStreet', 'pending', '2026-07-05 15:40:30', 'duy@gmail.com'),
(5, 'ORD-20260705-9500F0', 7, 3500000.00, NULL, 3500000.00, 'duy', '0939588189', 'SigmaStreet', 'confirmed', '2026-07-05 16:46:42', 'duy@gmail.com'),
(6, 'ORD-20260705-E37C9B', 1, 3500000.00, NULL, 3500000.00, 'duy', '0939588189', 'SigmaStreet', 'pending', '2026-07-05 19:07:40', 'duy@gmail.com'),
(7, 'ORD-20260705-D7D1CD', 1, 9000000.00, NULL, 9000000.00, 'skibidi', '0982870458', 'SigmaStreet', 'delivered', '2026-07-05 19:10:07', 'duy@gmail.com'),
(8, 'ORD-20260705-C5E706', 1, 11500000.00, 1, 8050000.00, 'duy', '0939588189', 'SigmaStreet', 'pending', '2026-07-05 20:16:28', 'duy@gmail.com'),
(9, 'ORD-20260705-043C20', 1, 11500000.00, 1, 8050000.00, 'duy', '0939588189', 'SigmaStreet', 'pending', '2026-07-05 20:16:30', 'duy@gmail.com'),
(10, 'ORD-20260705-30BE75', 1, 11500000.00, 1, 8050000.00, 'duy', '0939588189', 'SigmaStreet', 'pending', '2026-07-05 20:16:34', 'duy@gmail.com'),
(11, 'ORD-20260705-5316AD', 1, 11500000.00, 1, 8050000.00, 'duy', '0939588189', 'SigmaStreet', 'pending', '2026-07-05 20:16:37', 'duy@gmail.com'),
(12, 'ORD-20260705-2E1ABA', 1, 11500000.00, 1, 8050000.00, 'duy', '0939588189', 'SigmaStreet', 'pending', '2026-07-05 20:16:42', 'duy@gmail.com'),
(13, 'ORD-20260705-890470', 1, 11500000.00, 1, 8050000.00, 'duy', '0939588189', 'SigmaStreet', 'pending', '2026-07-05 20:16:48', 'duy@gmail.com'),
(14, 'ORD-20260705-A7505B', 1, 11500000.00, NULL, 11500000.00, 'duy', '0939588189', 'SigmaStreet', 'pending', '2026-07-05 20:17:31', 'duy@gmail.com'),
(15, 'ORD-20260705-5E360E', 1, 11500000.00, NULL, 11500000.00, 'duy', '0939588189', 'SigmaStreet', 'pending', '2026-07-05 20:18:00', 'duy@gmail.com');

--
-- Bẫy `orders`
--
DELIMITER $$
CREATE TRIGGER `trg_after_order_canceled` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    -- Chỉ hoàn kho nếu đơn đã từng được xác nhận/trừ kho trước đó
    IF NEW.status = 'canceled' AND OLD.status IN ('confirmed', 'shipping', 'delivered') THEN
        INSERT INTO Inventory_logs (variant_id, quantity_changed, reason)
        SELECT variant_id, quantity, CONCAT('Hoàn trả kho do hủy đơn hàng ID: ', NEW.id)
        FROM Order_items
        WHERE order_id = NEW.id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_update_daily_revenue` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    DECLARE discount_val DECIMAL(10,2);
    
    -- Chỉ tính doanh thu khi đơn hàng đã giao thành công
    IF NEW.status = 'delivered' AND OLD.status != 'delivered' THEN
        
        -- Tính số tiền đã giảm giá
        SET discount_val = NEW.total_amount - NEW.final_amount;
        
        -- Chèn báo cáo mới hoặc cập nhật báo cáo có sẵn của ngày hôm nay
        INSERT INTO Daily_revenue_reports 
            (report_date, total_orders, gross_revenue, total_discount, net_revenue)
        VALUES 
            (CURRENT_DATE(), 1, NEW.total_amount, discount_val, NEW.final_amount)
        ON DUPLICATE KEY UPDATE 
            total_orders = total_orders + 1,
            gross_revenue = gross_revenue + NEW.total_amount,
            total_discount = total_discount + discount_val,
            net_revenue = net_revenue + NEW.final_amount;
            
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_time`) VALUES
(1, 1, NULL, 2, 3400000.00),
(2, 2, NULL, 1, 3300000.00),
(3, 3, NULL, 2, 4600000.00),
(4, 4, NULL, 1, 3400000.00),
(5, 5, NULL, 1, 3500000.00),
(6, 6, NULL, 1, 3500000.00),
(7, 7, NULL, 1, 3500000.00),
(8, 7, NULL, 1, 2100000.00),
(9, 7, NULL, 1, 3400000.00);

--
-- Kho hàng được trừ khi admin chuyển đơn từ pending sang confirmed.
-- Không trừ kho ngay lúc insert order_items.

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_status_logs`
--

CREATE TABLE `order_status_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_status_logs`
--

INSERT INTO `order_status_logs` (`id`, `order_id`, `status`, `changed_by`, `created_at`) VALUES
(1, 5, 'confirmed', NULL, '2026-07-05 19:06:46'),
(2, 7, 'confirmed', NULL, '2026-07-05 19:26:25'),
(3, 7, 'delivered', NULL, '2026-07-05 19:26:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_reset_otp`
--

CREATE TABLE `password_reset_otp` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `post_categories`
--

CREATE TABLE `post_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `sold_count` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(4) DEFAULT 1,
  `type` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product`
--

INSERT INTO `product` (`id`, `category_id`, `name`, `slug`, `description`, `base_price`, `sold_count`, `status`, `type`, `gender`) VALUES
(100, 100, 'Air Zoom Pegasus 42 Wide', 'air-zoom-pegasus-42-wide', 'Air Zoom Pegasus 42 Wide chính hãng Nike. Sản phẩm thuộc dòng Running, cam kết chất lượng 100% và bảo hành đầy đủ.', 3800000.00, 0, 1, 'Giày chạy bộ Nam', 'men'),
(101, 101, 'Nike SB Dunk Low Pro', 'nike-sb-dunk-low-pro', 'Nike SB Dunk Low Pro chính hãng Nike. Sản phẩm thuộc dòng Skateboarding, cam kết chất lượng 100% và bảo hành đầy đủ.', 4200000.00, 0, 1, 'Giày Skateboarding Nam', 'men'),
(102, 102, 'Nike Air Max Moto 2K', 'nike-air-max-moto-2k', 'Nike Air Max Moto 2K chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3500000.00, 0, 1, 'Giày Lifestyle Nam', 'men'),
(103, 103, 'Vapor 17 Pro FG', 'vapor-17-pro-fg', 'Vapor 17 Pro FG chính hãng Nike. Sản phẩm thuộc dòng Football, cam kết chất lượng 100% và bảo hành đầy đủ.', 4500000.00, 0, 1, 'Giày đá bóng Nam', 'men'),
(104, 104, 'Giannis Freak 7 EP', 'giannis-freak-7-ep', 'Giannis Freak 7 EP chính hãng Nike. Sản phẩm thuộc dòng Basketball, cam kết chất lượng 100% và bảo hành đầy đủ.', 4800000.00, 0, 1, 'Giày bóng rổ Nam', 'men'),
(105, 105, 'Nike Court Lite 4', 'nike-court-lite-4', 'Nike Court Lite 4 chính hãng Nike. Sản phẩm thuộc dòng Tennis, cam kết chất lượng 100% và bảo hành đầy đủ.', 2200000.00, 0, 1, 'Giày tennis Nam', 'men'),
(106, 106, 'Nike Metcon 10', 'nike-metcon-10', 'Nike Metcon 10 chính hãng Nike. Sản phẩm thuộc dòng Training, cam kết chất lượng 100% và bảo hành đầy đủ.', 3600000.00, 0, 1, 'Giày Training Nam', 'men'),
(107, 105, 'Nike Vapor Lite 3 HC', 'nike-vapor-lite-3-hc', 'Nike Vapor Lite 3 HC chính hãng Nike. Sản phẩm thuộc dòng Tennis, cam kết chất lượng 100% và bảo hành đầy đủ.', 2800000.00, 0, 1, 'Giày tennis Nam', 'men'),
(108, 107, 'Nike Air Max Cirro Slide', 'nike-air-max-cirro-slide', 'Nike Air Max Cirro Slide chính hãng Nike. Sản phẩm thuộc dòng Slide, cam kết chất lượng 100% và bảo hành đầy đủ.', 1800000.00, 0, 1, 'Dép Nam', 'men'),
(109, 102, 'Nike P-6000', 'nike-p-6000', 'Nike P-6000 chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3200000.00, 0, 1, 'Giày Lifestyle Nam', 'men'),
(110, 107, 'Nike ReactX Rejuven8 Slide', 'nike-reactx-rejuven8-slide', 'Nike ReactX Rejuven8 Slide chính hãng Nike. Sản phẩm thuộc dòng Slide, cam kết chất lượng 100% và bảo hành đầy đủ.', 2100000.00, 0, 1, 'Dép Nam', 'men'),
(111, 101, 'Nike SB Chron 2 Canvas', 'nike-sb-chron-2-canvas', 'Nike SB Chron 2 Canvas chính hãng Nike. Sản phẩm thuộc dòng Skateboarding, cam kết chất lượng 100% và bảo hành đầy đủ.', 2000000.00, 0, 1, 'Giày Skateboarding Nam', 'men'),
(112, 101, 'Nike SB Zoom Blazer Mid', 'nike-sb-zoom-blazer-mid', 'Nike SB Zoom Blazer Mid chính hãng Nike. Sản phẩm thuộc dòng Skateboarding, cam kết chất lượng 100% và bảo hành đầy đủ.', 2900000.00, 0, 1, 'Giày Skateboarding Nam', 'men'),
(113, 100, 'Nike Zoom Vomero 5 SE', 'nike-zoom-vomero-5-se', 'Nike Zoom Vomero 5 SE chính hãng Nike. Sản phẩm thuộc dòng Running, cam kết chất lượng 100% và bảo hành đầy đủ.', 4100000.00, 0, 1, 'Giày chạy bộ Nam', 'men'),
(114, 103, 'Phantom 6 High Acad FG/MG', 'phantom-6-high-acad-fg-mg', 'Phantom 6 High Acad FG/MG chính hãng Nike. Sản phẩm thuộc dòng Football, cam kết chất lượng 100% và bảo hành đầy đủ.', 2600000.00, 0, 1, 'Giày đá bóng Nam', 'men'),
(115, 104, 'Sabrina 3 EP', 'sabrina-3-ep', 'Sabrina 3 EP chính hãng Nike. Sản phẩm thuộc dòng Basketball, cam kết chất lượng 100% và bảo hành đầy đủ.', 3900000.00, 0, 1, 'Giày bóng rổ Nam', 'men'),
(116, 103, 'Tiempo Maestro Elite FG SE', 'tiempo-maestro-elite-fg-se', 'Tiempo Maestro Elite FG SE chính hãng Nike. Sản phẩm thuộc dòng Football, cam kết chất lượng 100% và bảo hành đầy đủ.', 6500000.00, 0, 1, 'Giày đá bóng Nam', 'men'),
(117, 103, 'Tiempo Maestro Elite FG T', 'tiempo-maestro-elite-fg-t', 'Tiempo Maestro Elite FG T chính hãng Nike. Sản phẩm thuộc dòng Football, cam kết chất lượng 100% và bảo hành đầy đủ.', 6200000.00, 0, 1, 'Giày đá bóng Nam', 'men'),
(118, 108, 'Air Jordan 1 Low G SPK', 'air-jordan-1-low-g-spk', 'Air Jordan 1 Low G SPK chính hãng Nike. Sản phẩm thuộc dòng Golf, cam kết chất lượng 100% và bảo hành đầy đủ.', 4500000.00, 0, 1, 'Giày Golf Nam', 'men'),
(119, 102, 'Air Jordan Mule', 'air-jordan-mule', 'Air Jordan Mule chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3100000.00, 0, 1, 'Giày Lifestyle Nam', 'men'),
(120, 108, 'Victory Pro 4', 'victory-pro-4', 'Victory Pro 4 chính hãng Nike. Sản phẩm thuộc dòng Golf, cam kết chất lượng 100% và bảo hành đầy đủ.', 3800000.00, 0, 1, 'Giày Golf Nam', 'men'),
(121, 108, 'Victory Tour 4', 'victory-tour-4', 'Victory Tour 4 chính hãng Nike. Sản phẩm thuộc dòng Golf, cam kết chất lượng 100% và bảo hành đầy đủ.', 4600000.00, 0, 1, 'Giày Golf Nam', 'men'),
(122, 102, 'Waffle Racer SE', 'waffle-racer-se', 'Waffle Racer SE chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3400000.00, 0, 1, 'Giày Lifestyle Nam', 'men'),
(123, 102, 'Nike Air Max Moto 2K W', 'nike-air-max-moto-2k-w', 'Nike Air Max Moto 2K W chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3500000.00, 0, 1, 'Giày Lifestyle Nữ', 'women'),
(124, 102, 'Nike Cortez', 'nike-cortez', 'Nike Cortez chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 2800000.00, 0, 1, 'Giày Lifestyle Nữ', 'women'),
(125, 106, 'Nike Metcon 10 W', 'nike-metcon-10-w', 'Nike Metcon 10 W chính hãng Nike. Sản phẩm thuộc dòng Training, cam kết chất lượng 100% và bảo hành đầy đủ.', 3600000.00, 0, 1, 'Giày Training Nữ', 'women'),
(126, 102, 'Nike P-6000 W', 'nike-p-6000-w', 'Nike P-6000 W chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3200000.00, 0, 1, 'Giày Lifestyle Nữ', 'women'),
(127, 106, 'Nike Reax 8 NSW SL', 'nike-reax-8-nsw-sl', 'Nike Reax 8 NSW SL chính hãng Nike. Sản phẩm thuộc dòng Training, cam kết chất lượng 100% và bảo hành đầy đủ.', 2500000.00, 0, 1, 'Giày Training Nữ', 'women'),
(128, 102, 'Air Jordan 1 Low SE APLA', 'air-jordan-1-low-se-apla', 'Air Jordan 1 Low SE APLA chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3900000.00, 0, 1, 'Giày Lifestyle Nữ', 'women'),
(129, 102, 'Air Jordan 1 Low SE', 'air-jordan-1-low-se', 'Air Jordan 1 Low SE chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3600000.00, 0, 1, 'Giày Lifestyle Nữ', 'women'),
(130, 102, 'Jordan Flight Court', 'jordan-flight-court', 'Jordan Flight Court chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3300000.00, 0, 1, 'Giày Lifestyle Nữ', 'women'),
(131, 102, 'Nike Air Rift Neo', 'nike-air-rift-neo', 'Nike Air Rift Neo chính hãng Nike. Sản phẩm thuộc dòng Lifestyle, cam kết chất lượng 100% và bảo hành đầy đủ.', 3100000.00, 0, 1, 'Giày Lifestyle Nữ', 'women'),
(132, 105, 'Nike Court Legacy NN', 'nike-court-legacy-nn', 'Nike Court Legacy NN chính hãng Nike. Sản phẩm thuộc dòng Tennis, cam kết chất lượng 100% và bảo hành đầy đủ.', 2200000.00, 0, 1, 'Giày Tennis Nữ', 'women'),
(133, 100, 'Nike Motiva 2', 'nike-motiva-2', 'Nike Motiva 2 chính hãng Nike. Sản phẩm thuộc dòng Running, cam kết chất lượng 100% và bảo hành đầy đủ.', 3400000.00, 0, 1, 'Giày chạy bộ Nữ', 'women'),
(134, 107, 'Nike ReactX Rejuven8', 'nike-reactx-rejuven8', 'Nike ReactX Rejuven8 chính hãng Nike. Sản phẩm thuộc dòng Slide, cam kết chất lượng 100% và bảo hành đầy đủ.', 2100000.00, 0, 1, 'Dép Nữ', 'women'),
(136, 110, 'Biblos plush', 'plushie', 'Evil', 3500000.00, 0, 1, 'Plushie', 'men');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `is_primary`) VALUES
(1, 100, 'AIR+ZOOM+PEGASUS+42+WIDE.avif', 1),
(2, 101, 'NIKE+SB+DUNK+LOW+PRO.avif', 1),
(3, 102, 'NIKE+AIR+MAX+MOTO+2K.avif', 1),
(4, 103, 'VAPOR+17+PRO+FG.avif', 1),
(5, 104, 'GIANNIS+FREAK+7+EP.avif', 1),
(6, 105, 'M+NIKE+COURT+LITE+4.avif', 1),
(7, 106, 'M+NIKE+METCON+10.avif', 1),
(8, 107, 'M+VAPOR+LITE+3+HC.avif', 1),
(9, 108, 'NIKE+AIR+MAX+CIRRO+SLIDE.avif', 1),
(10, 109, 'NIKE+P-6000.avif', 1),
(11, 110, 'NIKE+REACTX+REJUVEN8+SLIDE.avif', 1),
(12, 111, 'NIKE+SB+CHRON+2+CNVS.avif', 1),
(13, 112, 'NIKE+SB+ZOOM+BLAZER+MID.avif', 1),
(14, 113, 'NIKE+ZOOM+VOMERO+5+SE.avif', 1),
(15, 114, 'PHANTOM+6+HIGH+ACAD+FG_MG.avif', 1),
(16, 115, 'SABRINA+3+EP.avif', 1),
(17, 116, 'TIEMPO+MAESTRO+ELITE+FG+SE.avif', 1),
(18, 117, 'TIEMPO+MAESTRO+ELITE+FG+T.avif', 1),
(19, 118, 'AIR+JORDAN+1+LOW+G+SPK.avif', 1),
(20, 119, 'AIR+JORDAN+MULE.avif', 1),
(21, 120, 'VICTORY+PRO+4.avif', 1),
(22, 121, 'VICTORY+TOUR+4.avif', 1),
(23, 122, 'WAFFLE+RACER+SE.avif', 1),
(24, 123, 'W+NIKE+AIR+MAX+MOTO+2K.avif', 1),
(25, 124, 'W+NIKE+CORTEZ.avif', 1),
(26, 125, 'W+NIKE+METCON+10.avif', 1),
(27, 126, 'W+NIKE+P-6000.avif', 1),
(28, 127, 'W+NIKE+REAX+8+NSW+SL.avif', 1),
(29, 128, 'WMNS+AIR+JORDAN+1+LOW+SE+APLA.avif', 1),
(30, 129, 'WMNS+AIR+JORDAN+1+LOW+SE.avif', 1),
(31, 130, 'WMNS+JORDAN+FLIGHT+COURT.avif', 1),
(32, 131, 'WMNS+NIKE+AIR++RIFT+NEO.avif', 1),
(33, 132, 'WMNS+NIKE+COURT+LEGACY+NN.avif', 1),
(34, 133, 'WMNS+NIKE+MOTIVA+2.avif', 1),
(35, 134, 'WMNS+NIKE+REACTX+REJUVEN8.avif', 1),
(37, 136, 'public/uploads/products/img_6a4a262c60c271.16553048.jpg', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_sales_reports`
--

CREATE TABLE `product_sales_reports` (
  `id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity_sold` int(11) DEFAULT 0,
  `total_revenue` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `size` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `price_modifier` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `stock_quantity`, `price_modifier`) VALUES
(1, 133, 'EU 42', 'Black', 550, 300000.00),
(2, 136, 'EU 45', 'Black', 100, 0.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL COMMENT '1-5 sao',
  `comment` text DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `setting`
--

CREATE TABLE `setting` (
  `id` int(11) NOT NULL,
  `key_name` varchar(50) NOT NULL,
  `value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','guest') DEFAULT 'user' COMMENT 'admin hoặc user',
  `status` tinyint(4) DEFAULT 1 COMMENT '1: Hoạt động, 0: Khóa',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user`
--

INSERT INTO `user` (`id`, `full_name`, `display_name`, `email`, `phone`, `avatar`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Admin', 'testingdn', 'admin@123', '0123456789', NULL, '$2y$10$Mpt6opJmon7cLQakVyk/e.4zNBi84IVHBu8dxHItssNnmW7Be.YNy', 'admin', 1, '2026-06-25 07:35:38'),
(2, 'Deeta', 'Ăn thịt người bán giày', 'user@123', '12345', 'public/uploads/avatars/1783138213_6a4887a573454.png', '$2y$10$0IO910WayVRynf5DZvjX2uTP1vqCHudiMNMm9Oo1jhS6CiLFAtFwW', 'user', 1, '2026-06-25 07:35:38'),
(4, 'Điu', 'Ăn thịt thùi', 'user0202@gmail.com', '0123456789', NULL, '$2y$10$i0Yyx/TSELV4lWYMZ08mmOS2lHtxc/JKEQZ//myFWJFeZr3xLhSfu', 'user', 1, '2026-07-04 13:48:22'),
(5, '_06 Deeta', NULL, 'dinhthuyanha@gmail.com', '0234567891', NULL, '$2y$10$ur1sJMCTE9b5y/BuNQ/fLuJneRI1gzNJcf9slM2Vlx/xX5FkaZBLi', 'user', 1, '2026-07-04 20:17:54'),
(6, 'Admin_number1', 'Ạc min', 'admin1@gmail.com', '0123123', NULL, '$2y$10$Ges0kPKvLlg/YfEeCJ3IBupJQIU69bOdMTqGvFFIr8lvJ8QgkIbB.', 'admin', 1, '2026-07-05 10:21:36'),
(7, 'duy', 'BIblos', 'duy@gmail.com', '0939588189', 'public/uploads/avatars/1783244821_6a4a281540d3b.jpg', '$2y$10$FWzBdM1fpzYkuyekr8ZahejUG9ppCt/JcSVFm.utjAbAV3lS7efXu', 'user', 1, '2026-07-05 16:45:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `address_line` varchar(255) NOT NULL,
  `ward_district_city` varchar(255) NOT NULL,
  `is_default` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_line`, `ward_district_city`, `is_default`) VALUES
(1, 2, '123312 AD', 'HCM', 0),
(2, 2, '3234 vptjad', 'HCM', 1),
(3, 1, '3234 vptjad', 'HCM', 0),
(4, 1, '123312 AD', 'HCM', 1),
(5, 7, 'sigmastreet', 'ohio', 0),
(6, 7, 'sigmastreet', 'ohio', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`) VALUES
(7, 1, 134);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `banner`
--
ALTER TABLE `banner`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `variant_id` (`product_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `custom_notes`
--
ALTER TABLE `custom_notes`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `daily_revenue_reports`
--
ALTER TABLE `daily_revenue_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_date` (`report_date`);

--
-- Chỉ mục cho bảng `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `coupon_id` (`coupon_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `variant_id` (`product_id`);

--
-- Chỉ mục cho bảng `order_status_logs`
--
ALTER TABLE `order_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Chỉ mục cho bảng `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `post_categories`
--
ALTER TABLE `post_categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `product_sales_reports`
--
ALTER TABLE `product_sales_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Chỉ mục cho bảng `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `banner`
--
ALTER TABLE `banner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT cho bảng `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `custom_notes`
--
ALTER TABLE `custom_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `daily_revenue_reports`
--
ALTER TABLE `daily_revenue_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `order_status_logs`
--
ALTER TABLE `order_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `post_categories`
--
ALTER TABLE `post_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT cho bảng `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT cho bảng `product_sales_reports`
--
ALTER TABLE `product_sales_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `setting`
--
ALTER TABLE `setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_status_logs`
--
ALTER TABLE `order_status_logs`
  ADD CONSTRAINT `order_status_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `post_categories` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_sales_reports`
--
ALTER TABLE `product_sales_reports`
  ADD CONSTRAINT `product_sales_reports_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_sales_reports_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
