<?php
/**
 * One-time import of data.json into MySQL.
 * 1. Import sql/schema.sql (create database + tables).
 * 2. Copy config.sample.php to config.php and set use_database => true.
 * 3. From project root: php api/seed.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$configFile = $root . '/config.php';
if (!is_readable($configFile)) {
    fwrite(STDERR, "Missing config.php — copy config.sample.php to config.php and set use_database => true.\n");
    exit(1);
}
$config = require $configFile;
if (empty($config['use_database'])) {
    fwrite(STDERR, "Set use_database => true in config.php before seeding.\n");
    exit(1);
}

require_once $root . '/includes/db.php';

$data = json_decode(file_get_contents($root . '/data.json'), true, 512, JSON_THROW_ON_ERROR);
$pdo = db();

$pdo->beginTransaction();
try {
    $pdo->exec('DELETE FROM menu_items');
    $pdo->exec('DELETE FROM restaurants');
    $pdo->exec('DELETE FROM categories');

    $ic = $pdo->prepare(
        'INSERT INTO categories (id, name, icon, filter_key) VALUES (?, ?, ?, ?)'
    );
    foreach ($data['categories'] as $c) {
        $ic->execute([
            (int) $c['id'],
            $c['name'],
            $c['icon'],
            $c['filter'],
        ]);
    }

    $ir = $pdo->prepare(
        'INSERT INTO restaurants (id, name, image, rating, delivery_time, delivery_fee, cuisines_json, tag, tag_style, category, is_open)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($data['restaurants'] as $r) {
        $ir->execute([
            (int) $r['id'],
            $r['name'],
            $r['image'],
            $r['rating'],
            $r['deliveryTime'],
            (int) $r['deliveryFee'],
            json_encode($r['cuisines'], JSON_UNESCAPED_UNICODE),
            $r['tag'],
            $r['tagStyle'] ?? '',
            $r['category'],
            !empty($r['open']) ? 1 : 0,
        ]);
    }

    $im = $pdo->prepare(
        'INSERT INTO menu_items (id, restaurant_id, name, description, price, is_available, sort_order)
         VALUES (?, ?, ?, ?, ?, 1, ?)'
    );
    foreach ($data['restaurants'] as $r) {
        $rid   = (int) $r['id'];
        $order = 0;
        foreach ($r['menu'] as $item) {
            $im->execute([
                (int) $item['id'],
                $rid,
                $item['name'],
                $item['description'],
                (int) $item['price'],
                ++$order,
            ]);
        }
    }

    // Seed delivery zones
    $pdo->exec("INSERT IGNORE INTO delivery_zones (name, slug, icon, is_active, sort_order) VALUES
        ('Metro Manila',        'metro-manila', '🏙️', 1, 1),
        ('Cebu',                'cebu',         '🏖️', 1, 2),
        ('Nationwide Delivery', 'nationwide',   '🚚', 1, 3)");

    // Seed default admin
    $defaultAdminEmail = 'admin@foodieph.com';
    $checkAdmin = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $checkAdmin->execute([$defaultAdminEmail]);
    if (!$checkAdmin->fetch()) {
        $addAdmin = $pdo->prepare(
            'INSERT INTO users (first_name, last_name, name, email, phone, password_hash, role)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $addAdmin->execute([
            'Foodie', 'Admin', 'Foodie Admin',
            $defaultAdminEmail, '',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin',
        ]);
        echo "Default admin created: {$defaultAdminEmail} / admin123\n";
    }

    $pdo->commit();
    echo "Seed completed.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
