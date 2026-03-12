<?php
/**
 * POST /logout.php
 * Destroys session and clears remember-me cookie/token.
 */
require_once 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

$pdo = getDBConnection();

startSession();

// Remove remember token for this browser (best-effort)
$raw = $_COOKIE['concrete_remember'] ?? '';
if ($pdo && $raw && str_contains($raw, ':')) {
    [$selector] = explode(':', $raw, 2);
    forgetRememberTokenBySelector($pdo, trim($selector));
}
clearRememberMeCookie();

// Clear session data
$_SESSION = [];

// Delete PHP session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params['path'] ?? '/', $params['domain'] ?? '', (bool)($params['secure'] ?? false), (bool)($params['httponly'] ?? true));
}

@session_destroy();

jsonResponse(true, 'Logged out');

