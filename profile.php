<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$authUser = requireLogin();
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim((string)($_POST['first_name'] ?? ''));
    $last = trim((string)($_POST['last_name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $street = trim((string)($_POST['street_address'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    
    $password = trim((string)($_POST['password'] ?? ''));
    $confirm = trim((string)($_POST['confirm_password'] ?? ''));

    if ($first === '' || $last === '' || $phone === '' || $street === '' || $city === '') {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($password !== '' && $password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $name = $first . ' ' . $last;
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET name=?, first_name=?, last_name=?, phone=?, street_address=?, city=?, password_hash=? WHERE id=?');
                $stmt->execute([$name, $first, $last, $phone, $street, $city, $hash, $authUser['id']]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name=?, first_name=?, last_name=?, phone=?, street_address=?, city=? WHERE id=?');
                $stmt->execute([$name, $first, $last, $phone, $street, $city, $authUser['id']]);
            }
            
            // Refresh session
            $_SESSION['auth_user']['name'] = $name;
            $success = 'Profile updated successfully!';
        } catch (Throwable $e) {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

// Fetch current data
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$authUser['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Foodie.PH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="foodieph.css">
    <style>
        .profile-container { max-width: 600px; margin: 60px auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .profile-title { font-size: 24px; font-weight: bold; margin-bottom: 20px; color: var(--brand); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 6px; font-size: 14px; color: #333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn-save { background: var(--brand); color: #fff; border: none; padding: 12px 20px; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-save:hover { background: var(--brand-dark); }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; }
        .section-header { margin: 30px 0 15px; padding-bottom: 8px; border-bottom: 1px solid #eee; font-weight: bold; color: #555; }
    </style>
</head>
<body style="background: #f7f5f2;">
    <nav class="navbar" style="background:#fff;border-bottom:1px solid #eee;padding:15px 40px;">
        <a href="index.php" class="logo" style="text-decoration:none;font-size:24px;color:var(--brand);font-weight:bold;">Foodie.PH</a>
        <div style="float:right;margin-top:5px;">
            <a href="index.php" style="margin-right:20px;text-decoration:none;color:#333;">Home</a>
            <a href="logout.php" style="text-decoration:none;color:var(--brand);">Logout</a>
        </div>
    </nav>

    <div class="profile-container">
        <h1 class="profile-title">My Profile</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="section-header">Personal Information</div>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled style="background:#f5f5f5;cursor:not-allowed;">
                <small style="color:#777;">Email cannot be changed.</small>
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
            </div>

            <div class="section-header">Default Delivery Address</div>
            <div class="form-group">
                <label>Street Address / Barangay</label>
                <input type="text" name="street_address" value="<?= htmlspecialchars($user['street_address'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" required>
            </div>

            <div class="section-header">Security (Optional)</div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Leave blank to keep current password">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password">
            </div>

            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>
</body>
</html>
