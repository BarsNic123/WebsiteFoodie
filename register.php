<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first    = trim((string)($_POST['first_name'] ?? ''));
    $last     = trim((string)($_POST['last_name']  ?? ''));
    $email    = trim((string)($_POST['email']      ?? ''));
    $password = trim((string)($_POST['password']   ?? ''));
    $phone    = trim((string)($_POST['phone']      ?? ''));
    $name     = $first . ' ' . $last;
    try {
        registerUser($name, $email, $password);
        $success = 'Account created! You can now log in.';
    } catch (Throwable $e) { $error = $e->getMessage(); }
}
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
.box{width:100%;max-width:520px;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;box-shadow:var(--shadow-lg)}
.tab-bar{display:grid;grid-template-columns:1fr 1fr;border:2px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px}
.tab-btn{padding:12px;font-family:var(--font);font-size:14px;font-weight:700;border:none;cursor:pointer;background:#fff;color:var(--muted);transition:all .2s}
.tab-btn.active{background:var(--brand);color:#fff}
.box-title{font-family:var(--font-display);font-size:26px;font-style:italic;margin-bottom:4px}
.box-title em{font-style:normal;color:var(--brand)}
.box-sub{font-size:13px;color:var(--muted);margin-bottom:18px}
.info-bar{background:#fff1f1;border:1px solid #ffd6d6;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--brand);display:flex;align-items:center;gap:8px;margin-bottom:18px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.form-group input,.form-group select{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:var(--font);font-size:14px;color:var(--text);background:#fff;transition:border-color .2s}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(232,40,43,.1)}
.form-group input::placeholder{color:#bbb}
.phone-row{display:grid;grid-template-columns:80px 1fr;gap:8px}
.check-row{display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text);margin-bottom:12px;cursor:pointer}
.check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--brand);margin-top:2px;flex-shrink:0}
.submit-btn{width:100%;padding:14px;background:var(--brand);color:#fff;border:none;border-radius:999px;font-family:var(--font);font-size:15px;font-weight:800;cursor:pointer;transition:background .2s;margin-top:8px}
.submit-btn:hover{background:var(--brand-dark)}
.links{margin-top:16px;font-size:13px;color:var(--muted);line-height:1.8}
.links a{color:var(--brand);font-weight:700}
.links a:hover{color:var(--brand-dark);text-decoration:underline}
.alert{margin-top:14px;padding:12px 14px;border-radius:10px;font-size:13px}
.alert-error{background:#fde8e8;color:#a61b1d;border:1px solid #f8c6c6}
.alert-ok{background:#e8f8ee;color:#146c2f;border:1px solid #c3ebd0}
@media(max-width:480px){.form-row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="topbar">?? Globe: <strong>09177135477</strong> &nbsp;|&nbsp; Fast. Fresh. Nationwide Delivery &nbsp;|&nbsp; <a href="foodieph.html">Back to Foodie.PH</a></div>
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
  <form method="post" id="reg-form">
    <div class="form-row">
      <div class="form-group"><label>*First Name</label><input type="text" name="first_name" placeholder="*First Name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
      <div class="form-group"><label>*Last Name</label><input type="text" name="last_name" placeholder="*Last Name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>*Email</label><input type="email" name="email" placeholder="*Email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
      <div class="form-group"><label>*Password</label><input type="password" name="password" placeholder="*Password" required minlength="6"></div>
    </div>
    <div class="form-group">
      <label>Phone Number</label>
      <div class="phone-row">
        <input type="text" value="+63" readonly style="background:#f5f5f5;color:var(--muted);text-align:center">
        <input type="tel" name="phone" placeholder="9XX XXX XXXX">
      </div>
    </div>
    <label class="check-row"><input type="checkbox" name="notify" checked><span>I would like to receive order status notifications and promos by email.</span></label>
    <button type="submit" class="submit-btn">Create Account</button>
  </form>
  <?php if ($error !== ''): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <?php if ($success !== ''): ?><div class="alert alert-ok"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?> <a href="login.php">Login now</a></div><?php endif; ?>
  <div class="links">
    Already have an account? <a href="login.php">Log In</a><br>
    Want to list your restaurant? <a href="partner.php">Partner With Us</a>
  </div>
</div>
</main>
</body></html>
