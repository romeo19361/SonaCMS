<?php
// /public_html/SonaCMS-V1.1/index.php

// Error handling as per constraints
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Absolute pathing for configuration
$config = require __DIR__ . '/config.php';

session_start();

// If already logged in, redirect to admin
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: app/admin.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Verify credentials
    if ($email === $config['admin_email'] && password_verify($password, $config['admin_password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $email;
        header('Location: app/admin.php');
        exit;
    } else {
        $error = "Invalid email or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>Login | CMS</title>
    <link rel="stylesheet" href="app/css/styles.css">
</head>
<body class="sona-admin">
<div class="sona-wrap sona-wrap--narrow">

    <h2 class="sona-login-heading">Admin Access</h2>

    <?php if ($error): ?>
        <p class="sona-error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <input class="sona-input" type="email" name="email" placeholder="Email Address" autocomplete="username" required autofocus>
        <input class="sona-input" type="password" name="password" placeholder="Password" autocomplete="current-password" required>
        <button class="sona-btn sona-btn--block" type="submit">Login</button>
    </form>

</div>
</body>
</html>