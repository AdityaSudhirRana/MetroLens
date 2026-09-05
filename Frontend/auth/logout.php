<?php
/**
 * AI Package Inspector - Logout Handler
 */
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    log_audit($_SESSION['user_id'], 'LOGOUT', 'User logged out successfully');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header('Location: login.php');
exit;
?>
