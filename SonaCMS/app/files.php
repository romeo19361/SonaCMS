<?php
// /SonaCMS/app/files.php
//
// File manager: lists all uploaded images (with thumbnails) and documents,
// each with a delete button. There is NO usage scanner — deleting a file that
// is still referenced will break wherever it's used, so the delete button
// carries a clear warning. This is deliberate: it keeps the tool simple and
// lets an admin remove a file for any reason (including compliance), even if
// it's currently in use.

require __DIR__ . '/auth.php';
require __DIR__ . '/paths.php';

/**
 * List files in a directory, newest first, skipping protective/placeholder
 * files. Returns [ ['name'=>..., 'size'=>..., 'mtime'=>..., 'url'=>...], ... ]
 */
function listUploadDir(string $dir, string $webPath): array
{
    $out = [];
    if (!is_dir($dir)) {
        return $out;
    }
    $skip = ['.htaccess', '.gitkeep', '.', '..'];
    foreach (scandir($dir) as $entry) {
        if (in_array($entry, $skip, true)) continue;
        $full = $dir . '/' . $entry;
        if (!is_file($full)) continue;
        $out[] = [
            'name'  => $entry,
            'size'  => filesize($full),
            'mtime' => filemtime($full),
            'url'   => siteWebRoot() . $webPath . '/' . rawurlencode($entry),
        ];
    }
    // Newest first
    usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $out;
}

function humanFileSize(int $bytes): string
{
    if ($bytes <= 0) return '0 KB';
    $kb = $bytes / 1024;
    return $kb < 1024 ? round($kb) . ' KB' : round($kb / 1024, 1) . ' MB';
}

$images = listUploadDir(UPLOADS_DIR, '/assets/images/uploads');
$files  = listUploadDir(FILES_DIR, '/assets/files/uploads');
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Files — SonaCMS</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="sona-admin">
<div class="sona-wrap sona-wrap--wide">

    <div class="sona-top-bar">
        <h2>Files</h2>
        <div>
            <a href="admin.php">&larr; Pages</a>
            <a href="authors.php">Authors</a>
            <a href="logout.php">Log out</a>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="sona-message">File deleted.</div>
    <?php elseif (isset($_GET['err'])): ?>
        <div class="sona-error">That file could not be deleted.</div>
    <?php endif; ?>

    <div class="sona-panel">
        <p class="sona-hint" style="margin-top:0;">
            These are the files uploaded through the editor. Deleting a file is
            permanent, and if the file is still used on a page, that image or
            download link will break. Deleting does not remove it from any page
            automatically.
        </p>
    </div>

    <!-- ── Images ────────────────────────────────────────────────────── -->
    <h3 style="margin-top:28px;">Images (<?php echo count($images); ?>)</h3>
    <?php if (empty($images)): ?>
        <p class="sona-hint">No images uploaded yet.</p>
    <?php else: ?>
        <div class="sona-filegrid">
            <?php foreach ($images as $img): ?>
                <div class="sona-filecard">
                    <div class="sona-filecard__thumb">
                        <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="" loading="lazy">
                    </div>
                    <div class="sona-filecard__meta">
                        <span class="sona-filecard__name" title="<?php echo htmlspecialchars($img['name']); ?>"><?php echo htmlspecialchars($img['name']); ?></span>
                        <span class="sona-filecard__size"><?php echo humanFileSize($img['size']); ?></span>
                    </div>
                    <form method="POST" action="file-delete.php"
                          onsubmit="return confirm('Delete this image permanently?\n\nIf it is used on any page, that image will break. This cannot be undone.');">
                        <input type="hidden" name="type" value="image">
                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($img['name']); ?>">
                        <button type="submit" class="sona-filecard__delete">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ── Documents ─────────────────────────────────────────────────── -->
    <h3 style="margin-top:36px;">Documents (<?php echo count($files); ?>)</h3>
    <?php if (empty($files)): ?>
        <p class="sona-hint">No documents uploaded yet.</p>
    <?php else: ?>
        <table class="sona-table">
            <thead>
            <tr><th>File</th><th>Size</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($files as $doc): ?>
                <tr>
                    <td>
                        <a href="<?php echo htmlspecialchars($doc['url']); ?>" target="_blank" rel="noopener">
                            <?php echo htmlspecialchars($doc['name']); ?>
                        </a>
                    </td>
                    <td><?php echo humanFileSize($doc['size']); ?></td>
                    <td class="sona-actions">
                        <form method="POST" action="file-delete.php"
                              onsubmit="return confirm('Delete this document permanently?\n\nIf it is linked on any page, that download link will break. This cannot be undone.');">
                            <input type="hidden" name="type" value="file">
                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($doc['name']); ?>">
                            <button type="submit" class="sona-btn sona-btn--danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php require __DIR__ . '/footer.php'; ?>
</div>
</body>
</html>