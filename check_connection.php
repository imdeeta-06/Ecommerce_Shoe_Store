<?php
require_once __DIR__ . '/config/db.php';
echo "Database DSN details:\n";
try {
    $stmt = $pdo->query("SELECT DATABASE()");
    echo "Active Database: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT id, name FROM categories WHERE status = 1");
    echo "Categories from categories table:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo " - ID: {$row['id']}, Name: {$row['name']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
