<?php
// /SonaCMS/app/activity-log.php
//
// Manager-only view of the activity log: who changed which page, and when.

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/auth.php';        // enforces login; loads users.php + paths.php
require __DIR__ . '/functions.php';   // provides licenseFooterText() used by footer.php
require __DIR__ . '/activity.php';

// Manager-only — editors can't view the audit trail.
requireManager();

$entries = readActivity(200);

/** Human-friendly relative-ish date/time. */
function fmtActivityTime(string $iso): string
{
    $ts = strtotime($iso);
    if ($ts === false) return htmlspecialchars($iso);
    return date('j M Y, g:i a', $ts);
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>Activity | SonaCMS</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="images/favicon.ico" sizes="any">
</head>
<body class="sona-admin">
<div class="sona-wrap sona-wrap--wide">

    <div class="sona-top-bar">
        <div class="sona-top-bar__brand">
            <a href="admin.php" class="sona-brand-link"><img src="images/SonaCMS.svg" alt="SonaCMS" class="sona-brand-logo"></a>
            <h2>Activity</h2>
        </div>
        <div>
            <a href="admin.php">Pages</a>
            <a href="authors.php">Authors</a>
            <a href="files.php">Files</a>
            <a href="users-admin.php">Users</a>
            <a href="logout.php">Log out</a>
        </div>
    </div>

    <p class="sona-help">
        A record of changes made in the CMS &mdash; who did what, and when. Newest first.
    </p>

    <table class="sona-table">
        <thead>
        <tr>
            <th>When</th>
            <th>Who</th>
            <th>Action</th>
            <th>Page</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($entries)): ?>
            <tr><td colspan="4">No activity recorded yet.</td></tr>
        <?php else: ?>
            <?php foreach ($entries as $e): ?>
                <tr>
                    <td><?php echo fmtActivityTime($e['time'] ?? ''); ?></td>
                    <td>
                        <?php echo htmlspecialchars($e['user'] ?? 'unknown'); ?>
                        <?php if (!empty($e['role'])): ?>
                            <span class="sona-role-tag"><?php echo htmlspecialchars($e['role']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $action = $e['action'] ?? '';
                        $cls = $action === 'deleted' ? 'sona-act--del'
                            : ($action === 'created' ? 'sona-act--new' : 'sona-act--upd');
                        ?>
                        <span class="sona-act <?php echo $cls; ?>"><?php echo htmlspecialchars($action); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($e['target'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<?php require __DIR__ . '/footer.php'; ?>

</body>
</html>