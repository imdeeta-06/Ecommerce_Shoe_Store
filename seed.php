<?php
// Run once: php seed.php OR visit in browser to create admin & user accounts
require_once __DIR__ . '/config/db.php';

$users = [
    ['full_name' => 'Admin PaceUp', 'display_name' => 'Admin', 'email' => 'admin@paceup.com', 'password' => 'Admin@123', 'role' => 'admin'],
    ['full_name' => 'User PaceUp', 'display_name' => 'Customer', 'email' => 'user@paceup.com', 'password' => 'User@123', 'role' => 'user'],
];

$statusMode = 'int';
try {
    $statusField = $pdo->query("SHOW COLUMNS FROM user LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
    if ($statusField && stripos($statusField['Type'], 'enum') !== false) {
        $statusMode = 'enum';
    }
} catch (Throwable $e) {
    $statusMode = 'int';
}

$hasDisplayName = false;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM user")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        if (($col['Field'] ?? '') === 'display_name') {
            $hasDisplayName = true;
            break;
        }
    }
} catch (Throwable $e) {
    $hasDisplayName = false;
}

foreach ($users as $u) {
    $existing = $pdo->prepare("SELECT id FROM user WHERE email = ?");
    $existing->execute([$u['email']]);
    $statusValue = $statusMode === 'enum' ? 'active' : 1;
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);

    if ($existing->fetch()) {
        $updateSql = "UPDATE user SET full_name = ?, password = ?, role = ?, status = ?";
        $params = [$u['full_name'], $hash, $u['role'], $statusValue, $u['email']];

        if ($hasDisplayName) {
            $updateSql .= ', display_name = ?';
            $params = [$u['full_name'], $u['display_name'], $hash, $u['role'], $statusValue, $u['email']];
            $updateSql .= ' WHERE email = ?';
        } else {
            $updateSql .= ' WHERE email = ?';
        }

        $stmt = $pdo->prepare($updateSql);
        $stmt->execute($params);
        echo "Updated {$u['email']} ({$u['role']})<br>";
        continue;
    }

    $columns = ['full_name', 'email', 'password', 'role', 'status'];
    $values = [$u['full_name'], $u['email'], $hash, $u['role'], $statusValue];

    if ($hasDisplayName) {
        array_splice($columns, 1, 0, 'display_name');
        array_splice($values, 1, 0, $u['display_name']);
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $stmt = $pdo->prepare("INSERT INTO user (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")");
    $stmt->execute($values);
    echo "Created {$u['email']} ({$u['role']})<br>";
}

echo "<h3>Seeding Categories...</h3>";
$categories = ['Running', 'Skateboarding', 'Lifestyle', 'Football', 'Basketball', 'Tennis', 'Training', 'Slide', 'Golf'];
foreach ($categories as $cat) {
    $slug = strtolower($cat);
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$cat]);
    if ($stmt->fetch()) {
        echo "Category {$cat} already exists.<br>";
        continue;
    }
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, status) VALUES (?, ?, 1)");
    $stmt->execute([$cat, $slug]);
    echo "Created Category {$cat}<br>";
}

echo "<h3>Seeding demo dashboard orders...</h3>";
$demoUsers = [
    ['full_name' => 'Nguyễn Minh Anh', 'email' => 'demo.anh@example.com', 'phone' => '0901000001'],
    ['full_name' => 'Trần Hoàng Nam', 'email' => 'demo.nam@example.com', 'phone' => '0901000002'],
    ['full_name' => 'Lê Thu Hà', 'email' => 'demo.ha@example.com', 'phone' => '0901000003'],
];
$demoUserIds = [];
$userInsert = $pdo->prepare("INSERT INTO user (full_name, display_name, email, phone, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'user', 1, ?)");
foreach ($demoUsers as $demoUser) {
    $findUser = $pdo->prepare("SELECT id FROM user WHERE email = ? LIMIT 1");
    $findUser->execute([$demoUser['email']]);
    $userId = $findUser->fetchColumn();
    if (!$userId) {
        $userInsert->execute([
            $demoUser['full_name'],
            $demoUser['full_name'],
            $demoUser['email'],
            $demoUser['phone'],
            password_hash('Demo@123', PASSWORD_DEFAULT),
            date('Y-m-d H:i:s', strtotime('-10 days')),
        ]);
        $userId = $pdo->lastInsertId();
        echo "Created demo user {$demoUser['email']}<br>";
    }
    $demoUserIds[$demoUser['email']] = (int)$userId;
}

