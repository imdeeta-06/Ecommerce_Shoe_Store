<?php
require_once __DIR__ . '/app/Core/App.php';
\App\Core\App::bootstrap();

$p = new \App\Models\Product();
echo "Active products count: " . $p->getActiveProductsCount() . "\n";
echo "getProductsByFilter count: " . count($p->getProductsByFilter([])) . "\n";
