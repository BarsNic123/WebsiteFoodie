<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function appPath(string $relative): string
{
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = rtrim($base, '/');
    if ($base === '') {
        $base = '/';
    }
    if ($base === '/') {
        return '/' . ltrim($relative, '/');
    }
    return $base . '/' . ltrim($relative, '/');
}

function ensureSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * @return array{id:int,name:string,email:string,role:string}|null
 */
function currentUser(): ?array
{
    ensureSession();
    $user = $_SESSION['auth_user'] ?? null;
    if (!is_array($user) || !isset($user['id'], $user['name'], $user['email'], $user['role'])) {
        return null;
    }

    $id = (int) $user['id'];
    try {
        $stmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            unset($_SESSION['auth_user']);
            return null;
        }
        $_SESSION['auth_user'] = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'role' => (string) $row['role'],
        ];
        return $_SESSION['auth_user'];
    } catch (Throwable $e) {
        return [
            'id' => $id,
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
    }
}

/**
 * User id for orders — NULL for guests. Clears stale session if the user
 * was deleted (e.g. after a database reset) to avoid foreign key errors.
 */
function resolveOrderUserId(): ?int
{
    $user = currentUser();
    if ($user === null) {
        return null;
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    if ($stmt->fetch()) {
        return $user['id'];
    }

    ensureSession();
    unset($_SESSION['auth_user']);
    return null;
}

function loginUser(string $email, string $password): bool
{
    ensureSession();
    $stmt = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([trim($email)]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    if (!password_verify($password, (string) $row['password_hash'])) {
        return false;
    }
    $userId = (int) $row['id'];
    $_SESSION['auth_user'] = [
        'id' => $userId,
        'name' => (string) $row['name'],
        'email' => (string) $row['email'],
        'role' => (string) $row['role'],
    ];

    try {
        $phoneStmt = db()->prepare('SELECT phone FROM users WHERE id = ? LIMIT 1');
        $phoneStmt->execute([$userId]);
        $phoneRow = $phoneStmt->fetch();
        $phone = trim((string) ($phoneRow['phone'] ?? ''));
        if ($phone !== '') {
            $link = db()->prepare(
                'UPDATE orders SET user_id = ? WHERE user_id IS NULL AND contact_number = ?'
            );
            $link->execute([$userId, $phone]);
        }
    } catch (Throwable $e) {
        // Non-fatal: login still succeeds
    }

    return true;
}

function registerUser(string $name, string $email, string $password): int
{
    $name = trim($name);
    $email = trim($email);
    if ($name === '' || $email === '' || $password === '') {
        throw new RuntimeException('All fields are required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid email format.');
    }
    if (strlen($password) < 6) {
        throw new RuntimeException('Password must be at least 6 characters.');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ((int) ($stmt->fetch()['c'] ?? 0) > 0) {
        throw new RuntimeException('Email is already registered.');
    }

    $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $ins->execute([$name, $email, $hash, 'user']);
    return (int) $pdo->lastInsertId();
}

function requireLogin(): void
{
    if (currentUser() !== null) {
        return;
    }
    $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: ' . appPath("login.php?next={$next}"));
    exit;
}

function requireAdmin(): void
{
    $user = currentUser();
    if ($user === null) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '/admin.php');
        header('Location: ' . appPath("login.php?next={$next}"));
        exit;
    }
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo 'Forbidden: admin access required.';
        exit;
    }
}

function logoutUser(): void
{
    ensureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
