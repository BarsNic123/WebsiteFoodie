<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
$authUser = currentUser();
$isAdmin = $authUser !== null && $authUser['role'] === 'admin';
$error = ''; $success = '';
<<<<<<< HEAD
$accountType = 'user';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first    = trim((string)($_POST['first_name']  ?? ''));
    $middle   = trim((string)($_POST['middle_name'] ?? ''));
    $last     = trim((string)($_POST['last_name']   ?? ''));
    $email    = trim((string)($_POST['email']       ?? ''));
    $password = trim((string)($_POST['password']    ?? ''));
    $confirm  = trim((string)($_POST['confirm']     ?? ''));
    $phone          = trim((string)($_POST['phone']       ?? ''));
    $city           = trim((string)($_POST['city']        ?? ''));
    $barangay       = trim((string)($_POST['barangay']    ?? ''));
    $street         = trim((string)($_POST['street']      ?? ''));
    $postal         = trim((string)($_POST['postal']      ?? ''));
    $agree          = isset($_POST['agree']);
    $agree          = isset($_POST['agree']);
    $nameParts      = array_filter([$first, $middle, $last], fn($val) => $val !== '');
    $name           = implode(' ', $nameParts);
    if ($first === '' || $last === '')          { $error = 'Please enter your first and last name.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Please enter a valid email address.'; }
    elseif (strlen($password) < 8)             { $error = 'Password must be at least 8 characters.'; }
    elseif ($password !== $confirm)            { $error = 'Passwords do not match.'; }
    elseif (!$agree)                           { $error = 'You must agree to the Terms & Conditions and Privacy Policy.'; }
    else {
=======
$isAdminRegister = isset($_GET['admin']) || isset($_POST['admin_register']);

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
    $jobTitle = trim((string)($_POST['job_title']    ?? ''));
    $dept     = trim((string)($_POST['department']   ?? ''));
    $staffId  = trim((string)($_POST['staff_id']     ?? ''));
    $emergency = trim((string)($_POST['emergency_contact'] ?? ''));
    $isAdminRegister = isset($_POST['admin_register']);
    $name     = trim($first . ' ' . $last);

    if ($first === '' || $last === '') {
        $error = 'Please enter your first and last name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($phone === '') {
        $error = 'Phone number is required for delivery updates and account security.';
    } elseif ($street === '' || $city === '') {
        $error = 'Street address and city are required.';
    } elseif ($isAdminRegister && ($jobTitle === '' || $dept === '')) {
        $error = 'Job title and department are required for admin / staff registration.';
    } else {
>>>>>>> ab3b258cd789bd39c7e99abe6be0d60099908d2d
        try {
            $userId = registerUser($name, $email, $password, $accountType);
            $config = require __DIR__ . '/config.php';
            if ($config['use_database']) {
<<<<<<< HEAD
                db()->prepare("UPDATE users SET first_name=?,middle_name=?,last_name=?,phone=?,city=?,state=?,street_address=?,postal_code=?,email_notifications=1 WHERE id=?")
                   ->execute([$first,$middle,$last,$phone,$city,$barangay,$street,$postal,$userId]);
=======
                $pdo = db();
                $role = 'user';
                if ($isAdminRegister) {
                    $role = 'admin';
                }
                $pdo->prepare('UPDATE users SET
                    first_name=?, last_name=?, phone=?,
                    street_address=?, unit=?, city=?, state=?, postal_code=?, country=?,
                    email_notifications=?, job_title=?, department=?, staff_id=?, emergency_contact=?,
                    role=?
                    WHERE id=?')->execute([
                    $first, $last, $phone,
                    $street, $unit, $city, $state, $postal, $country,
                    $notify, $jobTitle, $dept, $staffId, $emergency, $role,
                    $userId,
                ]);
>>>>>>> ab3b258cd789bd39c7e99abe6be0d60099908d2d
            }
            if ($isAdminRegister) {
                header('Location: login.php?next=admin.php&registered=1');
                exit;
            }
            $success = 'Account created! You can now log in.';
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$adminLoginUrl = 'login.php?next=admin.php';
$loginUrl = $isAdminRegister ? $adminLoginUrl : 'login.php';
$registerTabUrl = $isAdminRegister ? 'register.php?admin=1' : 'register.php';

function val(string $k): string
{
    return htmlspecialchars($_POST[$k] ?? '', ENT_QUOTES, 'UTF-8');
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
<<<<<<< HEAD
.nav-links{display:flex;gap:24px;flex:1}
.nav-links a{font-size:13px;font-weight:600;color:#6b7280}
.nav-links a:hover{color:var(--brand)}
.nav-actions{margin-left:auto;display:flex;align-items:center;gap:12px}
.btn-login{font-size:13px;font-weight:700;color:var(--text);padding:8px 16px;border-radius:999px;border:1.5px solid var(--border);background:#fff;cursor:pointer;font-family:var(--font)}
.btn-login:hover{border-color:var(--brand);color:var(--brand)}
.btn-register{font-size:13px;font-weight:800;color:#fff;padding:9px 22px;border-radius:999px;background:var(--brand);border:none;cursor:pointer;font-family:var(--font)}
.btn-register:hover{background:var(--brand-dark)}
.btn-ride{font-size:13px;font-weight:800;color:#fff;padding:10px 20px;border-radius:999px;background:#232323;border:none;cursor:pointer;font-family:var(--font);box-shadow:0 10px 24px rgba(0,0,0,.08);transition:background .2s}
.btn-ride:hover{background:#111}
/* page shell */
.page-shell{min-height:calc(100vh - 62px);display:flex;align-items:flex-start;justify-content:center;padding:40px 16px 60px;background:var(--bg)}
/* card */
.card{background:#fff;border-radius:20px;box-shadow:var(--shadow);width:100%;max-width:560px;padding:40px 40px 36px;border:1px solid var(--border)}
.card-title{font-size:26px;font-weight:800;color:var(--text);margin-bottom:6px}
.card-sub{font-size:14px;color:var(--muted);margin-bottom:28px}
/* section label */
.sec-label{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.9px;color:var(--brand);margin:24px 0 14px}
.sec-label i{font-size:13px}
.sec-label::after{content:'';flex:1;height:1px;background:#fde8e8}
/* form grid */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:700;color:var(--text);margin-bottom:7px}
.form-group label .req{color:var(--brand);margin-left:2px}
/* input with icon */
.input-wrap{position:relative}
.input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none}
.input-wrap input,.input-wrap select{width:100%;padding:12px 14px 12px 40px;border:1.5px solid var(--border);border-radius:12px;font-family:var(--font);font-size:14px;color:var(--text);background:#fff;transition:border-color .2s,box-shadow .2s;appearance:none}
.input-wrap input:focus,.input-wrap select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(232,40,43,.1)}
.input-wrap input::placeholder{color:var(--muted)}
.input-wrap input[readonly]{background:#f9fafb;color:var(--muted);cursor:default}
/* select arrow */
.input-wrap.has-select::after{content:'\f107';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--muted);pointer-events:none;font-size:13px}
/* password toggle */
.input-wrap .pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:15px;padding:0}
.input-wrap .pw-toggle:hover{color:var(--brand)}
/* checkbox */
.check-row{display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text);margin-bottom:20px;line-height:1.5}
.check-row input[type=checkbox]{width:18px;height:18px;accent-color:var(--brand);margin-top:1px;flex-shrink:0;border-radius:4px}
.check-row a{color:var(--brand);font-weight:700}
/* submit */
.submit-btn{width:100%;padding:15px;background:var(--brand);color:#fff;border:none;border-radius:14px;font-family:var(--font);font-size:16px;font-weight:800;cursor:pointer;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:8px}
=======
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
>>>>>>> ab3b258cd789bd39c7e99abe6be0d60099908d2d
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
<<<<<<< HEAD
<nav>
  <div class="nav-inner">
    <a href="foodieph.html" class="logo">Foodie<span>.PH</span></a>
    <a href="foodieph.html" class="nav-back" style="margin-left:auto;font-size:13px;font-weight:600;color:var(--muted);display:flex;align-items:center;gap:6px;"><i class="fas fa-arrow-left"></i> Back to Home</a>
=======
<nav><div class="nav-inner">
  <a href="foodieph.html" class="logo">Foodie<span>.PH</span></a>
  <a href="foodieph.html" class="nav-back"><i class="fas fa-arrow-left"></i> Back to Home</a>
</div></nav>
<main class="auth-shell">
<div class="box">
  <div class="tab-bar">
    <button class="tab-btn" onclick="window.location='<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>'">Log In</button>
    <button class="tab-btn active">Create Account</button>
>>>>>>> ab3b258cd789bd39c7e99abe6be0d60099908d2d
  </div>
  <h1 class="box-title"><?= $isAdminRegister ? 'Admin <em>Registration</em>' : 'Create an <em>Account</em>' ?></h1>
  <p class="box-sub"><?= $isAdminRegister
      ? 'After you submit, you will be taken to the admin sign-in page (same login form, labeled Admin Sign In).'
      : 'Join Foodie.PH and start ordering from your favorite restaurants.' ?></p>
  <div class="info-bar"><i class="fas fa-circle-info"></i> Fields marked with * are required</div>

  <?php if ($success !== ''): ?>
    <div class="alert alert-ok"><i class="fas fa-circle-check"></i><span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?> <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Login now &rarr;</a></span></div>
  <?php else: ?>
  <form method="post" id="reg-form">
    <?php if ($isAdminRegister): ?><input type="hidden" name="admin_register" value="1"><?php endif; ?>

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
<<<<<<< HEAD
    <h1 class="card-title">Create your account</h1>
    <p class="card-sub">Complete the registration to start using Foodie.PH.</p>
=======
>>>>>>> ab3b258cd789bd39c7e99abe6be0d60099908d2d

    <!-- Phone -->
    <div class="section-label"><i class="fas fa-mobile-screen"></i> Phone Number</div>
    <div class="form-group">
      <label>*Phone Number <span style="color:var(--muted);font-weight:400;text-transform:none">(for delivery updates &amp; 2FA)</span></label>
      <div class="phone-row">
        <input type="text" value="+63" readonly style="background:#f5f5f5;color:var(--muted);text-align:center;font-weight:600">
        <input type="tel" name="phone" placeholder="9XX XXX XXXX" required value="<?= val('phone') ?>">
      </div>
      <p class="hint"><i class="fas fa-shield-halved" style="color:var(--brand)"></i> Used for Two-Factor Authentication and delivery status updates</p>
    </div>

    <!-- Shipping Address -->
    <div class="section-label"><i class="fas fa-location-dot"></i> Default Shipping Address</div>
    <div class="form-group">
      <label>*Street Address</label>
      <input type="text" name="street" placeholder="House No. / Street Name / Barangay" required value="<?= val('street') ?>">
    </div>
    <div class="form-group">
      <label>Apartment / Suite / Unit <span style="color:var(--muted);font-weight:400;text-transform:none">(optional)</span></label>
      <input type="text" name="unit" placeholder="Unit, Floor, Building name" value="<?= val('unit') ?>">
    </div>
    <div class="form-row">
      <div class="form-group"><label>*City / Municipality</label><input type="text" name="city" placeholder="e.g. Makati" required value="<?= val('city') ?>"></div>
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

    <?php if ($isAdminRegister): ?>
    <div class="section-label"><i class="fas fa-shield-halved"></i> Staff / Admin Details</div>
    <div class="form-row">
      <div class="form-group"><label>*Job Title / Position</label><input type="text" name="job_title" placeholder="e.g. Operations Manager" required value="<?= val('job_title') ?>"></div>
      <div class="form-group"><label>*Department</label><input type="text" name="department" placeholder="e.g. Platform Operations" required value="<?= val('department') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Staff / Employee ID</label><input type="text" name="staff_id" placeholder="Optional internal ID" value="<?= val('staff_id') ?>"></div>
      <div class="form-group"><label>*Emergency Contact Number</label><input type="tel" name="emergency_contact" placeholder="09XX XXX XXXX" required value="<?= val('emergency_contact') ?>"></div>
    </div>
    <?php else: ?>
<<<<<<< HEAD
    <form method="post" id="reg-form" enctype="multipart/form-data" novalidate>



      <!-- Personal Info -->
      <div class="sec-label"><i class="fas fa-user"></i> Personal Information</div>
      <div class="form-row">
        <div class="form-group">
          <label>First name <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-user"></i><input type="text" name="first_name" placeholder="Juan" required value="<?= v('first_name') ?>"></div>
        </div>
        <div class="form-group">
          <label>Middle name</label>
          <div class="input-wrap"><i class="fas fa-user"></i><input type="text" name="middle_name" placeholder="Optional" value="<?= v('middle_name') ?>"></div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Last name <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-user"></i><input type="text" name="last_name" placeholder="Dela Cruz" required value="<?= v('last_name') ?>"></div>
        </div>
        <div class="form-group">
          <label>Phone number <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-phone"></i><input type="tel" name="phone" placeholder="+63 917 123 4567" required value="<?= v('phone') ?>"></div>
        </div>
      </div>
      <div class="form-group">
        <label>Email address <span class="req">*</span></label>
        <div class="input-wrap"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="you@email.com" required value="<?= v('email') ?>"></div>
      </div>

      <!-- Address -->
      <div class="sec-label"><i class="fas fa-location-dot"></i> Delivery Address</div>
      <div class="form-row">
        <div class="form-group">
          <label>City <span class="req">*</span></label>
          <div class="input-wrap has-select">
            <i class="fas fa-city"></i>
            <select name="city" id="city-select" required onchange="updateBarangays()">
              <option value="">Select city</option>
              <?php foreach($cities as $c): ?>
              <option value="<?= htmlspecialchars($c,ENT_QUOTES,'UTF-8') ?>" <?= v('city')===$c?'selected':'' ?>><?= htmlspecialchars($c,ENT_QUOTES,'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Barangay <span class="req">*</span></label>
          <div class="input-wrap has-select">
            <i class="fas fa-map-pin"></i>
            <select name="barangay" id="barangay-select" required>
              <option value="">Select city first</option>
            </select>
          </div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Street <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-road"></i><input type="text" name="street" placeholder="Unit/Building, Street Name" value="<?= v('street') ?>"></div>
        </div>
        <div class="form-group">
          <label>Zipcode</label>
          <div class="input-wrap"><i class="fas fa-hashtag"></i><input type="text" name="postal" id="postal-input" placeholder="Auto-filled" readonly value="<?= v('postal') ?>"></div>
        </div>
      </div>



      <!-- Account Security -->
      <div class="sec-label"><i class="fas fa-shield-halved"></i> Account Security</div>
      <div class="form-group">
        <label>Password <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" id="pw1" placeholder="Min. 8 characters" required minlength="8">
          <button type="button" class="pw-toggle" onclick="togglePw('pw1',this)"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <div class="form-group">
        <label>Confirm password <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" name="confirm" id="pw2" placeholder="Re-enter your password" required minlength="8">
          <button type="button" class="pw-toggle" onclick="togglePw('pw2',this)"><i class="fas fa-eye"></i></button>
        </div>
      </div>

      <!-- Terms -->
      <label class="check-row">
        <input type="checkbox" name="agree" required>
        <span>I agree to Foodie.PH's <a href="#">Terms &amp; Conditions</a> and <a href="#">Privacy Policy</a></span>
      </label>

      <button type="submit" class="submit-btn">Register <i class="fas fa-arrow-right"></i></button>
    </form>
    <?php if ($error !== ''): ?>
      <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span></div>
    <?php endif; ?>
=======
    <div class="section-label"><i class="fas fa-briefcase"></i> Optional Staff Info</div>
    <div class="form-row">
      <div class="form-group"><label>Job Title</label><input type="text" name="job_title" placeholder="Optional" value="<?= val('job_title') ?>"></div>
      <div class="form-group"><label>Department</label><input type="text" name="department" placeholder="Optional" value="<?= val('department') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Staff ID</label><input type="text" name="staff_id" placeholder="Optional" value="<?= val('staff_id') ?>"></div>
      <div class="form-group"><label>Emergency Contact</label><input type="tel" name="emergency_contact" placeholder="Optional" value="<?= val('emergency_contact') ?>"></div>
    </div>
>>>>>>> ab3b258cd789bd39c7e99abe6be0d60099908d2d
    <?php endif; ?>

    <!-- Preferences -->
    <div class="section-label"><i class="fas fa-bell"></i> Preferences</div>
    <label class="check-row"><input type="checkbox" name="notify" checked><span>I would like to receive order status notifications and promos by email &amp; SMS.</span></label>

    <button type="submit" class="submit-btn"><i class="fas fa-user-plus" style="margin-right:8px"></i>Create Account</button>
  </form>
  <?php if ($error !== ''): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?>
  <?php endif; ?>

  <div class="links">
    Already have an account? <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">Log In</a><br>
    <?= $isAdminRegister ? 'Ordering food? <a href="register.php">Register as a customer</a><br>' : '' ?>
    Want to list your restaurant? <a href="partner.php">Partner With Us</a>
  </div>
</div>
<<<<<<< HEAD

<script>
const barangayData = <?= json_encode($barangays, JSON_UNESCAPED_UNICODE) ?>;
const zipcodes = {
  'Manila':'1000','Quezon City':'1100','Makati':'1200','Pasig':'1600','Taguig':'1630',
  'Mandaluyong':'1550','Marikina':'1800','Pasay':'1300','Paranaque':'1700','Las Pinas':'1740',
  'Muntinlupa':'1770','Caloocan':'1400','Malabon':'1470','Navotas':'1485','Valenzuela':'1440',
  'Cebu City':'6000','Lapu-Lapu':'6015','Mandaue':'6014'
};
const savedCity = <?= json_encode(v('city')) ?>;
const savedBarangay = <?= json_encode(v('barangay')) ?>;


function updateBarangays() {
  const city = document.getElementById('city-select').value;
  const sel  = document.getElementById('barangay-select');
  const list = barangayData[city] || barangayData['default'];
  sel.innerHTML = '<option value="">Select barangay</option>' +
    list.map(b => `<option value="${b}"${b===savedBarangay?' selected':''}>${b}</option>`).join('');
  document.getElementById('postal-input').value = zipcodes[city] || '';
}

function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const isText = inp.type === 'text';
  inp.type = isText ? 'password' : 'text';
  btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
}

// Init barangays if city was pre-selected (form re-submit)
if (savedCity) { document.getElementById('city-select').value = savedCity; updateBarangays(); }

// Client-side password match check
document.getElementById('reg-form') && document.getElementById('reg-form').addEventListener('submit', function(e) {
  const p1 = document.getElementById('pw1').value;
  const p2 = document.getElementById('pw2').value;
  if (p1 !== p2) { e.preventDefault(); alert('Passwords do not match.'); }
});
</script>
=======
</main>
>>>>>>> ab3b258cd789bd39c7e99abe6be0d60099908d2d
</body></html>
