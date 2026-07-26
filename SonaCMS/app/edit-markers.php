<?php
// /SonaCMS/app/edit-markers.php
//
// Tier-2 concurrent-edit WARNING (2.0). Advisory only — it never blocks anyone.
//
// When someone opens a page in the editor, we record a small marker: which
// page, who opened it, and when. When a DIFFERENT user opens the same page and
// a recent marker exists, the editor shows a gentle "someone may be editing
// this" notice. This dramatically reduces accidental overwrites without the
// frustration and complexity of hard locks (a stale lock can never lock a real
// user out, because there are no locks — only advice).
//
// Markers are tiny JSON files under assets/content/edit-markers/, one per page
// (keyed by the page's filename). A marker older than the stale threshold is
// ignored (and treated as free), so an editor who wandered off doesn't warn
// people forever.

require_once __DIR__ . '/paths.php';

// How long a marker is considered "fresh". After this, it's treated as stale
// (the person probably closed the tab / wandered off) and no longer warns.
const SONA_EDIT_MARKER_TTL = 600; // seconds (10 minutes)

/** Directory holding edit markers (created on demand). */
function editMarkersDir(): string
{
    return CONTENT_DIR . '/edit-markers';
}

/** Safe marker file path for a given page filename. */
function editMarkerPath(string $pageFilename): string
{
    // Sanitise: markers are keyed by page filename, which is already a slug,
    // but guard anyway so nothing odd reaches the filesystem.
    $key = preg_replace('/[^a-z0-9._-]/i', '', $pageFilename);
    return editMarkersDir() . '/' . $key . '.json';
}

/**
 * Record (or refresh) a marker saying the current user is editing $pageFilename.
 * Silent no-op on failure — markers must never break the editor.
 */
function touchEditMarker(string $pageFilename): void
{
    if ($pageFilename === '') return;
    $dir = editMarkersDir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $data = [
        'page'  => $pageFilename,
        'user'  => $_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? 'someone'),
        'email' => $_SESSION['user_email'] ?? '',
        'time'  => time(),
    ];
    @file_put_contents(editMarkerPath($pageFilename), json_encode($data), LOCK_EX);
}

/**
 * Check whether SOMEONE ELSE has a fresh marker on $pageFilename. Returns the
 * marker array (with a friendly 'ago' string) if a *different* user opened it
 * recently, or null if it's free / stale / opened by the current user.
 */
function otherEditorOn(string $pageFilename): ?array
{
    if ($pageFilename === '') return null;
    $path = editMarkerPath($pageFilename);
    if (!is_file($path)) return null;

    $raw = @file_get_contents($path);
    $m = json_decode((string) $raw, true);
    if (!is_array($m) || empty($m['time'])) return null;

    // Stale? Treat as free.
    $age = time() - (int) $m['time'];
    if ($age > SONA_EDIT_MARKER_TTL) return null;

    // Opened by the current user themselves? Not a conflict.
    $me = $_SESSION['user_email'] ?? '';
    if (!empty($m['email']) && $me !== '' && strtolower($m['email']) === strtolower($me)) {
        return null;
    }

    // A different user, recently. Build a friendly "x minutes ago".
    $mins = (int) floor($age / 60);
    if ($age < 60) {
        $m['ago'] = 'less than a minute ago';
    } elseif ($mins === 1) {
        $m['ago'] = '1 minute ago';
    } else {
        $m['ago'] = $mins . ' minutes ago';
    }
    return $m;
}

/**
 * Clear the marker for a page (called after a successful save, so the page is
 * immediately free for others). Silent no-op if it doesn't exist.
 */
function clearEditMarker(string $pageFilename): void
{
    if ($pageFilename === '') return;
    $path = editMarkerPath($pageFilename);
    if (is_file($path)) {
        @unlink($path);
    }
}