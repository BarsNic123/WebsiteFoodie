<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();

$user = currentUser();
if ($user === null) {
    header('Location: login.php?next=my-orders.php');
    exit;
}

$config = require __DIR__ . '/config.php';
$orders = [];
$error = '';

if (empty($config['use_database'])) {
    $error = 'Order history requires MySQL. Enable use_database in config.php.';
} else {
    $pdo = db();
    $profile = $pdo->prepare('SELECT phone FROM users WHERE id = ? LIMIT 1');
    $profile->execute([$user['id']]);
    $profileRow = $profile->fetch() ?: [];
    $phone = trim((string) ($profileRow['phone'] ?? ''));

    $sql = 'SELECT o.id, o.delivery_address, o.payment_method, o.subtotal, o.delivery_fee,
                   o.total_amount, o.status, o.estimated_delivery, o.items_json, o.created_at,
                   r.name AS restaurant_name
            FROM orders o
            INNER JOIN restaurants r ON r.id = o.restaurant_id
            WHERE o.user_id = ?';
    $params = [$user['id']];
    if ($phone !== '') {
        $sql .= ' OR (o.user_id IS NULL AND o.contact_number = ?)';
        $params[] = $phone;
    }
    $sql .= ' ORDER BY o.id DESC LIMIT 50';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
}

function h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function formatPeso(int $amount): string
{
    return '₱' . number_format($amount, 2);
}

function statusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'Pending',
        'preparing' => 'Preparing',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

$safeName = h($user['name'] ?? 'User');

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - Foodie.PH</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="foodieph.css">
<style>
.orders-page { max-width: 900px; margin: 0 auto; padding: 32px 24px 64px; }
.orders-header { margin-bottom: 28px; }
.orders-header h1 { font-family: var(--font-display); font-size: 32px; font-style: italic; margin-bottom: 8px; }
.orders-header p { color: var(--muted); font-size: 14px; }
.orders-toolbar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
.orders-toolbar a {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 18px; border-radius: 100px; font-size: 13px; font-weight: 700;
  text-decoration: none; transition: all .18s;
}
.btn-back { background: #fff; border: 1.5px solid var(--border); color: var(--text); }
.btn-back:hover { border-color: var(--brand); color: var(--brand); }
.btn-shop { background: var(--brand); color: #fff; }
.btn-shop:hover { background: var(--brand-dark); }
.order-card {
  background: #fff; border: 1px solid var(--border); border-radius: var(--radius);
  padding: 20px 22px; margin-bottom: 16px; box-shadow: var(--shadow);
}
.order-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 14px; }
.order-id { font-weight: 800; font-size: 16px; }
.order-meta { font-size: 13px; color: var(--muted); margin-top: 4px; }
.order-status {
  font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px;
  padding: 6px 12px; border-radius: 100px; white-space: nowrap;
}
.status-pending { background: #fff3cd; color: #856404; }
.status-preparing { background: #cce5ff; color: #004085; }
.status-out_for_delivery { background: #d4edda; color: #155724; }
.status-delivered { background: #e8f8ee; color: #146c2f; }
.status-cancelled { background: #fde8e8; color: #a61b1d; }
.order-items { border-top: 1px solid var(--border); padding-top: 14px; }
.order-item-row { display: flex; justify-content: space-between; font-size: 14px; padding: 6px 0; }
.order-item-row span:last-child { font-weight: 600; color: var(--brand); }
.order-totals { border-top: 1px solid var(--border); margin-top: 12px; padding-top: 12px; }
.order-total-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; color: var(--muted); }
.order-total-row.grand { font-size: 16px; font-weight: 800; color: var(--text); margin-top: 8px; }
.orders-empty {
  text-align: center; padding: 48px 24px; background: #fff;
  border-radius: var(--radius); border: 1px dashed var(--border);
}
.orders-empty i { font-size: 48px; color: var(--muted); margin-bottom: 16px; }
.alert-box { padding: 14px 16px; border-radius: 10px; background: #fde8e8; color: #a61b1d; margin-bottom: 20px; font-size: 14px; }
</style>
</head>
<body>
<div class="topbar">
  📞 Globe: <strong>09177135477</strong> &nbsp;|&nbsp; Fast. Fresh. Nationwide Delivery &nbsp;|&nbsp;
  <span>Welcome, <strong><?= $safeName ?></strong></span>
</div>
<nav>
  <div class="nav-inner">
    <a href="index.php" class="logo">Foodie<span>.PH</span></a>
    <div class="nav-links"><a href="index.php">Home</a></div>
    <div class="nav-actions"><!--NAV_SESSION--></div>
  </div>
</nav>

<main class="orders-page">
  <div class="orders-header">
    <h1>My <em>Orders</em></h1>
    <p>Your past and current orders from Foodie.PH</p>
  </div>

  <div class="orders-toolbar">
    <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Continue shopping</a>
    <a href="index.php" class="btn-shop"><i class="fas fa-utensils"></i> Order food</a>
  </div>

  <?php if ($error !== ''): ?>
    <div class="alert-box"><?= h($error) ?></div>
  <?php elseif ($orders === []): ?>
    <div class="orders-empty">
      <i class="fas fa-receipt"></i>
      <h2 style="margin-bottom:8px">No orders yet</h2>
      <p style="color:var(--muted);margin-bottom:20px">Place an order while logged in and it will show up here.</p>
      <a href="index.php" class="btn-shop" style="display:inline-flex">Browse restaurants</a>
    </div>
  <?php else: ?>
    <?php foreach ($orders as $o):
      $items = json_decode((string) $o['items_json'], true);
      if (!is_array($items)) {
          $items = [];
      }
      $status = (string) $o['status'];
      $statusClass = 'status-' . preg_replace('/[^a-z_]/', '', $status);
    ?>
    <article class="order-card">
      <div class="order-card-head">
        <div>
          <div class="order-id">Order #<?= (int) $o['id'] ?></div>
          <div class="order-meta">
            <?= h($o['restaurant_name']) ?> &nbsp;·&nbsp;
            <?= h(date('M j, Y g:i A', strtotime((string) $o['created_at']))) ?>
          </div>
          <div class="order-meta">Payment: <?= h($o['payment_method']) ?> &nbsp;·&nbsp; <?= h($o['estimated_delivery']) ?></div>
        </div>
        <span class="order-status <?= h($statusClass) ?>"><?= h(statusLabel($status)) ?></span>
      </div>
      <div class="order-meta" style="margin-bottom:10px">
        <i class="fas fa-location-dot"></i> <?= h($o['delivery_address']) ?>
      </div>
      <div class="order-items">
        <?php foreach ($items as $item): ?>
        <div class="order-item-row">
          <span><?= h((string) ($item['name'] ?? 'Item')) ?> × <?= (int) ($item['quantity'] ?? 1) ?></span>
          <span>₱<?= number_format((float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1), 2) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="order-totals">
        <div class="order-total-row"><span>Subtotal</span><span><?= h(formatPeso((int) $o['subtotal'])) ?></span></div>
        <div class="order-total-row"><span>Delivery fee</span><span><?= h(formatPeso((int) $o['delivery_fee'])) ?></span></div>
        <div class="order-total-row grand"><span>Total</span><span><?= h(formatPeso((int) $o['total_amount'])) ?></span></div>
      </div>
    </article>
    <?php endforeach; ?>
  <?php endif; ?>
</main>
</body>
</html>
<?php
$html = ob_get_clean();
echo applySessionNav($html, 'checkout');
