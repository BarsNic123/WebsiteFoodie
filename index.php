<?php
/**
 * Entry point for XAMPP: http://localhost/WebsiteFoodie/
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/nav.php';

$html = file_get_contents(__DIR__ . '/foodieph.html');
if ($html === false) {
    http_response_code(500);
    echo 'Could not load homepage.';
    exit;
}

$html = applySessionNav($html, 'home');

$user = currentUser();
$cartOrdersLink = '';
$isLoggedInJs = '<script>window.IS_LOGGED_IN = false;</script>';
if ($user !== null) {
    $cartOrdersLink = '<a href="my-orders.php" class="cart-orders-link" style="display:block;text-align:center;margin-bottom:12px;font-size:13px;font-weight:700;color:var(--brand);text-decoration:none"><i class="fas fa-receipt"></i> View my past orders</a>';
    $isLoggedInJs = '<script>window.IS_LOGGED_IN = true;</script>';
}
$html = str_replace('<!--CART_MY_ORDERS_LINK-->', $cartOrdersLink, $html);
$html = str_replace('</body>', $isLoggedInJs . "\n</body>", $html);

echo $html;
