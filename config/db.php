<?php
require_once __DIR__ . '/../app/Core/App.php';
\App\Core\App::loadEnv();
$db_config = require __DIR__ . '/database.php';
try {
    // Dùng unix_socket nếu có (MAMP), ngược lại dùng host:port
    if (!empty($db_config['unix_socket'])) {
        $dsn = "mysql:unix_socket={$db_config['unix_socket']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    } else {
        $port = $db_config['port'] ?? 3306;
        $dsn = "mysql:host={$db_config['host']};port={$port};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    }
    $pdo = new PDO(
        $dsn,
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('Legacy admin database connection failed: ' . $e->getMessage());
    throw new RuntimeException('Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.');
}
