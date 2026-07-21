<?php
// /SonaCMS/app/upload-file.php
//
// Handles DOCUMENT uploads for the Download block (PDFs and common office
// files). Kept separate from upload.php (which stays image-only) so the image
// path's tight restrictions aren't loosened. Documents are a larger security
// surface, so this validates by extension AND sniffed MIME, caps size, stores
// under a non-executable uploads folder, and never trusts the original name.
//
// Response:
//   { "success": 1, "file": { "url": "...", "name": "original.pdf", "size": 12345 } }
//   { "success": 0, "message": "..." } on failure

error_reporting(E_ALL);
ini_set('display_errors', 0);

require __DIR__ . '/auth.php';   // only logged-in admins may upload
require __DIR__ . '/paths.php';

header('Content-Type: application/json');

function failFileUpload(string $reason): void
{
    http_response_code(400);
    echo json_encode(['success' => 0, 'message' => $reason]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    failFileUpload('No file received.');
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    failFileUpload('Upload error (code ' . $file['error'] . ').');
}

// Allowed document types — extension plus the MIME(s) we accept for each.
// PDFs, Word, Excel, PowerPoint (old + OOXML), and zip archives.
$allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip'];
$allowedMimeTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/zip',
    'application/octet-stream', // some browsers send this for office/zip files
    'application/x-zip-compressed',
];

$originalName = $file['name'];
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions, true)) {
    failFileUpload('File type not allowed. Accepted: PDF, Word, Excel, PowerPoint, ZIP.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detectedMime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($detectedMime, $allowedMimeTypes, true)) {
    failFileUpload('File content does not match an allowed document type.');
}

// 20MB cap for documents — larger than images, adjust as needed
$maxBytes = 20 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    failFileUpload('File is too large (max 20MB).');
}

// Documents live in their own top-level files area, separate from images.
// Structure: /assets/files/uploads/  (logical for anyone reading the tree —
// a PDF isn't an image, so it shouldn't sit under the images folder).
$docsDir = CONTENT_DIR . '/../files/uploads';
if (!is_dir($docsDir)) {
    if (!mkdir($docsDir, 0755, true)) {
        failFileUpload('Could not create the files directory. Check folder permissions.');
    }
}

// Content-addressed filename: prefix with a hash of the file's contents so
// identical uploads deduplicate (same bytes -> same hash -> reuse existing
// file). A readable slug of the original name is appended for human-friendly
// on-disk names. The download block stores the ORIGINAL name separately (see
// the JSON response below), so the visitor always sees the friendly filename
// regardless of what the stored file is called.
$baseName = pathinfo($originalName, PATHINFO_FILENAME);
$slug = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $baseName);
$slug = trim($slug, '-');
if ($slug === '') {
    $slug = 'file';
}
$hash = hash_file('sha256', $file['tmp_name']);
$safeName = substr($hash, 0, 32) . '-' . $slug . '.' . $extension;
$destination = $docsDir . '/' . $safeName;

// Only write if this exact file isn't already stored; otherwise reuse it.
if (!file_exists($destination)) {
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        failFileUpload('Could not save the uploaded file.');
    }
}

$url = siteWebRoot() . '/assets/files/uploads/' . $safeName;

echo json_encode([
    'success' => 1,
    'file' => [
        'url'  => $url,
        'name' => $originalName, // shown to visitors as the download label
        'size' => $file['size'],
    ],
]);