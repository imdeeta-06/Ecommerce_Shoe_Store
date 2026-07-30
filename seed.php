<?php
// Run once: php seed.php OR visit in browser to create admin & user accounts
require_once 'config/db.php';

$users = [
    ['full_name' => 'Admin', 'email' => 'admin@123', 'password' => 'Demo@123', 'role' => 'admin'],
    ['full_name' => 'User', 'email' => 'user@123', 'password' => 'Demo@123', 'role' => 'user'],
];

foreach ($users as $u) {
    $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->execute([$u['email']]);
    if ($stmt->fetch()) {
        echo "User {$u['email']} already exists.<br>";
        continue;
    }
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO user (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$u['full_name'], $u['email'], $hash, $u['role']]);
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