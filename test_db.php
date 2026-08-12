<?php
$config = require __DIR__ . '/config/database.php';
$dsn = "mysql:host={$config['host']};port=" . (int)($config['port'] ?? 3306) . ";dbname={$config['dbname']};charset={$config['charset']}";

try {
    $db = new PDO($dsn, $config['user'], $config['password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    echo "DB connection OK.\n";
} catch (PDOException $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Read-only check only. Schema is defined by Database/paceup_db.sql; the application never migrates it at runtime.\n";
echo "Products: " . (int)$db->query("SELECT COUNT(*) FROM product")->fetchColumn() . "\n";
echo "Variants with stock: " . (int)$db->query("SELECT COUNT(*) FROM product_variants WHERE stock_quantity > 0 AND status = 1")->fetchColumn() . "\n";
