<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

// Enable CORS for local development
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

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new RuntimeException('Invalid JSON data');
    }

    // Validate required fields
    $required = ['full_name', 'contact_number', 'delivery_address', 'payment_method', 'cart', 'restaurant_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new RuntimeException("Missing required field: {$field}");
        }
    }

    // Validate cart data
    if (!is_array($data['cart']) || empty($data['cart'])) {
        throw new RuntimeException('Cart cannot be empty');
    }

    // Get current user (optional - allow guest orders)
    $user = currentUser();
    $userId = $user ? $user['id'] : null;

    // Calculate totals
    $subtotal = 0;
    foreach ($data['cart'] as $item) {
        if (!isset($item['price']) || !isset($item['quantity'])) {
            throw new RuntimeException('Invalid cart item data');
        }
        $subtotal += (float)$item['price'] * (int)$item['quantity'];
    }

    // Get restaurant info for delivery fee
    $stmt = db()->prepare('SELECT delivery_fee FROM restaurants WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$data['restaurant_id']]);
    $restaurant = $stmt->fetch();
    
    if (!$restaurant) {
        throw new RuntimeException('Restaurant not found');
    }

    $deliveryFee = (float)$restaurant['delivery_fee'];
    $totalAmount = $subtotal + $deliveryFee;

    // Prepare items JSON
    $itemsJson = json_encode($data['cart'], JSON_UNESCAPED_UNICODE);

    // Insert order
    $insertStmt = db()->prepare('
        INSERT INTO orders (
            user_id, 
            restaurant_id, 
            items_json, 
            subtotal, 
            delivery_fee, 
            total_amount, 
            delivery_address, 
            contact_number, 
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $insertStmt->execute([
        $userId,
        (int)$data['restaurant_id'],
        $itemsJson,
        (int)($subtotal * 100), // Convert to cents
        (int)($deliveryFee * 100),
        (int)($totalAmount * 100),
        $data['delivery_address'],
        $data['contact_number'],
        'pending'
    ]);

    $orderId = (int)db()->lastInsertId();

    // Log additional order notes if provided
    if (!empty($data['delivery_notes']) || !empty($data['order_notes'])) {
        $notes = [
            'delivery_notes' => $data['delivery_notes'] ?? '',
            'order_notes' => $data['order_notes'] ?? '',
            'payment_method' => $data['payment_method']
        ];
        
        // You could store these in a separate order_notes table if needed
        // For now, we'll just include them in the response
    }

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Order placed successfully',
        'total_amount' => number_format($totalAmount, 2),
        'estimated_delivery' => '60-90 minutes'
    ]);

} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Checkout error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