$variants = $pdo->query("SELECT pv.id AS variant_id, pv.product_id, pv.size, pv.color, p.name, (p.base_price + pv.price_modifier) AS unit_price
    FROM product_variants pv
    JOIN product p ON p.id = pv.product_id
    WHERE pv.status = 1 AND p.status = 1
    ORDER BY pv.id ASC
    LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

if (count($variants) < 1) {
    echo "Skipped demo orders: no active product variants found.<br>";
} else {
    $demoOrders = [
        ['code' => 'DEMO-' . date('Ymd', strtotime('-6 days')) . '-001', 'email' => 'demo.anh@example.com', 'days_ago' => 6, 'status' => 'completed', 'quantity' => 2],
        ['code' => 'DEMO-' . date('Ymd', strtotime('-4 days')) . '-002', 'email' => 'demo.nam@example.com', 'days_ago' => 4, 'status' => 'delivered', 'quantity' => 1],
        ['code' => 'DEMO-' . date('Ymd', strtotime('-2 days')) . '-003', 'email' => 'demo.ha@example.com', 'days_ago' => 2, 'status' => 'shipping', 'quantity' => 1],
        ['code' => 'DEMO-' . date('Ymd', strtotime('-1 day')) . '-004', 'email' => 'demo.anh@example.com', 'days_ago' => 1, 'status' => 'pending', 'quantity' => 1],
    ];
    $orderInsert = $pdo->prepare("INSERT INTO orders (order_code, user_id, total_amount, final_amount, shipping_fee, shipping_name, shipping_phone, shipping_address, shipping_status, status, shipping_email, terms_accepted, terms_accepted_at, shipped_at, delivered_at, completed_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)");
    $itemInsert = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price_at_time, product_name_snapshot, variant_size_snapshot, variant_color_snapshot)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $logInsert = $pdo->prepare("INSERT INTO order_status_logs (order_id, status, note, created_at) VALUES (?, ?, ?, ?)");
    $paymentInsert = $pdo->prepare("INSERT INTO payments (order_id, payment_method, payment_status, payment_state, paid_at) VALUES (?, 'cod', ?, ?, ?)");

    foreach ($demoOrders as $index => $demoOrder) {
        $findOrder = $pdo->prepare("SELECT id FROM orders WHERE order_code = ? LIMIT 1");
        $findOrder->execute([$demoOrder['code']]);
        if ($findOrder->fetchColumn()) {
            echo "Demo order {$demoOrder['code']} already exists.<br>";
            continue;
        }

        $variant = $variants[$index % count($variants)];
        $quantity = $demoOrder['quantity'];
        $subtotal = (float)$variant['unit_price'] * $quantity;
        $shippingFee = $subtotal >= 1000000 ? 0 : 30000;
        $finalAmount = $subtotal + $shippingFee;
        $createdAt = date('Y-m-d H:i:s', strtotime('-' . $demoOrder['days_ago'] . ' days'));
        $deliveredAt = in_array($demoOrder['status'], ['delivered', 'completed'], true) ? $createdAt : null;
        $completedAt = $demoOrder['status'] === 'completed' ? $createdAt : null;
        $shippedAt = in_array($demoOrder['status'], ['shipping', 'delivered', 'completed'], true) ? $createdAt : null;
        $paymentStatus = in_array($demoOrder['status'], ['delivered', 'completed'], true) ? 1 : 0;
        $paymentState = $paymentStatus ? 'paid' : 'pending';

        $orderInsert->execute([
            $demoOrder['code'],
            $demoUserIds[$demoOrder['email']],
            $subtotal,
            $finalAmount,
            $shippingFee,
            $demoUsers[array_search($demoOrder['email'], array_column($demoUsers, 'email'), true)]['full_name'],
            '0901000000',
            '123 Đường Demo, Quận 1, TP. Hồ Chí Minh',
            $shippedAt ? 'shipped' : 'not_shipped',
            $demoOrder['status'],
            $demoOrder['email'],
            $createdAt,
            $shippedAt,
            $deliveredAt,
            $completedAt,
            $createdAt,
        ]);
        $orderId = (int)$pdo->lastInsertId();
        $itemInsert->execute([$orderId, $variant['product_id'], $variant['variant_id'], $quantity, $variant['unit_price'], $variant['name'], $variant['size'], $variant['color']]);
        $logInsert->execute([$orderId, $demoOrder['status'], 'Demo dashboard order', $createdAt]);
        $paymentInsert->execute([$orderId, $paymentStatus, $paymentState, $paymentStatus ? $createdAt : null]);
        echo "Created demo order {$demoOrder['code']} ({$demoOrder['status']})<br>";
    }
}

echo "Done.";