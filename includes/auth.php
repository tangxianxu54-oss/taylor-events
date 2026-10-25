<?php
/**
 * 会话与身份认证
 */

function boot_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        unset($_SESSION['user_id']);
        return null;
    }
    $user = $found;
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', false, true);
    }
    session_destroy();
}

function require_login(): void
{
    if (current_user()) {
        return;
    }
    if (is_ajax()) {
        json_response(['ok' => false, 'error' => 'Please sign in to continue.'], 401);
    }
    flash('info', 'Please sign in to continue.');
    $next = $_SERVER['REQUEST_URI'] ?? 'index.php';
    redirect('login.php?next=' . urlencode($next));
}

function require_verified(): void
{
    require_login();
    $user = current_user();
    if ((int) $user['verified'] === 1) {
        return;
    }
    if (is_ajax()) {
        json_response(['ok' => false, 'error' => 'Please verify your campus email first.'], 403);
    }
    redirect('verify.php');
}

/**
 * 只允许站内相对跳转，避免开放重定向
 */
function safe_next(?string $next, string $fallback = 'index.php'): string
{
    $next = trim((string) $next);
    if ($next === '' || str_contains($next, '://') || str_starts_with($next, '//')) {
        return $fallback;
    }
    return $next;
}
