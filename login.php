<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$error = '';
$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'index.php');
$next = trim($next) !== '' ? $next : 'index.php';
if (str_starts_with($next, 'http://') || str_starts_with($next, 'https://')) {
    $next = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string) ($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    try {
        if (loginUser($email, $password)) {
            header('Location: ' . $next);
            exit;
        }
        $error = 'Invalid email or password.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Foodie.PH</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand: #e8282b;
      --brand-dark: #b81e20;
      --gold: #f5a623;
      --bg: #f7f5f2;
      --surface: #ffffff;
      --text: #1a1a1a;
      --muted: #757575;
      --border: #e8e6e2;
      --shadow-lg: 0 10px 34px rgba(0,0,0,0.12);
      --font: 'Plus Jakarta Sans', sans-serif;
      --font-display: 'Fraunces', serif;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: var(--font); background: var(--bg); color: var(--text); }
    .topbar { background: var(--brand); color: #fff; text-align: center; font-size: 12px; padding: 8px 16px; }
    .topbar a { color: #ffe8e8; }
    .auth-shell {
      min-height: calc(100vh - 36px);
      display: grid;
      place-items: center;
      padding: 30px 16px;
      background: linear-gradient(180deg, #fff 0%, #fff6f6 100%);
    }
    .box {
      width: 100%;
      max-width: 440px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 26px;
      box-shadow: var(--shadow-lg);
    }
    .brand { text-decoration: none; color: var(--brand); font-family: var(--font-display); font-size: 32px; display: inline-block; margin-bottom: 6px; }
    .brand span { color: var(--text); font-style: italic; }
    h1 { margin: 0 0 8px; font-family: var(--font-display); font-size: 32px; line-height: 1.1; }
    p { color: var(--muted); margin: 0 0 16px; font-size: 14px; }
    label { display: block; margin: 12px 0 6px; font-size: 13px; font-weight: 700; }
    input {
      width: 100%;
      padding: 11px 12px;
      border-radius: 10px;
      border: 1px solid #dcd7d0;
      background: #fff;
      color: var(--text);
      font-family: var(--font);
    }
    input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px rgba(232,40,43,0.1); }
    button {
      width: 100%;
      margin-top: 18px;
      padding: 12px;
      border: 0;
      border-radius: 999px;
      background: var(--brand);
      color: #fff;
      font-weight: 800;
      cursor: pointer;
      font-family: var(--font);
    }
    button:hover { background: var(--brand-dark); }
    .error { margin-top: 12px; padding: 10px; border-radius: 10px; background: #fde8e8; color: #a61b1d; border: 1px solid #f8c6c6; }
    .links { margin-top: 14px; font-size: 13px; line-height: 1.6; }
    .links a { color: var(--brand); font-weight: 700; text-decoration: none; }
    .links a:hover { color: var(--brand-dark); text-decoration: underline; }
    .chip { display: inline-block; background: #fff1f1; color: var(--brand); border: 1px solid #ffd6d6; font-size: 11px; font-weight: 700; border-radius: 999px; padding: 4px 10px; margin-bottom: 12px; }
    .accent { color: var(--gold); font-style: italic; }
  </style>
</head>
<body>
  <div class="topbar">Fast. Fresh. Delivered. &nbsp;|&nbsp; <a href="index.php">Back to Foodie.PH</a></div>
  <main class="auth-shell">
    <div class="box">
      <a href="index.php" class="brand">Foodie<span>.PH</span></a>
      <div class="chip">Secure Account Access</div>
      <h1>Welcome <span class="accent">Back</span></h1>
      <p>Sign in as customer or admin to continue.</p>
      <form method="post">
        <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Sign In</button>
      </form>
      <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <div class="links">
        No account? <a href="register.php">Create one</a><br>
        Back to site: <a href="index.php">Home</a>
      </div>
    </div>
  </main>
</body>
</html>
