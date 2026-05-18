<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$error = '';
$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'index.php');
$next = trim($next) !== '' ? $next : 'index.php';
if (str_starts_with($next, 'http://') || str_starts_with($next, 'https://')) {
    $next = 'index.php';
}
// Admin login: ?next=admin.php or ?admin=1
if (!empty($_GET['admin']) || !empty($_POST['admin_login'])) {
    $next = 'admin.php';
}
$isAdminLogin = str_contains($next, 'admin.php');
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $isAdminLogin) {
    $user = currentUser();
    if ($user !== null && ($user['role'] ?? '') === 'admin') {
        header('Location: admin.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string) ($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    try {
        if (!loginUser($email, $password)) {
            $error = 'Invalid email or password.';
        } elseif ($isAdminLogin) {
            $user = currentUser();
            if ($user === null || ($user['role'] ?? '') !== 'admin') {
                logoutUser();
                $error = 'This account does not have admin access. Use Create Account to register as staff, or contact support.';
            } else {
                header('Location: admin.php');
                exit;
            }
        } else {
            header('Location: ' . $next);
            exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $isAdminLogin ? 'Admin Sign In' : 'Login' ?> - Foodie.PH</title>
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
.box{width:100%;max-width:460px;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:32px;box-shadow:var(--shadow-lg)}
.tab-bar{display:grid;grid-template-columns:1fr 1fr;border:2px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px}
.tab-btn{padding:12px;font-family:var(--font);font-size:14px;font-weight:700;border:none;cursor:pointer;background:#fff;color:var(--muted);transition:all .2s}
.tab-btn.active{background:var(--brand);color:#fff}
.box-title{font-family:var(--font-display);font-size:26px;font-style:italic;margin-bottom:4px}
.box-title em{font-style:normal;color:var(--brand)}
.box-sub{font-size:13px;color:var(--muted);margin-bottom:20px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.form-group input{width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:var(--font);font-size:14px;color:var(--text);background:#fff;transition:border-color .2s}
.form-group input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(232,40,43,.1)}
.form-group input::placeholder{color:#bbb}
.forgot{text-align:right;font-size:12px;margin-top:-8px;margin-bottom:12px}
.forgot a{color:var(--muted);font-weight:600}
.forgot a:hover{color:var(--brand)}
.submit-btn{width:100%;padding:14px;background:var(--brand);color:#fff;border:none;border-radius:999px;font-family:var(--font);font-size:15px;font-weight:800;cursor:pointer;transition:background .2s;margin-top:8px}
.submit-btn:hover{background:var(--brand-dark)}
.links{margin-top:16px;font-size:13px;color:var(--muted);line-height:1.8}
.links a{color:var(--brand);font-weight:700}
.links a:hover{color:var(--brand-dark);text-decoration:underline}
.alert{margin-top:14px;padding:12px 14px;border-radius:10px;font-size:13px}
.alert-error{background:#fde8e8;color:#a61b1d;border:1px solid #f8c6c6}
.divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--muted);font-size:12px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
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
    <button class="tab-btn active">Log In</button>
    <button class="tab-btn" onclick="window.location='<?= $isAdminLogin ? 'register.php?admin=1' : 'register.php' ?>'">Create Account</button>
  </div>
  <h1 class="box-title"><?= $isAdminLogin ? 'Admin <em>Sign In</em>' : 'Welcome <em>Back</em>' ?></h1>
  <p class="box-sub"><?= $isAdminLogin
      ? 'Sign in with your admin account to manage restaurants, menus, and orders. Admins use the same login as customers.'
      : 'Sign in to your Foodie.PH account to continue ordering.' ?></p>
  <?php if ($registered && $isAdminLogin): ?>
  <div class="alert" style="background:#e8f8ee;color:#146c2f;border:1px solid #c3ebd0;margin-bottom:16px">
    <i class="fas fa-circle-check"></i> Admin account created. Sign in below to open the admin panel.
  </div>
  <?php endif; ?>
  <?php if ($isAdminLogin): ?>
  <div class="info-bar" style="background:#fff8e6;border-color:#ffe0a3;color:#8a5a00;margin-bottom:16px">
    <i class="fas fa-shield-halved"></i>
    No account yet? <a href="register.php?admin=1" style="color:#8a5a00;font-weight:700">Create an admin account</a>
  </div>
  <?php endif; ?>
  <form method="post">
    <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($isAdminLogin): ?><input type="hidden" name="admin_login" value="1"><?php endif; ?>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" required>
    </div>
    <div class="forgot"><a href="#">Forgot password?</a></div>
    <button type="submit" class="submit-btn"><i class="fas fa-right-to-bracket" style="margin-right:8px"></i>Sign In</button>
  </form>
  <?php if ($error !== ''): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <div class="divider">or</div>
  <div class="links">
    No account yet? <a href="register.php">Create one free</a> &nbsp;·&nbsp;
    <a href="login.php?next=admin.php&admin=1">Admin sign in</a><br>
    Want to list your restaurant? <a href="partner.php">Partner With Us</a>
  </div>
</div>
</main>
</body></html>
