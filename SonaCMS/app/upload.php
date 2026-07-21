<?php
// /SonaCMS/app/upload.php
//
// Handles image uploads from the Editor.js Image tool ("byFile" endpoint).
// Expects a multipart/form-data POST with the file under the field name
// Editor.js uses by default: "image".
//
// Response format must match what @editorjs/image expects:
//   { "success": 1, "file": { "url": "..." } }
//   { "success": 0 } on failure

error_reporting(E_ALL);
ini_set('display_errors', 0); // never leak PHP warnings into a JSON response

require __DIR__ . '/auth.php';   // enforces login — only logged-in admins may upload
require __DIR__ . '/paths.php';

header('Content-Type: application/json');

function failUpload(string $reason): void
{
    http_response_code(400);
    echo json_encode(['success' => 0, 'message' => $reason]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    failUpload('No file received.');
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    failUpload('Upload error (code ' . $file['error'] . ').');
}

// Restrict to common, safe image types — both by extension and real MIME
// sniffing (extension alone can be spoofed).
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$originalName = $file['name'];
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions, true)) {
    failUpload('File type not allowed.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detectedMime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($detectedMime, $allowedMimeTypes, true)) {
    failUpload('File content does not match an allowed image type.');
}

// 5MB cap — adjust as needed
$maxBytes = 5 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    failUpload('File is too large (max 5MB).');
}

// Ensure the uploads directory exists
if (!is_dir(UPLOADS_DIR)) {
    if (!mkdir(UPLOADS_DIR, 0755, true)) {
        failUpload('Could not create uploads directory. Check folder permissions.');
    }
}

// Content-addressed filename: name the file after a hash of its contents.
// This deduplicates automatically — uploading the same image twice produces
// the same hash, so the second upload reuses the first file instead of writing
// a duplicate. No database or index needed; the filename IS the dedup key.
$hash = hash_file('sha256', $file['tmp_name']);
$safeName = $hash . '.' . $extension;
$destination = UPLOADS_DIR . '/' . $safeName;

// Only write if we don't already have this exact file. If it exists, we simply
// reuse it (the move is skipped) and return its URL.
if (!file_exists($destination)) {
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        failUpload('Could not save the uploaded file.');
    }
}

// Build the public URL back to the file, accounting for whatever
// directory SonaCMS is installed into.
$url = siteWebRoot() . '/assets/images/uploads/' . $safeName;

echo json_encode([
    'success' => 1,
    'file' => [
        'url' => $url,
    ],
]);