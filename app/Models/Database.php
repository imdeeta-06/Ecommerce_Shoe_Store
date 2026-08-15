<?php

namespace App\Models;

use PDO;
use PDOException;

/**
 * Kết nối đến database chuẩn của dự án.
 * Schema và dữ liệu mẫu chỉ được quản lý bởi Database/paceup_db.sql.
 * Ứng dụng không tự ALTER hoặc tự seed dữ liệu khi có request.
 */
class Database {
    private static $instance = null;
    private PDO $connection;

    private function __construct() {
        $config = require __DIR__ . '/../../config/database.php';
        $port = (int)($config['port'] ?? 3306);
        $dsn = "mysql:host={$config['host']};port={$port};dbname={$config['dbname']};charset={$config['charset']}";

        try {
            $this->connection = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }
}
