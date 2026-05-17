<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$configFile = dirname(__DIR__) . '/config.php';
if (!is_readable($configFile)) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'Server not configured. Copy config.sample.php to config.php.',
    ]);
    exit;
}

$config = require $configFile;
if (empty($config['use_database'])) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'Orders require MySQL. Set use_database => true in config.php.',
    ]);
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

/** @var PDO|null $pdo */
$pdo = null;

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input ?: '', true);

    if (!is_array($data)) {
        throw new RuntimeException('Invalid JSON data');
    }

    $required = ['full_name', 'contact_number', 'delivery_address', 'payment_method', 'cart', 'restaurant_id'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            throw new RuntimeException("Missing required field: {$field}");
        }
    }

    if (!is_array($data['cart']) || $data['cart'] === []) {
        throw new RuntimeException('Cart cannot be empty');
    }

    $userId = resolveOrderUserId();

    $subtotal = 0;
    foreach ($data['cart'] as $item) {
        if (!isset($item['price'], $item['quantity'])) {
            throw new RuntimeException('Invalid cart item data');
        }
        $qty = (int) $item['quantity'];
        if ($qty < 1) {
            throw new RuntimeException('Invalid item quantity');
        }
        $subtotal += (float) $item['price'] * $qty;
    }

    $restaurantId = (int) $data['restaurant_id'];
    if ($restaurantId < 1) {
        throw new RuntimeException('Invalid restaurant');
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, delivery_fee FROM restaurants WHERE id = ? LIMIT 1');
    $stmt->execute([$restaurantId]);
    $restaurant = $stmt->fetch();

    if (!$restaurant) {
        throw new RuntimeException(
            'This restaurant is no longer available. Clear your cart, refresh the homepage, and add items again.'
        );
    }

    $deliveryFee = (int) $restaurant['delivery_fee'];
    $subtotalInt = (int) round($subtotal);
    $totalAmount = $subtotalInt + $deliveryFee;

    $itemsJson = json_encode($data['cart'], JSON_UNESCAPED_UNICODE);
    if ($itemsJson === false) {
        throw new RuntimeException('Could not encode cart items');
    }

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare('
        INSERT INTO orders (
            user_id,
            restaurant_id,
            full_name,
            contact_number,
            delivery_address,
            delivery_notes,
            payment_method,
            order_notes,
            items_json,
            subtotal,
            delivery_fee,
            total_amount,
            status,
            estimated_delivery
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $insertStmt->execute([
        $userId,
        $restaurantId,
        trim((string) $data['full_name']),
        trim((string) $data['contact_number']),
        trim((string) $data['delivery_address']),
        trim((string) ($data['delivery_notes'] ?? '')),
        trim((string) $data['payment_method']),
        trim((string) ($data['order_notes'] ?? '')),
        $itemsJson,
        $subtotalInt,
        $deliveryFee,
        $totalAmount,
        'pending',
        '60-90 minutes',
    ]);

    $orderId = (int) $pdo->lastInsertId();
    if ($orderId < 1) {
        throw new RuntimeException('Order was not saved to the database');
    }

    $historyStmt = $pdo->prepare(
        'INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note)
         VALUES (?, ?, ?, ?, ?)'
    );
    $historyStmt->execute([$orderId, '', 'pending', $userId, 'Order placed']);

    $verifyStmt = $pdo->prepare('SELECT id, total_amount FROM orders WHERE id = ? LIMIT 1');
    $verifyStmt->execute([$orderId]);
    $saved = $verifyStmt->fetch();
    if (!$saved) {
        throw new RuntimeException('Could not verify order in database');
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Order saved to database',
        'total_amount' => number_format((int) $saved['total_amount'], 2),
        'estimated_delivery' => '60-90 minutes',
    ], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Place order PDO error: ' . $e->getMessage());
    $msg = $e->getMessage();
    if (str_contains($msg, 'fk_order_user') || str_contains($msg, '1452')) {
        $friendly = 'Your login session is out of date. Log out, refresh the page, and place the order again (or order as a guest).';
    } elseif (str_contains($msg, 'fk_order_restaurant')) {
        $friendly = 'That restaurant is not in the database. Clear your cart, run seed if needed, and add items again from the homepage.';
    } else {
        $friendly = 'Could not save order to the database. Please try again.';
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $friendly]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Place order error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save order. Check MySQL is running.']);
}
