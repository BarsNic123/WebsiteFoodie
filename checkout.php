<?php
declare(strict_types=1);

/**
 * Checkout page entry point.
 * GET  → checkout form (session-aware nav, no Admin link)
 * POST → save order to MySQL (JSON body)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api/place-order.php';
    exit;
}

$configFile = __DIR__ . '/config.php';
$dbError = null;

if (!is_readable($configFile)) {
    $dbError = 'Database is not configured. Copy <code>config.sample.php</code> to <code>config.php</code> in the project folder.';
} else {
    $config = require $configFile;
    if (empty($config['use_database'])) {
        $dbError = 'Orders are not saved until you set <code>use_database => true</code> in config.php.';
    } else {
        try {
            require_once __DIR__ . '/includes/db.php';
            db()->query('SELECT 1 FROM orders LIMIT 1');
        } catch (Throwable $e) {
            $dbError = 'Cannot connect to MySQL or the orders table is missing. Start MySQL in XAMPP and import <code>sql/schema.sql</code>.';
        }
    }
}

if ($dbError !== null) {
    header('Content-Type: text/html; charset=utf-8');
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Checkout unavailable</title>';
    echo '<link rel="stylesheet" href="foodieph.css"></head><body style="padding:40px;max-width:600px;margin:0 auto">';
    echo '<h1>Checkout unavailable</h1><p>' . $dbError . '</p>';
    echo '<p><a href="index.php">← Back to home</a></p></body></html>';
    exit;
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/nav.php';

$html = file_get_contents(__DIR__ . '/checkout.html');
if ($html === false) {
    http_response_code(500);
    echo 'Could not load checkout page.';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
echo applySessionNav($html, 'checkout');
