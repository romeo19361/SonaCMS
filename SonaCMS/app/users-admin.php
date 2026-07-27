<?php
// /SonaCMS/app/users-admin.php
//
// Manager-only screen to manage EDITOR accounts (stored in users.json).
// The site MANAGER account itself lives in config.php and is NOT editable here
// (it's the break-glass super-user). Editors added here can log in and edit
// pages, but cannot reach this screen or the activity log.

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/auth.php';        // enforces login; loads users.php + paths.php
require __DIR__ . '/functions.php';

// Manager-only. Editors are bounced back to the dashboard.
requireManager();

$message = null;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check (same pattern as the other admin screens)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security check failed. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $email = trim($_POST['email'] ?? '');
            $name  = trim($_POST['name'] ?? '');
            $pass  = $_POST['password'] ?? '';
            [$ok, $why] = saveEditor($email, $name, $pass);
            if ($ok) {
                $message = "Editor saved.";
            } else {
                $error = $why;
            }
        } elseif ($action === 'reset') {
            // Reset an existing editor's password (name kept)
            $email = trim($_POST['email'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $existing = findEditorByEmail($email);
            if (!$existing) {
                $error = "No such editor.";
            } else {
                [$ok, $why] = saveEditor($email, $existing['name'] ?? $email, $pass);
                $message = $ok ? "Password updated." : null;
                $error   = $ok ? null : $why;
            }
        } elseif ($action === 'delete') {
            $email = trim($_POST['email'] ?? '');
            if (removeEditor($email)) {
                $message = "Editor removed.";
            } else {
                $error = "Could not remove that editor.";
            }
        }
    }
}

$editors = loadEditors();
$csrf = htmlspecialchars($_SESSION['csrf_token']);
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>Users | SonaCMS</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="images/favicon.ico" sizes="any">
</head>
<body class="sona-admin">
<div class="sona-wrap sona-wrap--wide">

    <div class="sona-top-bar">
        <div class="sona-top-bar__brand">
            <a href="admin.php" class="sona-brand-link"><img src="images/SonaCMS.svg" alt="SonaCMS" class="sona-brand-logo"></a>
            <h2>Users</h2>
        </div>
        <div>
            <a href="admin.php">Pages</a>
            <a href="authors.php">Authors</a>
            <a href="files.php">Files</a>
            <a href="logout.php">Log out</a>
        </div>
    </div>

    <?php if ($message): ?>
        <p class="sona-message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="sona-error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <p class="sona-help">
        The <strong>site manager</strong> account is set in <code>config.php</code>
        and isn't listed here. Editors added below can log in and edit pages, but
        cannot manage users or view the activity log.
    </p>

    <h3>Add an editor</h3>
    <form method="POST" class="sona-form-inline">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input class="sona-input" type="text"  name="name"     placeholder="Full name" required>
        <input class="sona-input" type="email" name="email"    placeholder="Email address" autocomplete="off" required>
        <input class="sona-input" type="password" name="password" placeholder="Password (min 8 chars)" autocomplete="new-password" required>
        <button type="submit" class="sona-btn">Add editor</button>
    </form>

    <h3>Editors</h3>
    <table class="sona-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Added</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($editors)): ?>
            <tr><td colspan="4">No editors yet. Add one above.</td></tr>
        <?php else: ?>
            <?php foreach ($editors as $ed): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ed['name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($ed['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(!empty($ed['created']) ? date('j M Y', strtotime($ed['created'])) : ''); ?></td>
                    <td class="sona-actions sona-users-actions">
                        <!-- Reset password (inline) -->
                        <form method="POST" class="sona-inline-reset" onsubmit="return confirm('Set a new password for this editor?');">
                            <input type="hidden" name="action" value="reset">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($ed['email']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <input class="sona-input sona-input--sm" type="password" name="password" placeholder="New password" autocomplete="new-password" required>
                            <button type="submit" class="sona-btn">Reset</button>
                        </form>
                        <!-- Remove -->
                        <form method="POST" onsubmit="return confirm('Remove this editor? They will no longer be able to log in.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($ed['email']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <button type="submit" class="sona-btn sona-btn--danger">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<?php require __DIR__ . '/footer.php'; ?>

</body>
</html>