<?php
$config = require __DIR__ . '/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

try {
    $db = new PDO($dsn, $config['user'], $config['password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "DB connection OK.\n";
} catch (PDOException $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "No ad-hoc migration executed. Ứng dụng tự nâng schema nghiệp vụ một lần khi khởi động qua Database.php.\n";

$stmt = $db->query("DESCRIBE cart");
$columns = $stmt->fetchAll();
print_r($columns);
