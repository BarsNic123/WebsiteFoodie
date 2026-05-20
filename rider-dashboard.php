<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$riderInfo = requireRider();
$authUser = $riderInfo['user'];
$riderId = $riderInfo['rider_id'];

$root = __DIR__;
$configFile = $root . '/config.php';
$config = is_readable($configFile) ? require $configFile : [];
if (empty($config['use_database'])) {
    die("Database must be enabled.");
}
require_once $root . '/includes/db.php';
$pdo = db();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'accept_order') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            
            // Check if it's still available
            $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND status IN ('pending', 'preparing') AND rider_id IS NULL FOR UPDATE");
            $stmt->execute([$orderId]);
            $orderRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orderRow) {
                throw new Exception("Order is no longer available.");
            }
            
            $oldStatus = $orderRow['status'];
            
            // Update order
            $upd = $pdo->prepare("UPDATE orders SET rider_id = ?, status = 'out_for_delivery' WHERE id = ?");
            $upd->execute([$riderId, $orderId]);
            
            // Add history
            $hist = $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?, ?, 'out_for_delivery', ?, 'Accepted by rider')");
            $hist->execute([$orderId, $oldStatus, $authUser['id']]);
            
            $success = "You have accepted order #$orderId!";
        } elseif ($action === 'complete_order') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND rider_id = ? AND status = 'out_for_delivery'");
            $stmt->execute([$orderId, $riderId]);
            if (!$stmt->fetch()) {
                throw new Exception("Cannot complete this order.");
            }
            
            // Update order
            $upd = $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?");
            $upd->execute([$orderId]);
            
            // Add history
            $hist = $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?, 'out_for_delivery', 'delivered', ?, 'Delivered by rider')");
            $hist->execute([$orderId, $authUser['id']]);
            
            $success = "Order #$orderId marked as delivered!";
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// Fetch available orders (both pending and preparing)
$availableOrders = $pdo->query("SELECT o.*, r.name as restaurant_name FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE o.status IN ('pending', 'preparing') AND o.rider_id IS NULL ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch my active deliveries
$myDeliveriesStmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name FROM orders o JOIN restaurants r ON o.restaurant_id = r.id WHERE o.status = 'out_for_delivery' AND o.rider_id = ? ORDER BY o.created_at ASC");
$myDeliveriesStmt->execute([$riderId]);
$myDeliveries = $myDeliveriesStmt->fetchAll(PDO::FETCH_ASSOC);

function formatPeso(int $amount): string {
    return '₱' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard - Foodie.PH</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --bg:#111827; --card:#1f2937; --text:#f3f4f6; --muted:#9ca3af; --ok:#16a34a; --bad:#dc2626; --line:#374151; --accent:#f97316; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background:var(--bg); color:var(--text); display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .topbar { padding: 16px 24px; background: var(--card); border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; z-index: 10; }
        .topbar a { color: var(--accent); text-decoration: none; font-size: 14px; }
        .topbar a:hover { text-decoration: underline; }
        
        .main-container { display: flex; flex: 1; overflow: hidden; }
        
        .sidebar { width: 400px; background: var(--card); border-right: 1px solid var(--line); overflow-y: auto; display: flex; flex-direction: column; }
        .map-area { flex: 1; background: #e5e7eb; position: relative; }
        #map { width: 100%; height: 100%; z-index: 1; }
        
        .msg { padding:10px 12px; border-radius:8px; margin: 16px; font-size: 14px; }
        .ok { background:#14532d; color:#dcfce7; }
        .bad { background:#7f1d1d; color:#fee2e2; }
        
        .section-title { padding: 16px; font-size: 18px; font-weight: bold; border-bottom: 1px solid var(--line); background: #111827; position: sticky; top: 0; z-index: 2; }
        
        .order-card { padding: 16px; border-bottom: 1px solid var(--line); transition: background 0.2s; cursor: pointer; }
        .order-card:hover { background: rgba(255,255,255,0.05); }
        .order-card.active { background: rgba(249, 115, 22, 0.1); border-left: 4px solid var(--accent); }
        
        .o-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .o-id { font-weight: bold; color: var(--accent); }
        .o-fee { font-weight: bold; color: var(--ok); }
        
        .o-detail { display: flex; gap: 8px; margin-bottom: 6px; font-size: 13px; color: var(--muted); align-items: flex-start; }
        .o-detail i { margin-top: 3px; width: 16px; text-align: center; }
        
        .btn { display: block; width: 100%; padding: 10px; border: 0; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 12px; text-align: center; font-size: 14px; }
        .btn-accept { background: var(--accent); color: white; }
        .btn-complete { background: var(--ok); color: white; }
        
        .empty-state { padding: 32px 16px; text-align: center; color: var(--muted); font-size: 14px; }
        
        .map-overlay { position: absolute; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1000; background: rgba(0,0,0,0.8); padding: 10px 20px; border-radius: 20px; color: white; font-size: 14px; pointer-events: none; display: none; }
    </style>
</head>
<body>
    <div class="topbar">
        <div>
            <h2><i class="fas fa-motorcycle" style="color:var(--accent)"></i> Rider Dashboard</h2>
        </div>
        <div>
            Logged in as <?= htmlspecialchars($authUser['name']) ?> &nbsp;|&nbsp;
            <a href="index.php">Customer View</a> &nbsp;|&nbsp;
            <a href="rider-profile.php">My Profile</a> &nbsp;|&nbsp;
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="main-container">
        <div class="sidebar">
            <?php if ($success): ?><div class="msg ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg bad"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <div class="section-title"><i class="fas fa-box-open"></i> My Active Deliveries</div>
            <?php if (empty($myDeliveries)): ?>
                <div class="empty-state">No active deliveries right now.</div>
            <?php else: ?>
                <?php foreach ($myDeliveries as $order): ?>
                    <div class="order-card active" onclick="showOnMap(<?= htmlspecialchars(json_encode([
                        'id' => $order['id'],
                        'restaurant' => $order['restaurant_name'],
                        'customer' => $order['full_name'],
                        'address' => $order['delivery_address']
                    ])) ?>)">
                        <div class="o-header">
                            <span class="o-id">Order #<?= $order['id'] ?></span>
                            <span class="o-fee">Earn <?= formatPeso((int)$order['delivery_fee']) ?></span>
                        </div>
                        <div class="o-detail"><i class="fas fa-store"></i> <span>Pickup: <strong><?= htmlspecialchars($order['restaurant_name']) ?></strong></span></div>
                        <div class="o-detail"><i class="fas fa-user"></i> <span>Customer: <?= htmlspecialchars($order['full_name']) ?> (<?= htmlspecialchars($order['contact_number']) ?>)</span></div>
                        <div class="o-detail"><i class="fas fa-location-dot"></i> <span>Dropoff: <?= htmlspecialchars($order['delivery_address']) ?></span></div>
                        <form method="POST">
                            <input type="hidden" name="action" value="complete_order">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button type="submit" class="btn btn-complete" onclick="event.stopPropagation();"><i class="fas fa-check-circle"></i> Mark as Delivered</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="section-title" style="margin-top: 10px;"><i class="fas fa-satellite-dish"></i> Available Requests</div>
            <?php if (empty($availableOrders)): ?>
                <div class="empty-state">No new requests in your area.</div>
            <?php else: ?>
                <?php foreach ($availableOrders as $order): ?>
                    <div class="order-card" onclick="showOnMap(<?= htmlspecialchars(json_encode([
                        'id' => $order['id'],
                        'restaurant' => $order['restaurant_name'],
                        'customer' => $order['full_name'],
                        'address' => $order['delivery_address']
                    ])) ?>)">
                        <div class="o-header">
                            <span class="o-id">Order #<?= $order['id'] ?></span>
                            <span class="o-fee"><?= formatPeso((int)$order['delivery_fee']) ?></span>
                        </div>
                        <div class="o-detail"><i class="fas fa-store"></i> <span>From: <?= htmlspecialchars($order['restaurant_name']) ?></span></div>
                        <div class="o-detail"><i class="fas fa-location-dot"></i> <span>To: <?= htmlspecialchars($order['delivery_address']) ?></span></div>
                        <form method="POST">
                            <input type="hidden" name="action" value="accept_order">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button type="submit" class="btn btn-accept" onclick="event.stopPropagation();"><i class="fas fa-truck-fast"></i> Accept Request</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="map-area">
            <div id="map-overlay" class="map-overlay">Select an order to see details</div>
            <div id="map"></div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        // Initialize Map
        const map = L.map('map').setView([14.5995, 120.9842], 12); // Default to Manila
        
        // Standard OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        let currentMarkers = [];

        async function geocode(address) {
            // Simple Nominatim fetch. Be careful with rate limits (1 req/sec).
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`);
                const data = await res.json();
                if (data && data.length > 0) {
                    return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                }
            } catch (e) {
                console.error("Geocoding failed", e);
            }
            return null;
        }

        async function showOnMap(orderData) {
            const overlay = document.getElementById('map-overlay');
            overlay.style.display = 'block';
            overlay.innerHTML = `Locating route for Order #${orderData.id}...`;
            
            // Clear old markers
            currentMarkers.forEach(m => map.removeLayer(m));
            currentMarkers = [];

            // Attempt to geocode restaurant (just using name as fallback, might not be accurate)
            let restCoords = await geocode(orderData.restaurant + " Philippines");
            // Attempt to geocode delivery address
            let destCoords = await geocode(orderData.address + " Philippines");

            // Fallbacks if geocoding fails
            if (!restCoords) restCoords = [14.5547, 121.0244]; // Makati center
            if (!destCoords) destCoords = [14.6091, 121.0223]; // Mandaluyong center

            // Custom Icon
            const restIcon = L.divIcon({ html: '<div style="font-size: 24px; color: #f5a623;"><i class="fas fa-store"></i></div>', className: 'custom-div-icon', iconSize: [24, 24], iconAnchor: [12, 24] });
            const destIcon = L.divIcon({ html: '<div style="font-size: 24px; color: #16a34a;"><i class="fas fa-map-marker-alt"></i></div>', className: 'custom-div-icon', iconSize: [24, 24], iconAnchor: [12, 24] });

            // Add Markers
            const restMarker = L.marker(restCoords, {icon: restIcon}).addTo(map).bindPopup("<b>Pickup:</b> " + orderData.restaurant);
            const destMarker = L.marker(destCoords, {icon: destIcon}).addTo(map).bindPopup("<b>Dropoff:</b> " + orderData.address);
            
            currentMarkers.push(restMarker, destMarker);

            // Fit map to bounds of markers
            const group = new L.featureGroup(currentMarkers);
            map.fitBounds(group.getBounds(), { padding: [50, 50], maxZoom: 15 });
            
            overlay.innerHTML = `Route for Order #${orderData.id} displayed.`;
            setTimeout(() => { overlay.style.display = 'none'; }, 3000);
        }

        // If there's an active delivery, show it immediately
        <?php if (!empty($myDeliveries)): ?>
        setTimeout(() => {
            showOnMap(<?= json_encode([
                'id' => $myDeliveries[0]['id'],
                'restaurant' => $myDeliveries[0]['restaurant_name'],
                'customer' => $myDeliveries[0]['full_name'],
                'address' => $myDeliveries[0]['delivery_address']
            ]) ?>);
        }, 500);
        <?php endif; ?>
    </script>
</body>
</html>
