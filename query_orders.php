<?php
require_once __DIR__ . '/app/Core/Database.php';
$db = App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, shipping_name, shipping_phone, total_amount FROM orders");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
