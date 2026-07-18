<?php
// /SonaCMS/app/admin.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/auth.php';   // enforces login, starts session, provides csrf_token
require __DIR__ . '/paths.php';
require __DIR__ . '/functions.php';

$message = null;

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Security check failed. Please try again.";
    } else {
        $filename = $_POST['filename'] ?? '';
        if ($filename !== '' && deletePage($filename)) {
            $message = "Page deleted.";
        } else {
            $message = "Could not delete that page.";
        }
    }
}

$pages = getPageTree();

/**
 * Recursively render table rows for a page tree, indenting by depth
 * so nested pages are visually distinguishable from top-level ones.
 */
function renderPageRows(array $pages, int $depth = 0): void
{
    foreach ($pages as $page) {
        $indent = str_repeat('&mdash; ', $depth);
        ?>
        <tr>
            <td><?php echo $indent . htmlspecialchars($page['title'] ?? '(untitled)'); ?></td>
            <td><?php echo htmlspecialchars($page['slug'] ?? ''); ?></td>
            <td>
                <?php if (($page['status'] ?? '') === 'draft'): ?>
                    <span class="sona-status-draft">Draft</span>
                <?php else: ?>
                    Published
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($page['filename']); ?></td>
            <td class="sona-actions">
                <a class="sona-btn" href="editor.php?file=<?php echo urlencode($page['filename']); ?>">Edit</a>
                <form method="POST" onsubmit="return confirm('Delete this page? This cannot be undone.');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($page['filename']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <button type="submit" class="sona-btn sona-btn--danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php
        if (!empty($page['children'])) {
            renderPageRows($page['children'], $depth + 1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>Admin | CMS</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="sona-admin">
<div class="sona-wrap sona-wrap--wide">

    <div class="sona-top-bar">
        <h2>Pages</h2>
        <div>
            <a class="sona-btn" href="editor.php">+ New Page</a>
            <a href="authors.php">Authors</a>
            <a href="logout.php">Log out</a>
        </div>
    </div>

    <?php if ($message): ?>
        <p class="sona-message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <table class="sona-table">
        <thead>
        <tr>
            <th>Title</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Filename</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($pages)): ?>
            <tr><td colspan="5">No pages yet. Click "New Page" to create one.</td></tr>
        <?php else: ?>
            <?php renderPageRows($pages); ?>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<?php require __DIR__ . '/footer.php'; ?>

</body>
</html>