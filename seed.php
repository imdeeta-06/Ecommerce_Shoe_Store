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

echo "Done.";