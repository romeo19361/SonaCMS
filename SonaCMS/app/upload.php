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

// Accept larger originals (phone/camera photos are often 8-12MB) — they get
// resized and recompressed below, so the *stored* file will be much smaller.
$maxBytes = 25 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    failUpload('File is too large (max 25MB).');
}

// Ensure the uploads directory exists
if (!is_dir(UPLOADS_DIR)) {
    if (!mkdir(UPLOADS_DIR, 0755, true)) {
        failUpload('Could not create uploads directory. Check folder permissions.');
    }
}

/**
 * Resize an image down so its longest edge is at most $maxEdge pixels, and
 * re-encode it. Writes the result to $destPath. Returns true on success,
 * false if it declines/fails (caller then stores the original untouched).
 *
 * IMPORTANT: if the image is ALREADY within $maxEdge on both dimensions, this
 * returns false on purpose — meaning "no processing needed, keep the original
 * as-is." That avoids re-compressing an already-compressed image (e.g. one
 * imported from an old site), which would cause generational quality loss
 * (artifacts stacking on artifacts) for no benefit. We only ever re-encode
 * images that genuinely need shrinking.
 *
 * Why resize at all: uploads straight off a phone or camera can be 4000px+ and
 * many MB. Serving those full-size and letting the browser scale them down
 * wastes bandwidth and slows pages badly — especially galleries. Capping the
 * longest edge keeps images sharp on screen while cutting file size hugely.
 *
 * Requires the GD extension. If GD isn't available, returns false and the
 * caller stores the original unchanged.
 */
function resizeImage(string $srcPath, string $destPath, string $ext, int $maxEdge = 1800, int $jpegQuality = 82): bool
{
    if (!function_exists('imagecreatefromjpeg')) {
        return false; // GD not available — store original as-is
    }

    $info = @getimagesize($srcPath);
    if ($info === false) {
        return false;
    }
    [$width, $height] = $info;

    // Already web-sized? Leave it completely untouched (no re-compression).
    // This protects already-optimised / previously-compressed images from
    // generational quality loss.
    if ($width <= $maxEdge && $height <= $maxEdge) {
        return false;
    }

    // Load source into a GD image
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $src = @imagecreatefromjpeg($srcPath);
            break;
        case 'png':
            $src = @imagecreatefrompng($srcPath);
            break;
        case 'gif':
            $src = @imagecreatefromgif($srcPath);
            break;
        case 'webp':
            $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false;
            break;
        default:
            return false;
    }
    if (!$src) {
        return false;
    }

    // Scale DOWN to fit the longest edge (never up)
    $scale = $maxEdge / max($width, $height);
    $newW = max(1, (int) round($width * $scale));
    $newH = max(1, (int) round($height * $scale));

    $dst = imagecreatetruecolor($newW, $newH);

    // Preserve transparency for PNG and WebP
    if ($ext === 'png' || $ext === 'webp') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

    // Encode to the destination
    $ok = false;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $ok = imagejpeg($dst, $destPath, $jpegQuality);
            break;
        case 'png':
            $ok = imagepng($dst, $destPath, 6); // 6 = balanced compression
            break;
        case 'gif':
            $ok = imagegif($dst, $destPath);
            break;
        case 'webp':
            $ok = function_exists('imagewebp') ? imagewebp($dst, $destPath, $jpegQuality) : false;
            break;
    }

    imagedestroy($src);
    imagedestroy($dst);
    return $ok;
}

// Resize the upload to a web-friendly size first (in a temp file), THEN hash
// the result for dedup. Hashing the processed image means two uploads of the
// same source dedup to the same stored file. If GD isn't available or resizing
// fails, we fall back to the untouched original.
$processedPath = $file['tmp_name'];
$tmpResized = tempnam(sys_get_temp_dir(), 'sona_img_');
if ($tmpResized !== false && resizeImage($file['tmp_name'], $tmpResized, $extension)) {
    $processedPath = $tmpResized;
} else {
    // Resizing unavailable/failed — clean up the temp file if it was created
    if ($tmpResized !== false && file_exists($tmpResized)) {
        @unlink($tmpResized);
    }
    $tmpResized = false;
}

// Content-addressed filename: name the file after a hash of its (processed)
// contents. This deduplicates automatically — the same image resized the same
// way produces the same hash, so a repeat upload reuses the existing file. No
// database or index needed; the filename IS the dedup key.
$hash = hash_file('sha256', $processedPath);
$safeName = $hash . '.' . $extension;
$destination = UPLOADS_DIR . '/' . $safeName;

// Only write if we don't already have this exact file. If it exists, we simply
// reuse it and skip the write.
if (!file_exists($destination)) {
    if ($processedPath === $file['tmp_name']) {
        // No resize happened — move the original upload into place
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            if ($tmpResized !== false && file_exists($tmpResized)) @unlink($tmpResized);
            failUpload('Could not save the uploaded file.');
        }
    } else {
        // Resized version lives in a temp file — copy it into place
        if (!copy($processedPath, $destination)) {
            @unlink($tmpResized);
            failUpload('Could not save the resized image.');
        }
    }
}

// Clean up the temp resized file if we made one
if ($tmpResized !== false && file_exists($tmpResized)) {
    @unlink($tmpResized);
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