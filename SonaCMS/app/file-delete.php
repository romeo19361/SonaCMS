<?php
// /SonaCMS/app/file-delete.php
//
// Deletes a single uploaded file (image or document) on behalf of the file
// manager. Security is the whole job here:
//   - admin auth required
//   - POST only
//   - the target MUST resolve to a real path INSIDE one of the two upload
//     folders. Anything else (path traversal, absolute paths, files outside
//     uploads) is refused. This makes it impossible to delete core files,
//     config, or content via this endpoint.

require __DIR__ . '/auth.php';   // redirects to login if not authenticated
require __DIR__ . '/paths.php';

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: files.php');
    exit;
}

$type = $_POST['type'] ?? '';      // 'image' or 'file'
$name = $_POST['name'] ?? '';      // basename only

// Resolve the folder from the type — never from user-supplied path
$baseDir = ($type === 'image') ? UPLOADS_DIR : (($type === 'file') ? FILES_DIR : '');

if ($baseDir === '' || $name === '') {
    header('Location: files.php?err=1');
    exit;
}

// Force to a basename — strips any directory components / traversal attempts
$name = basename($name);

$target = $baseDir . '/' . $name;

// Final safety: the resolved real path must sit inside the intended folder.
$realTarget = realpath($target);
$realBase   = realpath($baseDir);

if ($realTarget === false || $realBase === false
    || strpos($realTarget, $realBase . DIRECTORY_SEPARATOR) !== 0) {
    // Target doesn't exist, or escaped the uploads folder — refuse.
    header('Location: files.php?err=1');
    exit;
}

// Don't delete protective .htaccess or placeholder files
$protected = ['.htaccess', '.gitkeep'];
if (in_array($name, $protected, true)) {
    header('Location: files.php?err=1');
    exit;
}

if (is_file($realTarget)) {
    @unlink($realTarget);
}

header('Location: files.php?deleted=1');
exit;