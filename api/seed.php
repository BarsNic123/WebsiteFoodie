<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$configFile = $root . '/config.php';
if (!is_readable($configFile)) { fwrite(STDERR, "Missing config.php\n"); exit(1); }
$config = require $configFile;
if (empty($config['use_database'])) { fwrite(STDERR, "Set use_database => true in config.php\n"); exit(1); }
require_once $root . '/includes/db.php';
$data = json_decode(file_get_contents($root . '/data.json'), true, 512, JSON_THROW_ON_ERROR);
$pdo = db();

// ?? Run DDL fixes BEFORE transaction (ALTER causes implicit commit) ??
$existingCols = array_column($pdo->query('SHOW COLUMNS FROM menu_items')->fetchAll(), 'Field');
if (!in_array('is_available', $existingCols, true)) {
    $pdo->exec('ALTER TABLE menu_items ADD COLUMN is_available TINYINT(1) NOT NULL DEFAULT 1 AFTER price');
    echo "Added column: menu_items.is_available\n";
}
if (!in_array('sort_order', $existingCols, true)) {
    $pdo->exec('ALTER TABLE menu_items ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER is_available');
    echo "Added column: menu_items.sort_order\n";
}
$userCols = array_column($pdo->query('SHOW COLUMNS FROM users')->fetchAll(), 'Field');
if (!in_array('first_name', $userCols, true)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(80) NOT NULL DEFAULT '' AFTER id");
    echo "Added column: users.first_name\n";
}
if (!in_array('last_name', $userCols, true)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(80) NOT NULL DEFAULT '' AFTER first_name");
    echo "Added column: users.last_name\n";
}
if (!in_array('phone', $userCols, true)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NOT NULL DEFAULT '' AFTER email");
    echo "Added column: users.phone\n";
}
// Shipping address columns
$addressCols = [
    'street_address' => "ALTER TABLE users ADD COLUMN street_address VARCHAR(300) NOT NULL DEFAULT '' AFTER phone",
    'unit'           => "ALTER TABLE users ADD COLUMN unit VARCHAR(100) NOT NULL DEFAULT '' AFTER street_address",
    'city'           => "ALTER TABLE users ADD COLUMN city VARCHAR(100) NOT NULL DEFAULT '' AFTER unit",
    'state'          => "ALTER TABLE users ADD COLUMN state VARCHAR(100) NOT NULL DEFAULT '' AFTER city",
    'postal_code'    => "ALTER TABLE users ADD COLUMN postal_code VARCHAR(20) NOT NULL DEFAULT '' AFTER state",
    'country'        => "ALTER TABLE users ADD COLUMN country VARCHAR(100) NOT NULL DEFAULT 'Philippines' AFTER postal_code",
];
foreach ($addressCols as $col => $sql) {
    if (!in_array($col, $userCols, true)) {
        $pdo->exec($sql);
        echo "Added column: users.{$col}\n";
    }
}
if (!in_array('email_notifications', $userCols, true)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) NOT NULL DEFAULT 1 AFTER role");
    echo "Added column: users.email_notifications\n";
}
if (!in_array('is_active', $userCols, true)) {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER email_notifications");
    echo "Added column: users.is_active\n";
}

$roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
if ($roleColumn && strpos($roleColumn['Type'] ?? '', 'rider') === false) {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','user','rider') NOT NULL DEFAULT 'user'");
    echo "Updated users.role to include rider\n";
}

if ($pdo->query("SHOW TABLES LIKE 'riders'")->fetch() === false) {
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS riders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  driver_license VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Driver license or permit number',
  vehicle_type VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Motorcycle, tricycle, bicycle, etc.',
  vehicle_plate VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Vehicle plate number',
  preferred_city VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Primary delivery city',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rider_user (user_id),
  KEY idx_preferred_city (preferred_city),
  CONSTRAINT fk_riders_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
SQL
    );
    echo "Created table: riders\n";
}

// ?? Now run data inserts inside a transaction ??
$pdo->beginTransaction();
try {
    $pdo->exec('DELETE FROM menu_items');
    $pdo->exec('DELETE FROM restaurants');
    $pdo->exec('DELETE FROM categories');

    $ic = $pdo->prepare('INSERT INTO categories (id, name, icon, filter_key) VALUES (?, ?, ?, ?)');
    foreach ($data['categories'] as $c) {
        $ic->execute([(int)$c['id'], $c['name'], $c['icon'], $c['filter']]);
    }

    $ir = $pdo->prepare('INSERT INTO restaurants (id, name, image, rating, delivery_time, delivery_fee, cuisines_json, tag, tag_style, category, is_open) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($data['restaurants'] as $r) {
        $ir->execute([(int)$r['id'], $r['name'], $r['image'], $r['rating'], $r['deliveryTime'], (int)$r['deliveryFee'], json_encode($r['cuisines'], JSON_UNESCAPED_UNICODE), $r['tag'], $r['tagStyle'] ?? '', $r['category'], !empty($r['open']) ? 1 : 0]);
    }

    $im = $pdo->prepare('INSERT INTO menu_items (id, restaurant_id, name, description, price, is_available, sort_order) VALUES (?, ?, ?, ?, ?, 1, ?)');
    foreach ($data['restaurants'] as $r) {
        $rid = (int)$r['id']; $order = 0;
        foreach ($r['menu'] as $item) {
            $im->execute([(int)$item['id'], $rid, $item['name'], $item['description'], (int)$item['price'], ++$order]);
        }
    }

    $pdo->exec("INSERT IGNORE INTO delivery_zones (name, slug, icon, is_active, sort_order) VALUES ('Metro Manila','metro-manila','',1,1),('Cebu','cebu','',1,2),('Nationwide Delivery','nationwide','',1,3)");

    $adminEmail = 'admin@foodieph.com';
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$adminEmail]);
    if (!$check->fetch()) {
        $ins = $pdo->prepare('INSERT INTO users (first_name, last_name, name, email, phone, password_hash, role) VALUES (?,?,?,?,?,?,?)');
        $ins->execute(['Foodie','Admin','Foodie Admin',$adminEmail,'',password_hash('admin123',PASSWORD_DEFAULT),'admin']);
        echo "Admin created: {$adminEmail} / admin123\n";
    } else {
        echo "Admin already exists: {$adminEmail}\n";
    }

    $pdo->commit();
    echo "Seed completed successfully.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
