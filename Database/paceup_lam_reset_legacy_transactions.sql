-- =====================================================================
-- ONE-TIME cleanup for the original shoe demo transactions in paceup_db.
-- Run this only for the current project seed before using the new catalog.
-- Users, addresses, auth logs and schema are intentionally preserved.
-- =====================================================================

USE `paceup_db`;

START TRANSACTION;

-- The current seed contains orders 1..15 and their dependent demo rows.
DELETE FROM `after_sale_requests` WHERE `order_id` BETWEEN 1 AND 15;
DELETE FROM `coupon_usages` WHERE `order_id` BETWEEN 1 AND 15;
DELETE FROM `order_notifications` WHERE `order_id` BETWEEN 1 AND 15;
DELETE FROM `order_sales_recognition` WHERE `order_id` BETWEEN 1 AND 15;
DELETE FROM `order_status_logs` WHERE `order_id` BETWEEN 1 AND 15;
DELETE FROM `payments` WHERE `order_id` BETWEEN 1 AND 15;
DELETE FROM `reviews` WHERE `order_id` BETWEEN 1 AND 15;
DELETE FROM `order_items` WHERE `order_id` BETWEEN 1 AND 15;
DELETE FROM `orders` WHERE `id` BETWEEN 1 AND 15;

-- These are reports/reminders generated from the old shoe demo data.
DELETE FROM `daily_revenue_reports`;
DELETE FROM `cart_reminders`;

COMMIT;
