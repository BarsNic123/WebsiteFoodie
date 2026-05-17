<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first    = trim((string)($_POST['first_name']    ?? ''));
    $last     = trim((string)($_POST['last_name']     ?? ''));
    $email    = trim((string)($_POST['email']         ?? ''));
    $password = trim((string)($_POST['password']      ?? ''));
    $phone    = trim((string)($_POST['phone']         ?? ''));
    $street   = trim((string)($_POST['street']        ?? ''));
    $unit     = trim((string)($_POST['unit']          ?? ''));
    $city     = trim((string)($_POST['city']          ?? ''));
    $state    = trim((string)($_POST['state']         ?? ''));
    $postal   = trim((string)($_POST['postal']        ?? ''));
    $country  = trim((string)($_POST['country']       ?? 'Philippines'));
    $notify   = isset($_POST['notify']) ? 1 : 0;
    $name     = trim($first . ' ' . $last);
    if ($first === '' || $last === '') { $error = 'Please enter your first and last name.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Please enter a valid email address.'; }
    elseif (strlen($password) < 6) { $error = 'Password must be at least 6 characters.'; }
    else {
        try {
            $userId = registerUser($name, $email, $password);
            // Save extended profile fields
            $config = require __DIR__ . '/config.php';
            if ($config['use_database']) {
                $pdo = db();
                $pdo->prepare("UPDATE users SET
                    first_name=?, last_name=?, phone=?,
                    street_address=?, unit=?, city=?, state=?, postal_code=?, country=?,
                    email_notifications=?
                    WHERE id=?")->execute([
                    $first, $last, $phone,
                    $street, $unit, $city, $state, $postal, $country,
                    $notify, $userId
                ]);
            }
            $success = 'Account created! You can now log in.';
        } catch (Throwable $e) { $error = $e->getMessage(); }
    }
}
function val(string $k): string { return htmlspecialchars($_POST[$k] ?? '', ENT_QUOTES, 'UTF-8'); }
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account - Foodie.PH</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--brand:#e8282b;--brand-dark:#b81e20;--gold:#f5a623;--bg:#f7f5f2;--surface:#fff;--text:#1a1a1a;--muted:#757575;--border:#e8e6e2;--shadow-lg:0 10px 34px rgba(0,0,0,.14);--font:'Plus Jakarta Sans',sans-serif;--font-display:'Fraunces',serif}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font);background:var(--bg);color:var(--text)}
a{text-decoration:none}
.topbar{background:var(--brand);color:#fff;text-align:center;font-size:12px;padding:8px 16px}
.topbar a{color:#ffe8e8}
nav{background:#fff;border-bottom:1px solid var(--border);box-shadow:0 2px 12px rgba(0,0,0,.07)}
.nav-inner{max-width:1200px;margin:0 auto;padding:0 24px;height:64px;display:flex;align-items:center;gap:16px}
.logo{font-family:var(--font-display);font-size:24px;color:var(--brand)}
.logo span{color:var(--text);font-style:italic}
.nav-back{margin-left:auto;font-size:13px;font-weight:600;color:var(--muted);display:flex;align-items:center;gap:6px}
.nav-back:hover{color:var(--brand)}
.auth-shell{min-height:calc(100vh - 100px);display:grid;place-items:center;padding:32px 16px;background:linear-gradient(160deg,#fff 0%,#fff6f6 100%)}
.box{width:100%;max-width:580px;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;box-shadow:var(--shadow-lg)}
.tab-bar{display:grid;grid-template-columns:1fr 1fr;border:2px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px}
.tab-btn{padding:12px;font-family:var(--font);font-size:14px;font-weight:700;border:none;cursor:pointer;background:#fff;color:var(--muted);transition:all .2s}
.tab-btn.active{background:var(--brand);color:#fff}
.box-title{font-family:var(--font-display);font-size:26px;font-style:italic;margin-bottom:4px}
.box-title em{font-style:normal;color:var(--brand)}
.box-sub{font-size:13px;color:var(--muted);margin-bottom:18px}
.info-bar{background:#fff1f1;border:1px solid #ffd6d6;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--brand);display:flex;align-items:center;gap:8px;margin-bottom:18px}
.section-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--brand);margin:20px 0 12px;display:flex;align-items:center;gap:8px}
.section-label::after{content:'';flex:1;height:1px;background:#ffd6d6}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.form-group input,.form-group select{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:var(--font);font-size:14px;color:var(--text);background:#fff;transition:border-color .2s}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(232,40,43,.1)}
.form-group input::placeholder{color:#bbb}
.form-group .hint{font-size:11px;color:var(--muted);margin-top:4px;display:flex;align-items:center;gap:4px}
.phone-row{display:grid;grid-template-columns:72px 1fr;gap:8px}
.check-row{display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text);margin-bottom:12px;cursor:pointer;line-height:1.5}
.check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--brand);margin-top:2px;flex-shrink:0}
.submit-btn{width:100%;padding:14px;background:var(--brand);color:#fff;border:none;border-radius:999px;font-family:var(--font);font-size:15px;font-weight:800;cursor:pointer;transition:background .2s;margin-top:8px}
.submit-btn:hover{background:var(--brand-dark)}
.links{margin-top:16px;font-size:13px;color:var(--muted);line-height:1.8}
.links a{color:var(--brand);font-weight:700}
.links a:hover{color:var(--brand-dark);text-decoration:underline}
.alert{margin-top:14px;padding:12px 14px;border-radius:10px;font-size:13px;display:flex;align-items:flex-start;gap:8px}
.alert-error{background:#fde8e8;color:#a61b1d;border:1px solid #f8c6c6}
.alert-ok{background:#e8f8ee;color:#146c2f;border:1px solid #c3ebd0}
@media(max-width:520px){.form-row,.form-row-3{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="topbar">&#128222; Globe: <strong>09177135477</strong> &nbsp;|&nbsp; Fast. Fresh. Nationwide Delivery &nbsp;|&nbsp; <a href="foodieph.html">Back to Foodie.PH</a></div>
<nav><div class="nav-inner">
  <a href="foodieph.html" class="logo">Foodie<span>.PH</span></a>
  <a href="foodieph.html" class="nav-back"><i class="fas fa-arrow-left"></i> Back to Home</a>
</div></nav>
<main class="auth-shell">
<div class="box">
  <div class="tab-bar">
    <button class="tab-btn" onclick="window.location='login.php'">Log In</button>
    <button class="tab-btn active">Create Account</button>
  </div>
  <h1 class="box-title">Create an <em>Account</em></h1>
  <p class="box-sub">Join Foodie.PH and start ordering from your favorite restaurants.</p>
  <div class="info-bar"><i class="fas fa-circle-info"></i> Fields marked with * are required</div>

  <?php if ($success !== ''): ?>
    <div class="alert alert-ok"><i class="fas fa-circle-check"></i><span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?> <a href="login.php">Login now &rarr;</a></span></div>
  <?php else: ?>
  <form method="post" id="reg-form">

    <!-- Account Info -->
    <div class="section-label"><i class="fas fa-user"></i> Account Information</div>
    <div class="form-row">
      <div class="form-group"><label>*First Name</label><input type="text" name="first_name" placeholder="First Name" required value="<?= val('first_name') ?>"></div>
      <div class="form-group"><label>*Last Name</label><input type="text" name="last_name" placeholder="Last Name" required value="<?= val('last_name') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>*Email Address</label><input type="email" name="email" placeholder="you@email.com" required value="<?= val('email') ?>"></div>
      <div class="form-group"><label>*Password</label><input type="password" name="password" placeholder="Min. 6 characters" required minlength="6"></div>
    </div>

    <!-- Phone -->
    <div class="section-label"><i class="fas fa-mobile-screen"></i> Phone Number</div>
    <div class="form-group">
      <label>Phone Number <span style="color:var(--muted);font-weight:400;text-transform:none">(for delivery updates &amp; 2FA)</span></label>
      <div class="phone-row">
        <input type="text" value="+63" readonly style="background:#f5f5f5;color:var(--muted);text-align:center;font-weight:600">
        <input type="tel" name="phone" placeholder="9XX XXX XXXX" value="<?= val('phone') ?>">
      </div>
      <p class="hint"><i class="fas fa-shield-halved" style="color:var(--brand)"></i> Used for Two-Factor Authentication and delivery status updates</p>
    </div>

    <!-- Shipping Address -->
    <div class="section-label"><i class="fas fa-location-dot"></i> Default Shipping Address</div>
    <div class="form-group">
      <label>Street Address</label>
      <input type="text" name="street" placeholder="House No. / Street Name / Barangay" value="<?= val('street') ?>">
    </div>
    <div class="form-group">
      <label>Apartment / Suite / Unit <span style="color:var(--muted);font-weight:400;text-transform:none">(optional)</span></label>
      <input type="text" name="unit" placeholder="Unit, Floor, Building name" value="<?= val('unit') ?>">
    </div>
    <div class="form-row">
      <div class="form-group"><label>City / Municipality</label><input type="text" name="city" placeholder="e.g. Makati" value="<?= val('city') ?>"></div>
      <div class="form-group"><label>State / Province / Region</label><input type="text" name="state" placeholder="e.g. Metro Manila" value="<?= val('state') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Postal / Zip Code</label><input type="text" name="postal" placeholder="e.g. 1200" value="<?= val('postal') ?>"></div>
      <div class="form-group"><label>Country</label>
        <select name="country">
          <option value="Philippines" <?= (val('country')==='Philippines'||val('country')==='') ? 'selected':'' ?>>Philippines</option>
          <option value="United States" <?= val('country')==='United States'?'selected':'' ?>>United States</option>
          <option value="Canada" <?= val('country')==='Canada'?'selected':'' ?>>Canada</option>
          <option value="Australia" <?= val('country')==='Australia'?'selected':'' ?>>Australia</option>
          <option value="United Kingdom" <?= val('country')==='United Kingdom'?'selected':'' ?>>United Kingdom</option>
          <option value="Singapore" <?= val('country')==='Singapore'?'selected':'' ?>>Singapore</option>
          <option value="Japan" <?= val('country')==='Japan'?'selected':'' ?>>Japan</option>
          <option value="Other" <?= val('country')==='Other'?'selected':'' ?>>Other</option>
        </select>
      </div>
    </div>

    <!-- Preferences -->
    <div class="section-label"><i class="fas fa-bell"></i> Preferences</div>
    <label class="check-row"><input type="checkbox" name="notify" checked><span>I would like to receive order status notifications and promos by email &amp; SMS.</span></label>

    <button type="submit" class="submit-btn"><i class="fas fa-user-plus" style="margin-right:8px"></i>Create Account</button>
  </form>
  <?php if ($error !== ''): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?>
  <?php endif; ?>

  <div class="links">
    Already have an account? <a href="login.php">Log In</a><br>
    Want to list your restaurant? <a href="partner.php">Partner With Us</a>
  </div>
</div>
</main>
</body></html>
