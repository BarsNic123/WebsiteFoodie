<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$riderInfo = requireRider();
$authUser = $riderInfo['user'];
$riderId = $riderInfo['rider_id'];
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_type = trim((string)($_POST['vehicle_type'] ?? ''));
    $vehicle_plate = trim((string)($_POST['vehicle_plate'] ?? ''));
    $preferred_city = trim((string)($_POST['preferred_city'] ?? ''));

    if ($vehicle_type === '' || $vehicle_plate === '' || $preferred_city === '') {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE riders SET vehicle_type=?, vehicle_plate=?, preferred_city=? WHERE id=?');
            $stmt->execute([$vehicle_type, $vehicle_plate, $preferred_city, $riderId]);
            $success = 'Rider profile updated successfully!';
        } catch (Throwable $e) {
            $error = 'Failed to update rider profile. Please try again.';
        }
    }
}

// Fetch current rider data
$stmt = $pdo->prepare('SELECT * FROM riders WHERE id = ?');
$stmt->execute([$riderId]);
$riderData = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Profile - Foodie.PH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="foodieph.css">
    <style>
        .profile-container { max-width: 600px; margin: 60px auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .profile-title { font-size: 24px; font-weight: bold; margin-bottom: 20px; color: var(--brand); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 6px; font-size: 14px; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        .btn-save { background: var(--brand); color: #fff; border: none; padding: 12px 20px; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-save:hover { background: var(--brand-dark); }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; }
        .section-header { margin: 30px 0 15px; padding-bottom: 8px; border-bottom: 1px solid #eee; font-weight: bold; color: #555; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-approved { background: #dcfce7; color: #16a34a; }
    </style>
</head>
<body style="background: #f7f5f2;">
    <nav class="navbar" style="background:#fff;border-bottom:1px solid #eee;padding:15px 40px;">
        <a href="rider-dashboard.php" class="logo" style="text-decoration:none;font-size:24px;color:var(--brand);font-weight:bold;">Foodie.PH Rider</a>
        <div style="float:right;margin-top:5px;">
            <a href="rider-dashboard.php" style="margin-right:20px;text-decoration:none;color:#333;">Dashboard</a>
            <a href="profile.php" style="margin-right:20px;text-decoration:none;color:#333;">My Account</a>
            <a href="logout.php" style="text-decoration:none;color:var(--brand);">Logout</a>
        </div>
    </nav>

    <div class="profile-container">
        <h1 class="profile-title">Rider Profile</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div style="margin-bottom: 20px;">
            <strong>Status:</strong> <span class="status-badge status-approved"><?= htmlspecialchars($riderData['status'] ?? 'approved') ?></span>
        </div>

        <form method="POST">
            <div class="section-header">Vehicle Information</div>
            <div class="form-group">
                <label>Vehicle Type</label>
                <select name="vehicle_type" required>
                    <?php 
                    $vtype = $riderData['vehicle_type'] ?? '';
                    $opts = ['Motorcycle', 'Bicycle', 'Car', 'Van'];
                    foreach ($opts as $opt) {
                        $sel = ($vtype === $opt) ? 'selected' : '';
                        echo "<option value=\"$opt\" $sel>$opt</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Vehicle Plate Number</label>
                <input type="text" name="vehicle_plate" value="<?= htmlspecialchars($riderData['vehicle_plate'] ?? '') ?>" required>
            </div>
            
            <div class="section-header">Delivery Area</div>
            <div class="form-group">
                <label>Preferred City</label>
                <input type="text" name="preferred_city" value="<?= htmlspecialchars($riderData['preferred_city'] ?? '') ?>" required>
            </div>

            <button type="submit" class="btn-save">Update Rider Profile</button>
        </form>
    </div>
</body>
</html>
