<?php
// /SonaCMS/app/auth.php

session_start();

// User/role helpers available on every admin page (currentRole(), isManager(),
// requireManager(), etc). paths.php provides CONTENT_DIR used by users.php.
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/users.php';

// Apply the site's configured timezone so all admin-side dates (activity log,
// edit markers, publish dates) use local time, not the server default. Done
// inline (not via functions.php) so auth.php stays lightweight and doesn't
// double-include functions.php, which admin pages require themselves.
$__cfg = sonaConfig();
$__tz  = $__cfg['timezone'] ?? 'UTC';
if (!is_string($__tz) || !in_array($__tz, timezone_identifiers_list(), true)) {
    $__tz = 'UTC';
}
date_default_timezone_set($__tz);

// Generate a CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect relative to the script's own location, not a hardcoded path,
    // so this works regardless of install directory.
    $loginPath = dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/index.php';
    header('Location: ' . $loginPath);
    exit;
}