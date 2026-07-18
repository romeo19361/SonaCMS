<?php
// /SonaCMS/app/logout.php

session_start();

// Clear all session data
$_SESSION = [];

// Remove the session cookie itself, if one is set
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Redirect relative to this script's location, so it works regardless
// of the install directory (same approach as auth.php).
$loginPath = dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/index.php';
header('Location: ' . $loginPath);
exit;