<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first    = trim((string)($_POST['first_name']  ?? ''));
    $last     = trim((string)($_POST['last_name']   ?? ''));
    $email    = trim((string)($_POST['email']       ?? ''));
    $password = trim((string)($_POST['password']    ?? ''));
    $confirm  = trim((string)($_POST['confirm']     ?? ''));
    $phone    = trim((string)($_POST['phone']       ?? ''));
    $city     = trim((string)($_POST['city']        ?? ''));
    $barangay = trim((string)($_POST['barangay']    ?? ''));
    $street   = trim((string)($_POST['street']      ?? ''));
    $postal   = trim((string)($_POST['postal']      ?? ''));
    $agree    = isset($_POST['agree']);
    $name     = trim($first . ' ' . $last);
    if ($first === '' || $last === '')          { $error = 'Please enter your first and last name.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Please enter a valid email address.'; }
    elseif (strlen($password) < 8)             { $error = 'Password must be at least 8 characters.'; }
    elseif ($password !== $confirm)            { $error = 'Passwords do not match.'; }
    elseif (!$agree)                           { $error = 'You must agree to the Terms & Conditions and Privacy Policy.'; }
    else {
        try {
            $userId = registerUser($name, $email, $password);
            $config = require __DIR__ . '/config.php';
            if ($config['use_database']) {
                db()->prepare("UPDATE users SET first_name=?,last_name=?,phone=?,city=?,state=?,street_address=?,postal_code=?,email_notifications=1 WHERE id=?")
                   ->execute([$first,$last,$phone,$city,$barangay,$street,$postal,$userId]);
            }
            $success = 'Account created successfully!';
        } catch (Throwable $e) { $error = $e->getMessage(); }
    }
}
function v(string $k): string { return htmlspecialchars($_POST[$k] ?? '', ENT_QUOTES, 'UTF-8'); }

