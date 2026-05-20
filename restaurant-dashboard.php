<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();
$authUser = currentUser();
if (!$authUser || $authUser['role'] !== 'restaurant') {
    http_response_code(403);
    die('Forbidden: Restaurant access required.');
}

require_once __DIR__ . '/includes/db.php';
$pdo = db();

$success = '';
$error = '';

// Get Restaurant Info
$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE owner_user_id = ? LIMIT 1');
$stmt->execute([$authUser['id']]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$restaurant) {
    die("Restaurant profile not found for this user. Please contact support.");
}

$restaurantId = (int)$restaurant['id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add_dish') {
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $price = (int)($_POST['price'] ?? 0);
            $is_avail = isset($_POST['is_available']) ? 1 : 0;
            
            if ($name === '' || $price < 0) {
                throw new Exception("Name and valid price are required.");
            }
            
            $imageUrl = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/menu/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = uniqid('menu_') . '_' . basename($_FILES['image']['name']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imageUrl = 'uploads/menu/' . $filename;
                }
            }
            
            $ins = $pdo->prepare("INSERT INTO menu_items (restaurant_id, name, description, image_url, price, is_available) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$restaurantId, $name, $desc, $imageUrl, $price, $is_avail]);
            $success = "Dish added successfully!";
            
        } elseif ($action === 'delete_dish') {
            $dishId = (int)($_POST['dish_id'] ?? 0);
            $del = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?");
            $del->execute([$dishId, $restaurantId]);
            $success = "Dish deleted.";
            
        } elseif ($action === 'update_settings') {
            $name = trim($_POST['name'] ?? '');
            $address = trim($_POST['business_address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $hours = trim($_POST['operating_hours'] ?? '');
            $fee = (int)($_POST['delivery_fee'] ?? 0);
            $isOpen = isset($_POST['is_open']) ? 1 : 0;
            
            $imageUrl = $restaurant['image'];
            if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/restaurants/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = uniqid('rest_') . '_' . basename($_FILES['cover']['name']);
                if (move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir . $filename)) {
                    $imageUrl = 'uploads/restaurants/' . $filename;
                }
            }
            
            $upd = $pdo->prepare("UPDATE restaurants SET name=?, business_address=?, city=?, operating_hours=?, delivery_fee=?, is_open=?, image=? WHERE id=?");
            $upd->execute([$name, $address, $city, $hours, $fee, $isOpen, $imageUrl, $restaurantId]);
            
            // Reload restaurant
            $stmt->execute([$authUser['id']]);
            $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
            $success = "Store settings updated.";
            
        } elseif ($action === 'update_order') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');
            
            $upd = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND restaurant_id = ?");
            $upd->execute([$newStatus, $orderId, $restaurantId]);
            
            // Add history
            $hist = $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?, '', ?, ?, 'Updated by restaurant')");
            $hist->execute([$orderId, $newStatus, $authUser['id']]);
            
            $success = "Order #{$orderId} status updated to {$newStatus}.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch data for view
$menuItems = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY id DESC");
$menuItems->execute([$restaurantId]);
$menuItems = $menuItems->fetchAll(PDO::FETCH_ASSOC);

$orders = $pdo->prepare("SELECT * FROM orders WHERE restaurant_id = ? ORDER BY id DESC LIMIT 50");
$orders->execute([$restaurantId]);
$orders = $orders->fetchAll(PDO::FETCH_ASSOC);

