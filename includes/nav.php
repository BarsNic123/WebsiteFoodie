<?php
declare(strict_types=1);

/**
 * Sync navigation with login session.
 *
 * @param string $html   Full page HTML
 * @param string $page   'home' | 'checkout'
 */
function applySessionNav(string $html, string $page = 'home'): string
{
    $user = currentUser();
    $topbarRegister = '<a href="register.php">New to Foodie.PH? Create an account →</a>';

    $html = str_replace('<!--NAV_SESSION-->', buildNavSessionHtml($user, $page), $html);

    if ($user === null) {
        return $html;
    }

    $safeName = htmlspecialchars((string) ($user['name'] ?? 'User'), ENT_QUOTES, 'UTF-8');
    $topbarLoggedIn = '<span>Welcome, <strong>' . $safeName . '</strong></span>';
    $html = str_replace($topbarRegister, $topbarLoggedIn, $html);

    return $html;
}

/**
 * @param array{id:int,name:string,email:string,role:string}|null $user
 */
function buildNavSessionHtml(?array $user, string $page): string
{
    if ($user === null) {
        $login = '<a href="login.php" class="btn-ghost" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;"><i class="fa fa-user" style="margin-right:6px;font-size:12px"></i>Login</a>';
        $register = '<a href="register.php" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Create Account</a>';
        $html = $login . $register;
        // Admin entry when logged out only (leads to login, then admin panel)
        if ($page === 'home') {
            $admin = '<a href="login.php?next=admin.php&admin=1" class="btn-ghost" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;"><i class="fa fa-shield-halved" style="margin-right:6px;font-size:12px"></i>Admin</a>';
            $html = $login . $admin . $register;
        }
        return $html;
    }

    $safeName = htmlspecialchars((string) ($user['name'] ?? 'User'), ENT_QUOTES, 'UTF-8');
    $myOrders = '<a href="my-orders.php" class="btn-ghost" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;"><i class="fa fa-receipt" style="margin-right:6px;font-size:12px"></i>My Orders</a>';
    return '<span class="btn-ghost" style="display:inline-flex;align-items:center;justify-content:center;">Hi, ' . $safeName . '</span>'
        . $myOrders
        . '<a href="logout.php" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Logout</a>';
}