// PH cities with barangay data
$cities = ['Manila','Quezon City','Makati','Pasig','Taguig','Mandaluyong','Marikina','Pasay','Paranaque','Las Pinas','Muntinlupa','Caloocan','Malabon','Navotas','Valenzuela','Cebu City','Lapu-Lapu','Mandaue'];
$barangays = [
    'Manila'       => ['Binondo','Ermita','Intramuros','Malate','Paco','Pandacan','Port Area','Quiapo','Sampaloc','San Miguel','San Nicolas','Santa Ana','Santa Cruz','Santa Mesa','Tondo'],
    'Quezon City'  => ['Bagong Silangan','Batasan Hills','Commonwealth','Cubao','Diliman','Fairview','Kamuning','Loyola Heights','Novaliches','Project 4','Project 6','Project 8','Tandang Sora','UP Campus'],
    'Makati'       => ['Ayala Alabang','Bel-Air','Forbes Park','Greenbelt','Legazpi Village','Poblacion','Rockwell','Salcedo Village','San Lorenzo','Urdaneta'],
    'Pasig'        => ['Bagong Ilog','Bagong Katipunan','Bambang','Buting','Caniogan','Kapitolyo','Manggahan','Ortigas Center','Pinagbuhatan','Rosario','San Antonio','San Joaquin','San Nicolas','Santa Lucia','Santolan'],
    'Taguig'       => ['BGC','Bagumbayan','Bambang','Calzada','Central Bicutan','Central Signal Village','Fort Bonifacio','Hagonoy','Ibayo-Tipas','Ligid-Tipas','Lower Bicutan','Maharlika Village','Napindan','New Lower Bicutan','North Daang Hari','Palingon','Pinagsama','San Miguel','Santa Ana','South Daang Hari','Tanyag','Tuktukan','Upper Bicutan','Ususan','Wawa','Western Bicutan'],
    'Mandaluyong'  => ['Addition Hills','Bagong Silang','Barangka Drive','Barangka Ibaba','Barangka Ilaya','Barangka Itaas','Buayang Bato','Burol','Daang Bakal','Hagdang Bato Itaas','Hagdang Bato Libis','Harapin Ang Bukas','Highway Hills','Hulo','Mabini-J. Rizal','Malamig','Mauway','Namayan','New Za?iga','Old Za?iga','Pag-Asa','Plainview','Pleasant Hills','Poblacion','San Joaquin','Vergara','Wack-Wack Greenhills'],
    'Cebu City'    => ['Adlaon','Agsungot','Apas','Babag','Bacayan','Banilad','Basak Pardo','Basak San Nicolas','Binaliw','Bonbon','Budlaan','Busay','Calamba','Cambinocot','Capitol Site','Carreta','Central','Cogon Pardo','Cogon Ramos','Day-as','Duljo','Ermita','Guadalupe','Guba','Hippodromo','Inayawan','Kalubihan','Kalunasan','Kamagayan','Kasambagan','Kinasang-an','Labangon','Lahug','Lorega','Lusaran','Luz','Mabini','Mabolo','Malubog','Mambaling','Pahina Central','Pahina San Nicolas','Pamutan','Pardo','Pari-an','Paril','Pasil','Pit-os','Poblacion Pardo','Pulangbato','Pung-ol-Sibugay','Punta Princesa','Quiot Pardo','Sambag I','Sambag II','San Antonio','San Jose','San Nicolas Central','San Roque','Santa Cruz','Santo Nino','Sapangdaku','Sawang Calero','Sinsin','Sirao','Suba','Sudlon I','Sudlon II','T. Padilla','Tabunan','Tagbao','Talamban','Taptap','Tejero','Tinago','Tisa','To-ong Pardo','Toong','Zapatera'],
    'default'      => ['Barangay 1','Barangay 2','Barangay 3','Barangay 4','Barangay 5','Barangay 6','Barangay 7','Barangay 8','Barangay 9','Barangay 10'],
];
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account - Foodie.PH</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--brand:#e8282b;--brand-dark:#b81e20;--bg:#f5f5f7;--surface:#fff;--text:#1a1a1a;--muted:#9ca3af;--border:#e5e7eb;--shadow:0 4px 24px rgba(0,0,0,.08);--font:'Plus Jakarta Sans',sans-serif;--font-display:'Fraunces',serif}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh}
a{text-decoration:none}
/* topbar */
.topbar{background:var(--brand);color:#fff;text-align:center;font-size:12px;padding:7px 20px}
.topbar a{color:#ffe4e4}
/* nav */
nav{background:#fff;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100}
.nav-inner{max-width:1100px;margin:0 auto;padding:0 24px;height:62px;display:flex;align-items:center;gap:28px}
.logo{font-family:var(--font-display);font-size:22px;color:var(--brand);font-weight:700}
.logo span{color:var(--text);font-style:italic}
.nav-links{display:flex;gap:24px;flex:1}
.nav-links a{font-size:13px;font-weight:600;color:#6b7280}
.nav-links a:hover{color:var(--brand)}
.nav-actions{margin-left:auto;display:flex;align-items:center;gap:12px}
.btn-login{font-size:13px;font-weight:700;color:var(--text);padding:8px 16px;border-radius:999px;border:1.5px solid var(--border);background:#fff;cursor:pointer;font-family:var(--font)}
.btn-login:hover{border-color:var(--brand);color:var(--brand)}
.btn-register{font-size:13px;font-weight:800;color:#fff;padding:9px 22px;border-radius:999px;background:var(--brand);border:none;cursor:pointer;font-family:var(--font)}
.btn-register:hover{background:var(--brand-dark)}
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
.submit-btn:hover{background:var(--brand-dark)}
/* alerts */
.alert{margin-top:16px;padding:12px 16px;border-radius:10px;font-size:13px;display:flex;align-items:flex-start;gap:8px}
.alert-error{background:#fde8e8;color:#a61b1d;border:1px solid #f8c6c6}
.alert-ok{background:#e8f8ee;color:#146c2f;border:1px solid #c3ebd0}
/* bottom link */
.bottom-link{text-align:center;margin-top:20px;font-size:13px;color:var(--muted)}
.bottom-link a{color:var(--brand);font-weight:700}
/* tab bar */
.tab-bar{display:grid;grid-template-columns:1fr 1fr;border:2px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:28px}
.tab-btn{padding:11px;font-family:var(--font);font-size:14px;font-weight:700;border:none;cursor:pointer;background:#fff;color:var(--muted);transition:all .2s}
.tab-btn.active{background:var(--brand);color:#fff}
@media(max-width:520px){.form-row{grid-template-columns:1fr}.card{padding:28px 20px}}
</style>
</head>
<body>
<div class="topbar">&#128222; Globe: <strong>09177135477</strong> &nbsp;|&nbsp; Fast. Fresh. Nationwide Delivery &nbsp;|&nbsp; <a href="foodieph.html">Back to Foodie.PH</a></div>
<nav>
  <div class="nav-inner">
    <a href="foodieph.html" class="logo">Foodie<span>.PH</span></a>
    <div class="nav-links">
      <a href="#">Testimonials</a>
      <a href="#">FAQ</a>
    </div>
    <div class="nav-actions">
      <button class="btn-login" onclick="window.location='login.php'">Log in</button>
      <button class="btn-register">Register now</button>
    </div>
  </div>
</nav>
<div class="page-shell">
  <div class="card">
    <div class="tab-bar">
      <button class="tab-btn" onclick="window.location='login.php'">Log In</button>
      <button class="tab-btn active">Create Account</button>
    </div>
    <h1 class="card-title">Create your account</h1>
    <p class="card-sub">Fill in the details below and start ordering from your favorite restaurants.</p>

    <?php if ($success !== ''): ?>
      <div class="alert alert-ok"><i class="fas fa-circle-check"></i><span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?> <a href="login.php">Log in now &rarr;</a></span></div>
    <?php else: ?>
    <form method="post" id="reg-form" novalidate>

      <!-- Personal Info -->
      <div class="sec-label"><i class="fas fa-user"></i> Personal Information</div>
      <div class="form-row">
        <div class="form-group">
          <label>Owner first name <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-user"></i><input type="text" name="first_name" placeholder="Juan" required value="<?= v('first_name') ?>"></div>
        </div>
        <div class="form-group">
          <label>Owner last name <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-user"></i><input type="text" name="last_name" placeholder="Dela Cruz" required value="<?= v('last_name') ?>"></div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email address <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-envelope"></i><input type="email" name="email" placeholder="you@email.com" required value="<?= v('email') ?>"></div>
        </div>
        <div class="form-group">
          <label>Phone number <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-phone"></i><input type="tel" name="phone" placeholder="+63 917 123 4567" value="<?= v('phone') ?>"></div>
        </div>
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
    <?php endif; ?>

    <div class="bottom-link">Already have an account? <a href="login.php">Log in here</a></div>
  </div>
</div>

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
</body></html>