function formatPeso(int $amount): string {
    return '₱' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard - Foodie.PH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --brand:#e8282b; --bg:#f7f5f2; --card:#fff; --text:#1a1a1a; --border:#e8e6e2; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', Arial, sans-serif; background: var(--bg); color: var(--text); display: flex; height: 100vh; overflow: hidden; }
        
        .sidebar { width: 250px; background: #111; color: #fff; padding: 20px; display: flex; flex-direction: column; }
        .sidebar h2 { color: var(--brand); font-size: 20px; margin-bottom: 30px; font-style: italic; }
        .nav-btn { background: none; border: none; color: #aaa; text-align: left; padding: 12px; font-size: 15px; cursor: pointer; border-radius: 8px; margin-bottom: 8px; font-weight: bold; }
        .nav-btn:hover { background: #222; color: #fff; }
        .nav-btn.active { background: var(--brand); color: #fff; }
        .sidebar-footer { margin-top: auto; border-top: 1px solid #333; padding-top: 20px; }
        
        .main-content { flex: 1; overflow-y: auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 24px; }
        
        .msg { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .ok { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .bad { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 6px; color: #555; text-transform: uppercase; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f9f9f9; font-weight: bold; font-size: 13px; color: #666; text-transform: uppercase; }
        
        .btn { padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; background: #eee; color: #333; }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        
        .status-badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        
        .dish-image { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; background: #eee; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Foodie.PH <span>Partner</span></h2>
        <button class="nav-btn active" onclick="switchTab('orders')"><i class="fas fa-receipt"></i> Orders</button>
        <button class="nav-btn" onclick="switchTab('menu')"><i class="fas fa-utensils"></i> Menu Management</button>
        <button class="nav-btn" onclick="switchTab('settings')"><i class="fas fa-store"></i> Store Settings</button>
        
        <div class="sidebar-footer">
            <div style="font-size: 12px; color: #888; margin-bottom: 10px;">Logged in as: <?= htmlspecialchars($authUser['name']) ?></div>
            <a href="logout.php" style="color: #ff6b6b; text-decoration: none; font-size: 14px; font-weight: bold;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1><?= htmlspecialchars($restaurant['name']) ?> Dashboard</h1>
            <div>
                <?php if ($restaurant['is_open']): ?>
                    <span class="status-badge" style="background:#dcfce7;color:#166534;"><i class="fas fa-door-open"></i> Store is Open</span>
                <?php else: ?>
                    <span class="status-badge" style="background:#fee2e2;color:#991b1b;"><i class="fas fa-door-closed"></i> Store is Closed</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($success): ?><div class="msg ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg bad"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        
        <!-- ORDERS TAB -->
        <div id="tab-orders" class="tab-content active">
            <div class="card">
                <h2 style="margin-bottom:15px;"><i class="fas fa-list"></i> Recent Orders</h2>
                <?php if (empty($orders)): ?>
                    <p style="color:#666;">No orders yet.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong>#<?= $o['id'] ?></strong></td>
                            <td><?= htmlspecialchars($o['full_name']) ?></td>
                            <td>
                                <span class="status-badge" style="background:#f1f5f9;color:#475569;"><?= strtoupper($o['status']) ?></span>
                            </td>
                            <td><?= formatPeso((int)$o['total_amount']) ?></td>
                            <td><?= $o['created_at'] ?></td>
                            <td>
                                <form method="POST" style="display:flex;gap:5px;">
                                    <input type="hidden" name="action" value="update_order">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" style="padding:4px;border-radius:4px;border:1px solid #ccc;">
                                        <option value="pending" <?= $o['status']==='pending'?'selected':'' ?>>Pending</option>
                                        <option value="preparing" <?= $o['status']==='preparing'?'selected':'' ?>>Preparing</option>
                                        <option value="ready" <?= $o['status']==='ready'?'selected':'' ?>>Ready for Pickup</option>
                                        <option value="cancelled" <?= $o['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary" style="padding:4px 8px;font-size:12px;">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- MENU TAB -->
        <div id="tab-menu" class="tab-content">
            <div class="card" style="background: #fafafa;">
                <h2 style="margin-bottom:15px;"><i class="fas fa-plus-circle"></i> Add New Dish</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_dish">
                    <div class="form-group">
                        <label>Dish Name</label>
                        <input type="text" name="name" required placeholder="e.g. Spicy Chicken Wings">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" placeholder="Describe the dish..."></textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                        <div class="form-group">
                            <label>Price (PHP)</label>
                            <input type="number" name="price" required placeholder="e.g. 199" min="0">
                        </div>
                        <div class="form-group">
                            <label>Dish Photo</label>
                            <input type="file" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;gap:10px;">
                        <input type="checkbox" name="is_available" id="is_avail" checked style="width:auto;">
                        <label for="is_avail" style="margin:0;font-size:14px;text-transform:none;">Item is currently available</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Add to Menu</button>
                </form>
            </div>
            
            <div class="card">
                <h2 style="margin-bottom:15px;"><i class="fas fa-book-open"></i> Current Menu</h2>
                <?php if (empty($menuItems)): ?>
                    <p style="color:#666;">Your menu is empty.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Photo</th>
                            <th>Dish Name</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach ($menuItems as $m): ?>
                        <tr>
                            <td>
                                <?php if ($m['image_url']): ?>
                                    <img src="<?= htmlspecialchars($m['image_url']) ?>" class="dish-image">
                                <?php else: ?>
                                    <div class="dish-image"></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($m['name']) ?></strong><br>
                                <small style="color:#666;"><?= htmlspecialchars($m['description']) ?></small>
                            </td>
                            <td><?= formatPeso((int)$m['price']) ?></td>
                            <td>
                                <?= $m['is_available'] ? '<span style="color:#16a34a;font-weight:bold;">Available</span>' : '<span style="color:#dc2626;font-weight:bold;">Sold Out</span>' ?>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this dish?');">
                                    <input type="hidden" name="action" value="delete_dish">
                                    <input type="hidden" name="dish_id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:6px 10px;font-size:12px;"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- SETTINGS TAB -->
        <div id="tab-settings" class="tab-content">
            <div class="card">
                <h2 style="margin-bottom:15px;"><i class="fas fa-cogs"></i> Store Settings</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="form-group" style="background:#f9f9f9;padding:15px;border-radius:8px;border:1px solid #ddd;display:flex;align-items:center;gap:15px;margin-bottom:20px;">
                        <input type="checkbox" name="is_open" id="store_open" <?= $restaurant['is_open'] ? 'checked' : '' ?> style="width:20px;height:20px;">
                        <label for="store_open" style="margin:0;font-size:16px;text-transform:none;color:#111;">Store is Open and accepting orders</label>
                    </div>

                    <div class="form-group">
                        <label>Restaurant Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($restaurant['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Store Cover Image</label>
                        <?php if ($restaurant['image']): ?>
                            <div style="margin-bottom:10px;"><img src="<?= htmlspecialchars($restaurant['image']) ?>" style="height:100px;border-radius:8px;"></div>
                        <?php endif; ?>
                        <input type="file" name="cover" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>Business Address</label>
                        <input type="text" name="business_address" value="<?= htmlspecialchars($restaurant['business_address']) ?>" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?= htmlspecialchars($restaurant['city']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Operating Hours</label>
                            <input type="text" name="operating_hours" value="<?= htmlspecialchars($restaurant['operating_hours']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Delivery Fee (PHP)</label>
                            <input type="number" name="delivery_fee" value="<?= (int)$restaurant['delivery_fee'] ?>" required min="0">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:10px;">Save Settings</button>
                </form>
            </div>
        </div>
        
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById('tab-' + tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>
