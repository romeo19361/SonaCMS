<?php
// /SonaCMS-V1.1/app/auth.php

session_start();

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