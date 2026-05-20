<?php
declare(strict_types=1);
$configFile = __DIR__ . '/config.php';
$config = is_readable($configFile) ? require $configFile : ['use_database' => false];
if (!empty($config['use_database'])) {
    require_once __DIR__ . '/includes/db.php';
}
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $biz_name   = trim((string)($_POST['biz_name']   ?? ''));
    $owner_first= trim((string)($_POST['owner_first'] ?? ''));
    $owner_last = trim((string)($_POST['owner_last']  ?? ''));
    $category   = trim((string)($_POST['category']    ?? ''));
    $bir_form   = trim((string)($_POST['bir_form']    ?? ''));
    $has_device = trim((string)($_POST['has_device']  ?? ''));
    $email      = trim((string)($_POST['email']       ?? ''));
    $phone      = trim((string)($_POST['phone']       ?? ''));
    $address    = trim((string)($_POST['address']     ?? ''));
    $city       = trim((string)($_POST['city']        ?? ''));
    $bir_tin    = trim((string)($_POST['bir_tin']     ?? ''));
    $permit_no  = trim((string)($_POST['permit_no']   ?? ''));
    $delivery_fee = trim((string)($_POST['delivery_fee'] ?? ''));
    $operating_hours = trim((string)($_POST['operating_hours'] ?? ''));
    $agree      = isset($_POST['agree']);
    $same_phone = isset($_POST['same_phone']);
    $updates    = isset($_POST['updates']);
    
    $password   = trim((string)($_POST['password'] ?? ''));
    $confirm    = trim((string)($_POST['confirm_password'] ?? ''));

    $required = ['Business Name'=>$biz_name,'Owner First Name'=>$owner_first,'Owner Last Name'=>$owner_last,'Business Category'=>$category,'Business Email'=>$email,'Phone Number'=>$phone,'Business Address'=>$address,'City'=>$city,'BIR TIN'=>$bir_tin,'Business Permit No.'=>$permit_no,'Delivery Fee'=>$delivery_fee,'Operating Hours'=>$operating_hours,'Password'=>$password];
    foreach ($required as $lbl => $val) { if ($val==='') { $error="Please fill in: $lbl"; break; } }
    if ($error==='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Please enter a valid business email.';
    if ($error==='' && strlen($password) < 6) $error='Password must be at least 6 characters.';
    if ($error==='' && $password !== $confirm) $error='Passwords do not match.';
    if ($error==='' && $bir_form==='') $error='Please indicate if you have a BIR 2303 form.';
    if ($error==='' && $has_device==='') $error='Please indicate if you have an Android device.';
    if ($error==='' && !$agree) $error='You must agree to the Partner Terms and Conditions.';
    if ($error === '') {
        $docUrl = '';
        if (isset($_FILES['legitimacy_doc']) && $_FILES['legitimacy_doc']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = uniqid('doc_') . '_' . basename($_FILES['legitimacy_doc']['name']);
            $targetFile = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['legitimacy_doc']['tmp_name'], $targetFile)) {
                $docUrl = 'uploads/documents/' . $filename;
            } else {
                $error = 'Failed to save the uploaded document.';
            }
        } else {
            $error = 'Please upload a valid Document of Legitimacy.';
        }
    }

    if ($error === '') {
        try {
            if (!empty($config['use_database'])) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare("INSERT INTO restaurant_applications (restaurant_name,cuisine_type,description,owner_name,owner_email,owner_phone,business_address,city,delivery_zones,operating_hours,avg_delivery_time,delivery_fee,min_order,payment_methods,bir_tin,business_permit,social_media,how_heard,status,legitimacy_doc_url,password_hash) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending',?,?)");
                $stmt->execute([$biz_name,$category,'',''.($owner_first.' '.$owner_last),$email,$phone,$address,$city,'Metro Manila',$operating_hours,'60-90 mins',(int)$delivery_fee,0,'Cash on Delivery',$bir_tin,$permit_no,'','',$docUrl,$hash]);
            }
            $success = 'Thank you, '.$owner_first.'! Your application for <strong>'.htmlspecialchars($biz_name,ENT_QUOTES,'UTF-8').'</strong> has been submitted. Our team will contact you within 2-3 business days.';
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(),'restaurant_applications')) {
                $success = 'Thank you, '.$owner_first.'! Your application has been received. Our team will contact you within 2-3 business days.';
            } else { $error='Something went wrong. Please try again.'; error_log('Partner: '.$e->getMessage()); }
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Partner With Us - Foodie.PH</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--brand:#e8282b;--brand-dark:#b81e20;--gold:#f5a623;--bg:#f7f5f2;--surface:#fff;--text:#1a1a1a;--muted:#757575;--border:#e8e6e2;--shadow-lg:0 10px 34px rgba(0,0,0,.14);--font:'Plus Jakarta Sans',sans-serif;--font-display:'Fraunces',serif}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font);color:var(--text)}
a{text-decoration:none}
.topbar{background:var(--brand);color:#fff;text-align:center;font-size:12px;padding:8px 16px}
.topbar a{color:#ffe8e8}
nav{background:#fff;border-bottom:1px solid var(--border);box-shadow:0 2px 12px rgba(0,0,0,.07);position:sticky;top:0;z-index:100}
.nav-inner{max-width:1200px;margin:0 auto;padding:0 24px;height:64px;display:flex;align-items:center;gap:16px}
.logo{font-family:var(--font-display);font-size:24px;color:var(--brand)}
.logo span{color:var(--text);font-style:italic}
.nav-back{margin-left:auto;font-size:13px;font-weight:600;color:var(--muted);display:flex;align-items:center;gap:6px}
.nav-back:hover{color:var(--brand)}
/* hero split */
.partner-shell{display:grid;grid-template-columns:1fr 1fr;min-height:calc(100vh - 100px)}
.hero-side{background:linear-gradient(160deg,rgba(26,5,5,.7),rgba(90,10,10,.8)),url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=900&q=70') center/cover no-repeat;display:flex;flex-direction:column;justify-content:center;padding:60px 48px;color:#fff}
.hero-side h1{font-family:var(--font-display);font-size:clamp(28px,3.5vw,44px);font-style:italic;line-height:1.15;margin-bottom:16px}
.hero-side h1 em{font-style:normal;color:var(--gold)}
.hero-side p{font-size:15px;opacity:.85;line-height:1.7;max-width:380px;margin-bottom:32px}
.perks{display:flex;flex-direction:column;gap:14px}
.perk{display:flex;align-items:center;gap:12px;font-size:14px;font-weight:600}
.perk-icon{width:36px;height:36px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
/* form side */
.form-side{background:var(--bg);overflow-y:auto;padding:48px 40px;display:flex;flex-direction:column;justify-content:center}
.form-card{background:#fff;border-radius:20px;padding:36px;box-shadow:var(--shadow-lg);max-width:560px;width:100%;margin:0 auto}
.form-card h2{font-family:var(--font-display);font-size:22px;font-style:italic;margin-bottom:4px}
.form-card h2 em{font-style:normal;color:var(--brand)}
.form-card .sub{font-size:13px;color:var(--muted);margin-bottom:24px}
.section-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--brand);margin:20px 0 12px;display:flex;align-items:center;gap:8px}
.section-label::after{content:'';flex:1;height:1px;background:#ffd6d6}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:12px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:var(--font);font-size:14px;color:var(--text);background:#fff;transition:border-color .2s;resize:none}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(232,40,43,.1)}
.form-group input::placeholder,.form-group textarea::placeholder{color:#bbb}
.phone-row{display:grid;grid-template-columns:72px 1fr;gap:8px}
.radio-group{display:flex;gap:20px;margin-top:4px}
.radio-label{display:flex;align-items:center;gap:7px;font-size:14px;cursor:pointer}
.radio-label input[type=radio]{accent-color:var(--brand);width:16px;height:16px}
.check-row{display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--text);margin-bottom:10px;cursor:pointer;line-height:1.5}
.check-row input[type=checkbox]{accent-color:var(--brand);width:16px;height:16px;margin-top:2px;flex-shrink:0}
.submit-btn{width:100%;padding:15px;background:var(--brand);color:#fff;border:none;border-radius:999px;font-family:var(--font);font-size:15px;font-weight:800;cursor:pointer;transition:background .2s;margin-top:12px;letter-spacing:.2px}
.submit-btn:hover{background:var(--brand-dark)}
.links{margin-top:16px;font-size:13px;color:var(--muted);line-height:1.8;text-align:center}
.links a{color:var(--brand);font-weight:700}
.links a:hover{color:var(--brand-dark);text-decoration:underline}
.alert{margin-top:14px;padding:12px 14px;border-radius:10px;font-size:13px}
.alert-error{background:#fde8e8;color:#a61b1d;border:1px solid #f8c6c6}
.alert-ok{background:#e8f8ee;color:#146c2f;border:1px solid #c3ebd0}
.required-note{background:#fff1f1;border:1px solid #ffd6d6;border-radius:8px;padding:10px 14px;font-size:13px;color:var(--brand);display:flex;align-items:center;gap:8px;margin-bottom:20px}
@media(max-width:900px){.partner-shell{grid-template-columns:1fr}.hero-side{padding:40px 28px;min-height:260px}.form-side{padding:32px 20px}}
@media(max-width:480px){.form-row{grid-template-columns:1fr}.form-card{padding:24px 18px}}
</style>
</head>
<body>
<div class="topbar"><i class="fas fa-phone"></i> Globe: <strong>09177135477</strong> &nbsp;|&nbsp; Fast. Fresh. Nationwide Delivery &nbsp;|&nbsp; <a href="foodieph.html">Back to Foodie.PH</a></div>
<nav><div class="nav-inner">
  <a href="foodieph.html" class="logo">Foodie<span>.PH</span></a>
  <a href="foodieph.html" class="nav-back"><i class="fas fa-arrow-left"></i> Back to Home</a>
</div></nav>
<div class="partner-shell">
  <!-- LEFT HERO -->
  <div class="hero-side">
    <h1>Register your restaurant<br>with <em>Foodie.PH!</em></h1>
    <p>Sign up easily, showcase your menu, and start reaching thousands of new customers across the Philippines.</p>
    <div class="perks">
      <div class="perk"><div class="perk-icon"><i class="fas fa-rocket"></i></div><span>Go live in as fast as 48 hours</span></div>
      <div class="perk"><div class="perk-icon"><i class="fas fa-users"></i></div><span>Reach more customers nationwide</span></div>
      <div class="perk"><div class="perk-icon"><i class="fas fa-credit-card"></i></div><span>Multiple payment options supported</span></div>
      <div class="perk"><div class="perk-icon"><i class="fas fa-truck"></i></div><span>We handle delivery logistics for you</span></div>
    </div>
  </div>
  <!-- RIGHT FORM -->
  <div class="form-side">
    <div class="form-card">
      <h2>Ready to boost your <em>sales?</em></h2>
      <p class="sub">Fill in your restaurant details below. Our team will review and contact you within 2?3 business days.</p>
      <div class="required-note"><i class="fas fa-circle-info"></i> Fields marked with * are required</div>
      <?php if ($success !== ''): ?>
        <div class="alert alert-ok"><i class="fas fa-circle-check"></i> <?= $success ?></div>
        <div class="links" style="margin-top:20px"><a href="foodieph.html"><i class="fas fa-house"></i> Back to Home</a></div>
      <?php else: ?>
      <form method="post" enctype="multipart/form-data">
        <div class="section-label"><i class="fas fa-store"></i> Business Information</div>
        <div class="form-group"><label>Your Business Name *</label><input type="text" name="biz_name" placeholder="Your Business Name *" required value="<?= htmlspecialchars($_POST['biz_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="form-row">
          <div class="form-group"><label>Owner First Name *</label><input type="text" name="owner_first" placeholder="Business Owner First Name *" required value="<?= htmlspecialchars($_POST['owner_first'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
          <div class="form-group"><label>Owner Last Name *</label><input type="text" name="owner_last" placeholder="Business Owner Last Name *" required value="<?= htmlspecialchars($_POST['owner_last'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
        </div>
        <div class="form-group"><label>What describes your business? *</label>
          <select name="category" required>
            <option value="">What describes your business?</option>
            <option value="fast-food" <?= ($_POST['category']??'')==='fast-food'?'selected':'' ?>>Fast Food</option>
            <option value="asian" <?= ($_POST['category']??'')==='asian'?'selected':'' ?>>Asian Cuisine</option>
            <option value="healthy" <?= ($_POST['category']??'')==='healthy'?'selected':'' ?>>Healthy / Salads</option>
            <option value="desserts" <?= ($_POST['category']??'')==='desserts'?'selected':'' ?>>Desserts & Coffee</option>
            <option value="pizza" <?= ($_POST['category']??'')==='pizza'?'selected':'' ?>>Pizza & Pasta</option>
            <option value="bbq" <?= ($_POST['category']??'')==='bbq'?'selected':'' ?>>BBQ / Grill</option>
            <option value="seafood" <?= ($_POST['category']??'')==='seafood'?'selected':'' ?>>Seafood</option>
            <option value="bakery" <?= ($_POST['category']??'')==='bakery'?'selected':'' ?>>Bakery</option>
            <option value="other" <?= ($_POST['category']??'')==='other'?'selected':'' ?>>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Do you have a BIR 2303 form? *</label>
          <div class="radio-group">
            <label class="radio-label"><input type="radio" name="bir_form" value="yes" <?= ($_POST['bir_form']??'')==='yes'?'checked':'' ?>> Yes</label>
            <label class="radio-label"><input type="radio" name="bir_form" value="no" <?= ($_POST['bir_form']??'')==='no'?'checked':'' ?>> No</label>
          </div>
        </div>
        <div class="form-group">
          <label>Do you have an Android device to receive orders? *</label>
          <div class="radio-group">
            <label class="radio-label"><input type="radio" name="has_device" value="yes" <?= ($_POST['has_device']??'')==='yes'?'checked':'' ?>> Yes</label>
            <label class="radio-label"><input type="radio" name="has_device" value="no" <?= ($_POST['has_device']??'')==='no'?'checked':'' ?>> No</label>
          </div>
        </div>
        <div class="section-label"><i class="fas fa-user"></i> Contact Details</div>
        <div class="form-group"><label>Business Email *</label><input type="email" name="email" placeholder="Enter your Business Email *" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="form-group">
          <label>Business Owner Phone Number *</label>
          <div class="phone-row">
            <input type="text" value="+63" readonly style="background:#f5f5f5;color:var(--muted);text-align:center">
            <input type="tel" name="phone" placeholder="9XX XXX XXXX" required value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <div class="section-label"><i class="fas fa-lock"></i> Account Security</div>
        <div class="form-row">
          <div class="form-group"><label>Create Password *</label><input type="password" name="password" placeholder="Min. 6 characters" required></div>
          <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" placeholder="Confirm your password" required></div>
        </div>
        <div class="section-label"><i class="fas fa-location-dot"></i> Location & Operations</div>
        <div class="form-group"><label>Business Address *</label><input type="text" name="address" placeholder="Street / Building / Barangay" required value="<?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="form-row">
          <div class="form-group"><label>City *</label>
            <select name="city" required>
              <option value="">Select City</option>
              <?php foreach(['Manila','Quezon City','Makati','Pasig','Taguig','Mandaluyong','Marikina','Pasay','Paranaque','Las Pinas','Muntinlupa','Caloocan','Malabon','Navotas','Valenzuela','Cebu City','Lapu-Lapu','Mandaue','Other'] as $c): ?>
              <option value="<?= $c ?>" <?= ($_POST['city']??'')===$c?'selected':'' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Delivery Fee (?) *</label><input type="number" name="delivery_fee" placeholder="e.g. 59" min="0" required value="<?= htmlspecialchars($_POST['delivery_fee'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
        </div>
        <div class="form-group"><label>Operating Hours *</label><input type="text" name="operating_hours" placeholder="e.g. Mon-Sun 8:00 AM - 10:00 PM" required value="<?= htmlspecialchars($_POST['operating_hours'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="section-label"><i class="fas fa-file-lines"></i> Legal & Compliance</div>
        <div class="form-row">
          <div class="form-group"><label>BIR TIN *</label><input type="text" name="bir_tin" placeholder="XXX-XXX-XXX-XXX" required value="<?= htmlspecialchars($_POST['bir_tin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
          <div class="form-group"><label>Business Permit No. *</label><input type="text" name="permit_no" placeholder="Permit Number" required value="<?= htmlspecialchars($_POST['permit_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
        </div>
        <div class="form-group">
          <label>Upload Document of Legitimacy (BIR/Permit/ID) *</label>
          <input type="file" name="legitimacy_doc" accept="image/*,application/pdf" required>
        </div>
        <div class="section-label"><i class="fas fa-handshake"></i> Preferences</div>
        <label class="check-row"><input type="checkbox" name="same_phone" checked><span>My Business Phone is the same as my Mobile Number</span></label>
        <label class="check-row"><input type="checkbox" name="updates" checked><span>I'd like to get updates &amp; promotions via email and SMS</span></label>
        <label class="check-row"><input type="checkbox" name="agree" required><span>I agree to the Foodie.PH <a href="#" style="color:var(--brand)">Partner Terms &amp; Conditions</a> and <a href="#" style="color:var(--brand)">Privacy Policy</a> *</span></label>
        <button type="submit" class="submit-btn"><i class="fas fa-paper-plane" style="margin-right:8px"></i>Register My Restaurant</button>
      </form>
      <?php if ($error !== ''): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <div class="links">
        Already have an account? <a href="login.php">Login</a><br>
        Just a customer? <a href="register.php">Create a customer account</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body></html>
