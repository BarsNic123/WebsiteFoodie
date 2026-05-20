<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireAdmin();
$authUser = currentUser();

$root = __DIR__;
$configFile = $root . '/config.php';
$errors = [];
$success = '';  

if (!is_readable($configFile)) {
    $errors[] = 'Missing config.php. Copy config.sample.php to config.php first.';
}

$config = is_readable($configFile) ? require $configFile : [];
if (empty($config['use_database'])) {
    $errors[] = "Set 'use_database' => true in config.php to use admin.";
}

if (empty($errors)) {
    require_once $root . '/includes/db.php';
}

function nextId(PDO $pdo, string $table): int
{
    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM {$table}");
    $row = $stmt->fetch();
    return (int) ($row['next_id'] ?? 1);
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
        $pdo = db();
        $action = $_POST['action'] ?? '';

        if ($action === 'create_category') {
            $id = nextId($pdo, 'categories');
            $name = trim((string) ($_POST['name'] ?? ''));
            $icon = trim((string) ($_POST['icon'] ?? ''));
            $filter = trim((string) ($_POST['filter_key'] ?? ''));
            if ($name === '' || $icon === '' || $filter === '') {
                throw new RuntimeException('Category fields are required.');
            }
            $stmt = $pdo->prepare('INSERT INTO categories (id, name, icon, filter_key) VALUES (?, ?, ?, ?)');
            $stmt->execute([$id, $name, $icon, $filter]);
            $success = "Category #{$id} created.";
        } elseif ($action === 'update_category') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $icon = trim((string) ($_POST['icon'] ?? ''));
            $filter = trim((string) ($_POST['filter_key'] ?? ''));
            $stmt = $pdo->prepare('UPDATE categories SET name = ?, icon = ?, filter_key = ? WHERE id = ?');
            $stmt->execute([$name, $icon, $filter, $id]);
            $success = "Category #{$id} updated.";
        } elseif ($action === 'delete_category') {
            $id = (int) ($_POST['id'] ?? 0);
            $restStmt = $pdo->prepare('SELECT COUNT(*) AS c FROM restaurants WHERE category = (SELECT filter_key FROM categories WHERE id = ?)');
            $restStmt->execute([$id]);
            $count = (int) ($restStmt->fetch()['c'] ?? 0);
            if ($count > 0) {
                throw new RuntimeException('Cannot delete category used by restaurants.');
            }
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            $success = "Category #{$id} deleted.";
        } elseif ($action === 'create_restaurant') {
            $id = nextId($pdo, 'restaurants');
            $name = trim((string) ($_POST['name'] ?? ''));
            $image = trim((string) ($_POST['image'] ?? ''));
            $rating = (float) ($_POST['rating'] ?? 0);
            $deliveryTime = trim((string) ($_POST['delivery_time'] ?? ''));
            $deliveryFee = (int) ($_POST['delivery_fee'] ?? 0);
            $cuisinesRaw = trim((string) ($_POST['cuisines'] ?? ''));
            $tag = trim((string) ($_POST['tag'] ?? ''));
            $tagStyle = trim((string) ($_POST['tag_style'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $isOpen = isset($_POST['is_open']) ? 1 : 0;
            if ($name === '' || $image === '' || $deliveryTime === '' || $category === '') {
                throw new RuntimeException('Restaurant required fields are missing.');
            }
            $cuisines = array_values(array_filter(array_map('trim', explode(',', $cuisinesRaw))));
            $stmt = $pdo->prepare(
                'INSERT INTO restaurants (id, name, image, rating, delivery_time, delivery_fee, cuisines_json, tag, tag_style, category, is_open)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$id, $name, $image, $rating, $deliveryTime, $deliveryFee, json_encode($cuisines, JSON_UNESCAPED_UNICODE), $tag, $tagStyle, $category, $isOpen]);
            $success = "Restaurant #{$id} created.";
        } elseif ($action === 'update_restaurant') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $image = trim((string) ($_POST['image'] ?? ''));
            $rating = (float) ($_POST['rating'] ?? 0);
            $deliveryTime = trim((string) ($_POST['delivery_time'] ?? ''));
            $deliveryFee = (int) ($_POST['delivery_fee'] ?? 0);
            $cuisinesRaw = trim((string) ($_POST['cuisines'] ?? ''));
            $tag = trim((string) ($_POST['tag'] ?? ''));
            $tagStyle = trim((string) ($_POST['tag_style'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $isOpen = isset($_POST['is_open']) ? 1 : 0;
            $cuisines = array_values(array_filter(array_map('trim', explode(',', $cuisinesRaw))));
            $stmt = $pdo->prepare(
                'UPDATE restaurants
                 SET name = ?, image = ?, rating = ?, delivery_time = ?, delivery_fee = ?, cuisines_json = ?, tag = ?, tag_style = ?, category = ?, is_open = ?
                 WHERE id = ?'
            );
            $stmt->execute([$name, $image, $rating, $deliveryTime, $deliveryFee, json_encode($cuisines, JSON_UNESCAPED_UNICODE), $tag, $tagStyle, $category, $isOpen, $id]);
            $success = "Restaurant #{$id} updated.";
        } elseif ($action === 'delete_restaurant') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM restaurants WHERE id = ?');
            $stmt->execute([$id]);
            $success = "Restaurant #{$id} deleted.";
        } elseif ($action === 'create_menu_item') {
            $id = nextId($pdo, 'menu_items');
            $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = (int) ($_POST['price'] ?? 0);
            $stmt = $pdo->prepare('INSERT INTO menu_items (id, restaurant_id, name, description, price) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$id, $restaurantId, $name, $description, $price]);
            $success = "Menu item #{$id} created.";
        } elseif ($action === 'update_menu_item') {
            $id = (int) ($_POST['id'] ?? 0);
            $restaurantId = (int) ($_POST['restaurant_id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = (int) ($_POST['price'] ?? 0);
            $stmt = $pdo->prepare('UPDATE menu_items SET restaurant_id = ?, name = ?, description = ?, price = ? WHERE id = ?');
            $stmt->execute([$restaurantId, $name, $description, $price, $id]);
            $success = "Menu item #{$id} updated.";
        } elseif ($action === 'delete_menu_item') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM menu_items WHERE id = ?');
            $stmt->execute([$id]);
            $success = "Menu item #{$id} deleted.";
        } elseif ($action === 'update_order_status') {
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $newStatus = trim((string) ($_POST['status'] ?? ''));
            $allowed = ['pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
            if (!in_array($newStatus, $allowed, true)) {
                throw new RuntimeException('Invalid order status.');
            }
            $cur = $pdo->prepare('SELECT status FROM orders WHERE id = ? LIMIT 1');
            $cur->execute([$orderId]);
            $row = $cur->fetch();
            if (!$row) {
                throw new RuntimeException('Order not found.');
            }
            $oldStatus = (string) $row['status'];
            $upd = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $upd->execute([$newStatus, $orderId]);
            $hist = $pdo->prepare(
                'INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $hist->execute([$orderId, $oldStatus, $newStatus, (int) ($authUser['id'] ?? 0), 'Updated in admin']);
            $success = "Order #{$orderId} marked as {$newStatus}.";
        } elseif ($action === 'update_rider_status') {
            $riderId = (int) ($_POST['rider_id'] ?? 0);
            $newStatus = trim((string) ($_POST['status'] ?? ''));
            if (!in_array($newStatus, ['approved', 'rejected', 'pending'], true)) {
                throw new RuntimeException('Invalid rider status.');
            }
            $stmt = $pdo->prepare('UPDATE riders SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $riderId]);
            $success = "Rider #{$riderId} status updated to {$newStatus}.";
        } elseif ($action === 'update_application_status') {
            $appId = (int) ($_POST['application_id'] ?? 0);
            $newStatus = trim((string) ($_POST['status'] ?? ''));
            if (!in_array($newStatus, ['approved', 'rejected', 'pending', 'under_review'], true)) {
                throw new RuntimeException('Invalid application status.');
            }
            
            if ($newStatus === 'approved') {
                $appStmt = $pdo->prepare('SELECT * FROM restaurant_applications WHERE id = ?');
                $appStmt->execute([$appId]);
                $app = $appStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($app && $app['status'] !== 'approved') {
                    $pdo->beginTransaction();
                    try {
                        // 1. Create User
                        $userStmt = $pdo->prepare('INSERT INTO users (name, first_name, last_name, email, phone, password_hash, role, street_address, city, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
                        $userStmt->execute([
                            $app['owner_name'],
                            $app['owner_first_name'],
                            $app['owner_last_name'],
                            $app['owner_email'],
                            $app['owner_phone'],
                            $app['password_hash'],
                            'restaurant',
                            $app['business_address'],
                            $app['city']
                        ]);
                        $userId = $pdo->lastInsertId();
                        
                        // 2. Create Restaurant
                        $restStmt = $pdo->prepare('INSERT INTO restaurants (owner_user_id, name, category, business_address, city, operating_hours, delivery_time, delivery_fee, cuisines_json, is_open) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
                        $restStmt->execute([
                            $userId,
                            $app['restaurant_name'],
                            $app['cuisine_type'],
                            $app['business_address'],
                            $app['city'],
                            $app['operating_hours'],
                            $app['avg_delivery_time'],
                            $app['delivery_fee'],
                            json_encode([$app['cuisine_type']])
                        ]);
                        
                        // 3. Update application status
                        $updStmt = $pdo->prepare('UPDATE restaurant_applications SET status = ? WHERE id = ?');
                        $updStmt->execute([$newStatus, $appId]);
                        
                        $pdo->commit();
                        $success = "Application #{$appId} approved! User and Restaurant accounts generated.";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw new RuntimeException('Failed to provision restaurant: ' . $e->getMessage());
                    }
                } else {
                    $stmt = $pdo->prepare('UPDATE restaurant_applications SET status = ? WHERE id = ?');
                    $stmt->execute([$newStatus, $appId]);
                    $success = "Application #{$appId} status updated.";
                }
            } else {
                $stmt = $pdo->prepare('UPDATE restaurant_applications SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $appId]);
                $success = "Application #{$appId} status updated to {$newStatus}.";
            }
        }
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$categories = [];
$restaurants = [];
$menuItems = [];
$ridersList = [];
$orders = [];
if (empty($errors)) {
    $pdo = db();
    $categories = $pdo->query('SELECT id, name, icon, filter_key FROM categories ORDER BY id')->fetchAll();
    $restaurants = $pdo->query('SELECT id, name, image, rating, delivery_time, delivery_fee, cuisines_json, tag, tag_style, category, is_open FROM restaurants ORDER BY id')->fetchAll();
    $menuItems = $pdo->query('SELECT id, restaurant_id, name, description, price FROM menu_items ORDER BY id')->fetchAll();
    $ridersList = $pdo->query('SELECT u.id as user_id, u.name, u.email, u.phone, r.id as rider_id, r.driver_license, r.vehicle_type, r.vehicle_plate, r.preferred_city, r.license_front_url, r.license_back_url, r.status, r.created_at FROM users u JOIN riders r ON u.id = r.user_id ORDER BY r.created_at DESC')->fetchAll();
    $orders = $pdo->query(
        'SELECT o.id, o.full_name, o.contact_number, o.delivery_address, o.payment_method,
                o.subtotal, o.delivery_fee, o.total_amount, o.status, o.created_at,
                r.name AS restaurant_name
         FROM orders o
         INNER JOIN restaurants r ON r.id = o.restaurant_id
         ORDER BY o.id DESC
         LIMIT 100'
    )->fetchAll();
    
    $applications = $pdo->query('SELECT id, restaurant_name, cuisine_type, owner_name, owner_email, owner_phone, city, status, created_at, legitimacy_doc_url FROM restaurant_applications ORDER BY created_at DESC')->fetchAll();
}

function formatPeso(int $amount): string
{
    return '₱' . number_format($amount, 2);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Foodie Admin</title>
  <style>
    :root { --bg:#111827; --card:#1f2937; --text:#f3f4f6; --muted:#9ca3af; --ok:#16a34a; --bad:#dc2626; --line:#374151; --accent:#f97316; }
    * { box-sizing: border-box; }
    body { margin:0; font-family: Arial, sans-serif; background:var(--bg); color:var(--text); }
    .wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
    h1 { margin:0 0 8px; }
    p { color: var(--muted); }
    .msg { padding:10px 12px; border-radius:8px; margin:12px 0; }
    .ok { background:#14532d; color:#dcfce7; }
    .bad { background:#7f1d1d; color:#fee2e2; }
    .card { background: var(--card); border:1px solid var(--line); border-radius: 12px; padding: 16px; margin: 16px 0; overflow-x:auto; }
    .title { margin:0 0 12px; }
    table { width:100%; border-collapse: collapse; min-width: 950px; }
    th, td { border-bottom:1px solid var(--line); padding: 8px; font-size: 13px; vertical-align: top; }
    th { text-align:left; color:#d1d5db; }
    input, select { width:100%; background:#111827; color:var(--text); border:1px solid #4b5563; border-radius: 6px; padding:6px; font-size:12px; }
    .actions { display:flex; gap:8px; }
    button { border:0; border-radius:6px; padding:6px 10px; cursor:pointer; font-weight:600; font-size:12px; }
    .save { background:var(--accent); color:white; }
    .del { background:var(--bad); color:white; }
    .new-row { background:#0f172a; }
    .switch { width:20px; height:20px; }
    .tiny { font-size:11px; color:var(--muted); }
    a { color:#fdba74; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Foodie Admin</h1>
    <p>
      Manage categories, restaurants, and menu items.
      <a href="./index.php">Open customer site</a> ·
      Logged in as <?= h($authUser['name'] ?? '') ?> (<?= h($authUser['role'] ?? '') ?>) ·
      <a href="./logout.php">Logout</a>
    </p>

    <?php if ($success !== ''): ?>
      <div class="msg ok"><?= h($success) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
      <div class="msg bad"><?= h($err) ?></div>
    <?php endforeach; ?>

    <?php if (empty($errors)): ?>
      <div class="card">
        <h2 class="title">Orders</h2>
        <p class="tiny">Latest customer orders from checkout.</p>
        <?php if ($orders === []): ?>
          <p class="tiny" style="margin-top:12px">No orders yet.</p>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th><th>Customer</th><th>Restaurant</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th><th>Update</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>#<?= (int) $o['id'] ?></td>
              <td>
                <strong><?= h($o['full_name']) ?></strong><br>
                <span class="tiny"><?= h($o['contact_number']) ?></span>
              </td>
              <td><?= h($o['restaurant_name']) ?></td>
              <td><?= h(formatPeso((int) $o['total_amount'])) ?></td>
              <td><?= h($o['payment_method']) ?></td>
              <td><?= h($o['status']) ?></td>
              <td class="tiny"><?= h($o['created_at']) ?></td>
              <td>
                <form method="post" style="display:flex;gap:6px;align-items:center">
                  <input type="hidden" name="action" value="update_order_status">
                  <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                  <select name="status" style="min-width:140px">
                    <?php foreach (['pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'] as $st): ?>
                      <option value="<?= h($st) ?>" <?= $o['status'] === $st ? 'selected' : '' ?>><?= h($st) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="save" type="submit">Save</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2 class="title">Categories</h2>
        <table>
          <thead>
            <tr><th>ID</th><th>Name</th><th>Icon</th><th>Filter Key</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($categories as $c): ?>
            <tr>
              <form method="post">
                <td>
                  <?= (int) $c['id'] ?>
                  <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                  <input type="hidden" name="action" value="update_category">
                </td>
                <td><input name="name" value="<?= h($c['name']) ?>" required></td>
                <td><input name="icon" value="<?= h($c['icon']) ?>" required></td>
                <td><input name="filter_key" value="<?= h($c['filter_key']) ?>" required></td>
                <td class="actions">
                  <button class="save" type="submit">Save</button>
              </form>
                  <form method="post" onsubmit="return confirm('Delete this category?');">
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <button class="del" type="submit">Delete</button>
                  </form>
                </td>
            </tr>
          <?php endforeach; ?>
          <tr class="new-row">
            <form method="post">
              <input type="hidden" name="action" value="create_category">
              <td>auto</td>
              <td><input name="name" placeholder="Category name" required></td>
              <td><input name="icon" placeholder="e.g. 🍔" required></td>
              <td><input name="filter_key" placeholder="e.g. fast-food" required></td>
              <td><button class="save" type="submit">Add</button></td>
            </form>
          </tr>
          </tbody>
        </table>
      </div>

      <div class="card">
        <h2 class="title">Restaurants</h2>
        <p class="tiny">Cuisines format: comma-separated values (e.g. Filipino, Grill)</p>
        <table>
          <thead>
            <tr><th>ID</th><th>Name</th><th>Image URL</th><th>Rating</th><th>Time</th><th>Fee</th><th>Cuisines</th><th>Tag</th><th>Tag Style</th><th>Category</th><th>Open</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($restaurants as $r): ?>
            <tr>
              <form method="post">
                <td>
                  <?= (int) $r['id'] ?>
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="action" value="update_restaurant">
                </td>
                <td><input name="name" value="<?= h($r['name']) ?>" required></td>
                <td><input name="image" value="<?= h($r['image']) ?>" required></td>
                <td><input type="number" step="0.1" min="0" max="9.9" name="rating" value="<?= h((string) $r['rating']) ?>" required></td>
                <td><input name="delivery_time" value="<?= h($r['delivery_time']) ?>" required></td>
                <td><input type="number" min="0" name="delivery_fee" value="<?= (int) $r['delivery_fee'] ?>" required></td>
                <td><input name="cuisines" value="<?= h(implode(', ', json_decode($r['cuisines_json'], true, 512, JSON_THROW_ON_ERROR))) ?>" required></td>
                <td><input name="tag" value="<?= h($r['tag']) ?>"></td>
                <td><input name="tag_style" value="<?= h($r['tag_style']) ?>"></td>
                <td><input name="category" value="<?= h($r['category']) ?>" required></td>
                <td><input class="switch" type="checkbox" name="is_open" <?= (int) $r['is_open'] === 1 ? 'checked' : '' ?>></td>
                <td class="actions">
                  <button class="save" type="submit">Save</button>
              </form>
                  <form method="post" onsubmit="return confirm('Delete this restaurant and its menu items?');">
                    <input type="hidden" name="action" value="delete_restaurant">
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <button class="del" type="submit">Delete</button>
                  </form>
                </td>
            </tr>
          <?php endforeach; ?>
          <tr class="new-row">
            <form method="post">
              <input type="hidden" name="action" value="create_restaurant">
              <td>auto</td>
              <td><input name="name" placeholder="Restaurant name" required></td>
              <td><input name="image" placeholder="https://..." required></td>
              <td><input type="number" step="0.1" min="0" max="9.9" name="rating" value="4.5" required></td>
              <td><input name="delivery_time" value="45-60" required></td>
              <td><input type="number" min="0" name="delivery_fee" value="50" required></td>
              <td><input name="cuisines" placeholder="Filipino, Grill" required></td>
              <td><input name="tag" value="Popular"></td>
              <td><input name="tag_style" value=""></td>
              <td><input name="category" placeholder="fast-food" required></td>
              <td><input class="switch" type="checkbox" name="is_open" checked></td>
              <td><button class="save" type="submit">Add</button></td>
            </form>
          </tr>
          </tbody>
        </table>
      </div>

      <div class="card">
        <h2 class="title">Menu Items</h2>
        <table>
          <thead>
            <tr><th>ID</th><th>Restaurant</th><th>Name</th><th>Description</th><th>Price</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($menuItems as $m): ?>
            <tr>
              <form method="post">
                <td>
                  <?= (int) $m['id'] ?>
                  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                  <input type="hidden" name="action" value="update_menu_item">
                </td>
                <td>
                  <select name="restaurant_id">
                    <?php foreach ($restaurants as $r): ?>
                      <option value="<?= (int) $r['id'] ?>" <?= (int) $m['restaurant_id'] === (int) $r['id'] ? 'selected' : '' ?>>
                        <?= (int) $r['id'] ?> - <?= h($r['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td><input name="name" value="<?= h($m['name']) ?>" required></td>
                <td><input name="description" value="<?= h($m['description']) ?>" required></td>
                <td><input type="number" min="0" name="price" value="<?= (int) $m['price'] ?>" required></td>
                <td class="actions">
                  <button class="save" type="submit">Save</button>
              </form>
                  <form method="post" onsubmit="return confirm('Delete this menu item?');">
                    <input type="hidden" name="action" value="delete_menu_item">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button class="del" type="submit">Delete</button>
                  </form>
                </td>
            </tr>
          <?php endforeach; ?>
          <tr class="new-row">
            <form method="post">
              <input type="hidden" name="action" value="create_menu_item">
              <td>auto</td>
              <td>
                <select name="restaurant_id">
                  <?php foreach ($restaurants as $r): ?>
                    <option value="<?= (int) $r['id'] ?>"><?= (int) $r['id'] ?> - <?= h($r['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input name="name" placeholder="Item name" required></td>
              <td><input name="description" placeholder="Description" required></td>
              <td><input type="number" min="0" name="price" value="100" required></td>
              <td><button class="save" type="submit">Add</button></td>
            </form>
          </tr>
          </tbody>
        </table>
      </div>
      </div>

      <div class="card">
        <h2 class="title">Registered Riders</h2>
        <table>
          <thead>
            <tr><th>User ID</th><th>Name</th><th>Email / Phone</th><th>Vehicle</th><th>Plate</th><th>License / Docs</th><th>City</th><th>Status</th><th>Applied On</th></tr>
          </thead>
          <tbody>
                    <?php foreach ($ridersList as $rider): ?>
            <tr>
              <td><?= (int) $rider['user_id'] ?></td>
              <td><?= h($rider['name']) ?></td>
              <td>
                <?= h($rider['email']) ?><br>
                <span class="tiny"><?= h($rider['phone']) ?></span>
              </td>
              <td><?= h($rider['vehicle_type']) ?></td>
              <td><?= h($rider['vehicle_plate']) ?></td>
              <td>
                <?= h($rider['driver_license'] ? 'License: ' . $rider['driver_license'] : 'No License') ?><br>
                <?php if (!empty($rider['license_front_url'])): ?>
                   <a href="<?= h($rider['license_front_url']) ?>" target="_blank" style="color:var(--accent)" class="tiny"><i class="fas fa-image"></i> Front</a>
                <?php endif; ?>
                <?php if (!empty($rider['license_back_url'])): ?>
                   <span class="tiny" style="color:var(--muted)">|</span> <a href="<?= h($rider['license_back_url']) ?>" target="_blank" style="color:var(--accent)" class="tiny"><i class="fas fa-image"></i> Back</a>
                <?php endif; ?>
              </td>
              <td><?= h($rider['preferred_city']) ?></td>
              <td>
                <?php
                   $rColor = '#9ca3af';
                   if ($rider['status'] === 'approved') $rColor = '#16a34a';
                   else if ($rider['status'] === 'rejected') $rColor = '#dc2626';
                ?>
                <strong style="color:<?= $rColor ?>"><?= ucfirst(h($rider['status'])) ?></strong><br>
                <?php if ($rider['status'] === 'pending'): ?>
                  <div style="display:flex;gap:4px;margin-top:4px;">
                    <form method="post" style="display:inline">
                      <input type="hidden" name="action" value="update_rider_status">
                      <input type="hidden" name="rider_id" value="<?= (int) $rider['rider_id'] ?>">
                      <input type="hidden" name="status" value="approved">
                      <button class="save" type="submit" style="padding:4px 6px;font-size:10px;">Approve</button>
                    </form>
                    <form method="post" style="display:inline">
                      <input type="hidden" name="action" value="update_rider_status">
                      <input type="hidden" name="rider_id" value="<?= (int) $rider['rider_id'] ?>">
                      <input type="hidden" name="status" value="rejected">
                      <button class="del" type="submit" style="padding:4px 6px;font-size:10px;">Reject</button>
                    </form>
                  </div>
                <?php endif; ?>
              </td>
              <td><?= h($rider['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($ridersList)): ?>
            <tr><td colspan="9">No riders registered yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- PARTNER APPLICATIONS SECTION -->
      <div class="card">
        <h2 class="title">Partner Applications</h2>
        <p class="tiny">Restaurant registration requests submitted via the Partner With Us page.</p>
        <div style="overflow-x:auto;">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Restaurant Name</th>
                <th>Owner</th>
                <th>Contact</th>
                <th>City</th>
                <th>Status</th>
                <th>Legitimacy Doc</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($applications as $app): ?>
                <tr>
                  <td><?= (int) $app['id'] ?></td>
                  <td>
                    <strong><?= h($app['restaurant_name']) ?></strong><br>
                    <span class="tiny"><?= h($app['cuisine_type']) ?></span>
                  </td>
                  <td><?= h($app['owner_name']) ?></td>
                  <td>
                    <?= h($app['owner_email']) ?><br>
                    <span class="tiny"><?= h($app['owner_phone']) ?></span>
                  </td>
                  <td><?= h($app['city']) ?></td>
                  <td>
                    <?php
                       $sColor = '#9ca3af';
                       if ($app['status'] === 'approved') $sColor = '#16a34a';
                       else if ($app['status'] === 'rejected') $sColor = '#dc2626';
                       else if ($app['status'] === 'under_review') $sColor = '#f59e0b';
                    ?>
                    <strong style="color:<?= $sColor ?>"><?= ucfirst(h($app['status'])) ?></strong>
                    <?php if (in_array($app['status'], ['pending', 'under_review'], true)): ?>
                      <div style="display:flex;gap:4px;margin-top:4px;">
                        <form method="post" style="display:inline">
                          <input type="hidden" name="action" value="update_application_status">
                          <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
                          <input type="hidden" name="status" value="approved">
                          <button class="save" type="submit" style="padding:4px 6px;font-size:10px;">Approve</button>
                        </form>
                        <form method="post" style="display:inline">
                          <input type="hidden" name="action" value="update_application_status">
                          <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
                          <input type="hidden" name="status" value="rejected">
                          <button class="del" type="submit" style="padding:4px 6px;font-size:10px;">Reject</button>
                        </form>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($app['legitimacy_doc_url']): ?>
                      <a href="<?= h($app['legitimacy_doc_url']) ?>" target="_blank" style="color:var(--accent)"><i class="fas fa-file-invoice"></i> View Doc</a>
                    <?php else: ?>
                      <span class="tiny" style="color:var(--muted)">No Document</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="tiny"><?= h($app['created_at']) ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($applications)): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:16px;">No applications found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      
    <?php endif; ?>
  </div>
</body>
</html>
