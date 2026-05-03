<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    $root = dirname(__DIR__);
    $configFile = $root . '/config.php';
    $config = file_exists($configFile) ? require $configFile : ['use_database' => false];

    if (!empty($config['use_database'])) {
        require_once $root . '/includes/db.php';
        $payload = fetchFromDatabase();
    } else {
        $payload = fetchFromJson($root . '/data.json');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * @return array{categories: array, restaurants: array}
 */
function fetchFromJson(string $path): array
{
    if (!is_readable($path)) {
        throw new RuntimeException('data.json not found or not readable');
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Could not read data.json');
    }
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!isset($data['categories'], $data['restaurants']) || !is_array($data['categories']) || !is_array($data['restaurants'])) {
        throw new RuntimeException('data.json must contain categories and restaurants arrays');
    }
    return $data;
}

/**
 * @return array{categories: array, restaurants: array}
 */
function fetchFromDatabase(): array
{
    $pdo = db();

    $catStmt = $pdo->query(
        'SELECT id, name, icon, filter_key AS `filter` FROM categories ORDER BY id'
    );
    $categories = [];
    foreach ($catStmt->fetchAll() as $row) {
        $categories[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'icon' => $row['icon'],
            'filter' => $row['filter'],
        ];
    }

    $restStmt = $pdo->query(
        'SELECT id, name, image, rating, delivery_time, delivery_fee, cuisines_json, tag, tag_style, category, is_open
         FROM restaurants ORDER BY id'
    );

    $byId = [];
    while ($row = $restStmt->fetch()) {
        $id = (int) $row['id'];
        $byId[$id] = [
            'id' => $id,
            'name' => $row['name'],
            'image' => $row['image'],
            'rating' => (float) $row['rating'],
            'deliveryTime' => $row['delivery_time'],
            'deliveryFee' => (int) $row['delivery_fee'],
            'cuisines' => json_decode($row['cuisines_json'], true, 512, JSON_THROW_ON_ERROR),
            'tag' => $row['tag'],
            'tagStyle' => $row['tag_style'],
            'category' => $row['category'],
            'open' => (bool) (int) $row['is_open'],
            'menu' => [],
        ];
    }

    $menuStmt = $pdo->query(
        'SELECT id, restaurant_id, name, description, price FROM menu_items ORDER BY restaurant_id, id'
    );
    while ($m = $menuStmt->fetch()) {
        $rid = (int) $m['restaurant_id'];
        if (!isset($byId[$rid])) {
            continue;
        }
        $byId[$rid]['menu'][] = [
            'id' => (int) $m['id'],
            'name' => $m['name'],
            'description' => $m['description'],
            'price' => (int) $m['price'],
        ];
    }

    return [
        'categories' => $categories,
        'restaurants' => array_values($byId),
    ];
}
