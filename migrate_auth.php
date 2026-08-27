<?php
require_once __DIR__ . '/app/Models/Database.php';

use App\Models\Database;

try {
    $db = Database::getInstance()->getConnection();

    // 1. Alter user table
    echo "Altering user table...\n";
    $sql1 = "
        ALTER TABLE `user`
        MODIFY `password` VARCHAR(255) NULL,
        ADD `email_verified` TINYINT(1) NOT NULL DEFAULT 1,
        ADD `google_id` VARCHAR(255) NULL UNIQUE;
    ";
    
    // Check if columns already exist before running (to avoid error if run multiple times)
    $checkCols = $db->query("SHOW COLUMNS FROM `user` LIKE 'email_verified'")->fetch();
    if (!$checkCols) {
        $db->exec($sql1);
        echo "User table altered successfully.\n";
    } else {
        echo "User table already altered.\n";
    }

    // 2. Create auth_otps table
    echo "Creating auth_otps table...\n";
    $sql2 = "
        CREATE TABLE IF NOT EXISTS `auth_otps` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `purpose` ENUM('email_verification', 'password_reset') NOT NULL,
            `otp_hash` VARCHAR(255) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (`id`),
            UNIQUE KEY `auth_otps_user_purpose_unique` (`user_id`, `purpose`),
            CONSTRAINT `auth_otps_user_fk`
                FOREIGN KEY (`user_id`)
                REFERENCES `user` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sql2);
    echo "auth_otps table created successfully.\n";

    echo "Migration completed!\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
