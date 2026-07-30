<?php
$db = new PDO('mysql:host=localhost;dbname=paceup_db', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $db->exec("ALTER TABLE `cart` CHANGE `variant_id` `product_id` INT(11) NULL DEFAULT NULL");
    echo "Migration successful.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}

$stmt = $db->query("DESCRIBE cart");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";
