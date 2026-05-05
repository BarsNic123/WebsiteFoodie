<?php
/**
 * Entry point for XAMPP: http://localhost/WebsiteFoodie/
 * Serves the same markup as foodieph.html.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$html = file_get_contents(__DIR__ . '/foodieph.html');
if ($html === false) {
    http_response_code(500);
    echo 'Could not load homepage.';
    exit;
}

$user = currentUser();
$adminLink = '';
if ($user !== null && ($user['role'] ?? '') === 'admin') {
    $adminLink = '<a href="admin.php" class="btn-ghost" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Admin</a>';
}

$loggedOutLogin = '<a href="login.php" class="btn-ghost" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;"><i class="fa fa-user" style="margin-right:6px;font-size:12px"></i>Login</a>';
$loggedOutRegister = '<a href="register.php" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Create Account</a>';
$staticAdmin = '<a href="admin.php" class="btn-ghost" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Admin</a>';

if ($user === null) {
    $html = str_replace($staticAdmin, '', $html);
} else {
    $safeName = htmlspecialchars((string) ($user['name'] ?? 'User'), ENT_QUOTES, 'UTF-8');
    $accountBtn = '<span class="btn-ghost" style="display:inline-flex;align-items:center;justify-content:center;">Hi, ' . $safeName . '</span>';
    $logoutBtn = '<a href="logout.php" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Logout</a>';
    $html = str_replace($loggedOutLogin, $accountBtn, $html);
    $html = str_replace($loggedOutRegister, $logoutBtn, $html);
    $html = str_replace($staticAdmin, $adminLink, $html);
}

echo $html;
