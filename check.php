<?php
require_once __DIR__ . '/app/Core/App.php';
\App\Core\App::bootstrap();

$db = \App\Models\Database::getInstance()->getConnection();

echo "1. Checking total products in DB:\n";
$stmt = $db->query("SELECT COUNT(*) FROM product");
echo "Total products: " . $stmt->fetchColumn() . "\n";

echo "2. Checking products that match filter:\n";
$sql = "SELECT p.id, p.name, p.status, p.category_id, c.status as category_status,
        (SELECT COUNT(*) FROM product_variants pv WHERE pv.product_id = p.id AND pv.status = 1) as active_variants
        FROM product p
        LEFT JOIN categories c ON p.category_id = c.id";
$stmt = $db->query($sql);
$allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$displayCount = 0;
foreach ($allProducts as $p) {
    $wouldDisplay = $p['status'] == 1 && ($p['category_status'] == 1 || is_null($p['category_status'])) && $p['active_variants'] > 0;
    if ($wouldDisplay) {
        $displayCount++;
    } else {
        echo "Product ID {$p['id']} - {$p['name']} is NOT displayed. Reason:\n";
        if ($p['status'] != 1) echo "- Product status is {$p['status']}\n";
        if (!is_null($p['category_status']) && $p['category_status'] != 1) echo "- Category status is {$p['category_status']}\n";
        if ($p['active_variants'] == 0) echo "- Active variants is 0\n";
    }
}
echo "Total products that would be displayed: $displayCount\n";
