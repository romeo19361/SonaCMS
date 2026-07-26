<?php
// /SonaCMS/app/activity.php
//
// Activity log (2.0). Records who changed what, and when — so the site manager
// has oversight of edits made by editors (and themselves).
//
// LAYER 1 by design: it logs the ACTOR, the ACTION, the TARGET (which page),
// and a TIMESTAMP. It deliberately does NOT diff content ("what exactly
// changed") — that's a much heavier feature with little payoff for the manager,
// who can just look at the page. Keep it simple.
//
// Storage: a flat JSON-lines file (one JSON object per line) under
// assets/content. Append-only, human-greppable, no database — consistent with
// the rest of SonaCMS. Reading is newest-first for display.

require_once __DIR__ . '/paths.php';

/** Absolute path to the activity log file. */
function activityLogPath(): string
{
    return CONTENT_DIR . '/activity.log';
}

/**
 * Append one entry to the activity log. Silent no-op on failure — logging must
 * never block or break an actual save.
 *
 * @param string $action e.g. 'created', 'updated', 'deleted'
 * @param string $target the page/thing affected (title or slug)
 * @param array  $extra  optional extra fields (e.g. ['type' => 'author'])
 */
function logActivity(string $action, string $target, array $extra = []): void
{
    $entry = array_merge([
        'time'   => date('c'),                       // ISO 8601
        'user'   => $_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? 'unknown'),
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['role'] ?? '',
        'action' => $action,
        'target' => $target,
    ], $extra);

    $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($line === false) {
        return;
    }
    // Append with a lock; suppress errors so a logging problem never surfaces
    // to the user mid-save.
    @file_put_contents(activityLogPath(), $line . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Read recent activity entries, newest first.
 *
 * @param int $limit max entries to return (default 200)
 * @return array list of entry arrays
 */
function readActivity(int $limit = 200): array
{
    $path = activityLogPath();
    if (!is_file($path)) {
        return [];
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }
    // Newest first
    $lines = array_reverse($lines);
    $out = [];
    foreach ($lines as $line) {
        $rec = json_decode($line, true);
        if (is_array($rec)) {
            $out[] = $rec;
        }
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}