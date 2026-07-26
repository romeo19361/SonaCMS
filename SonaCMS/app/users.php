<?php
// /SonaCMS/app/users.php
//
// Multi-user support (2.0).
//
// AUTH MODEL:
//   - MANAGER: a single account whose credentials live in config.php
//     (admin_email / admin_password_hash). Set by whoever installs/hosts the
//     site. This is the super-user and a "break-glass" account — it always
//     works, can't be deleted through the app, and survives whatever happens
//     to users.json. There is exactly one manager per site.
//   - EDITORS: named accounts stored in users.json (hashed passwords), added
//     and removed by the manager through the app. Editors can edit pages but
//     cannot manage users or view the activity log.
//
// This file provides the editor store + the current-user/role helpers the rest
// of the app uses. It does NOT start the session or guard pages — that's
// auth.php. Passwords use PHP's password_hash()/password_verify() (bcrypt).

require_once __DIR__ . '/paths.php';

const SONA_ROLE_MANAGER = 'manager';
const SONA_ROLE_EDITOR  = 'editor';

/**
 * Absolute path to the editors store. Kept alongside content (flat-file, no
 * database), in the same directory the app already writes pages/authors to.
 */
function usersFilePath(): string
{
    // CONTENT_DIR is defined in paths.php (SITE_ROOT/assets/content).
    return CONTENT_DIR . '/users.json';
}

/**
 * Load the site config array (config.php one level up from app/). Cached for
 * the request so repeated calls are cheap.
 */
function sonaConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $path = __DIR__ . '/../config.php';
        $cfg = is_file($path) ? (require $path) : [];
        if (!is_array($cfg)) {
            $cfg = [];
        }
    }
    return $cfg;
}

/**
 * Load all editor records. Returns an array keyed by lowercase email:
 *   [ 'jane@club.com' => ['email'=>'jane@club.com','name'=>'Jane Smith','hash'=>'$2y$...','role'=>'editor','created'=>'...'], ... ]
 * Returns [] if the file doesn't exist yet.
 */
function loadEditors(): array
{
    $path = usersFilePath();
    if (!is_file($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    // Normalise to email-keyed map
    $out = [];
    foreach ($data as $rec) {
        if (!empty($rec['email'])) {
            $out[strtolower($rec['email'])] = $rec;
        }
    }
    return $out;
}

/**
 * Persist the editors map back to users.json. Returns true on success.
 */
function saveEditors(array $editors): bool
{
    $path = usersFilePath();
    // Store as a plain list (values), pretty-printed for human inspection.
    $list = array_values($editors);
    $json = json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    return file_put_contents($path, $json, LOCK_EX) !== false;
}

/**
 * Find a single editor record by email (case-insensitive), or null.
 */
function findEditorByEmail(string $email): ?array
{
    $editors = loadEditors();
    return $editors[strtolower(trim($email))] ?? null;
}

/**
 * Add or update an editor (identified by email). If $plainPassword is null/''
 * on an update, the existing hash is kept. Returns [true, ''] on success or
 * [false, 'reason'].
 */
function saveEditor(string $email, string $name, ?string $plainPassword): array
{
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'A valid email address is required.'];
    }
    // Guard against colliding with the config manager's email.
    $cfg = sonaConfig();
    if (isset($cfg['admin_email']) && strtolower($cfg['admin_email']) === $email) {
        return [false, 'That email is reserved for the site manager.'];
    }

    $editors = loadEditors();
    $existing = $editors[$email] ?? null;

    if ($plainPassword === null || $plainPassword === '') {
        if (!$existing) {
            return [false, 'A password is required for a new editor.'];
        }
        $hash = $existing['hash'];
    } else {
        if (strlen($plainPassword) < 8) {
            return [false, 'Password must be at least 8 characters.'];
        }
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    $editors[$email] = [
        'email'   => $email,
        'name'    => trim($name) !== '' ? trim($name) : $email,
        'hash'    => $hash,
        'role'    => SONA_ROLE_EDITOR,
        'created' => $existing['created'] ?? date('c'),
    ];

    return saveEditors($editors) ? [true, ''] : [false, 'Could not write the users file (check permissions).'];
}

/**
 * Remove an editor by email. Returns true if removed.
 */
function removeEditor(string $email): bool
{
    $email = strtolower(trim($email));
    $editors = loadEditors();
    if (!isset($editors[$email])) {
        return false;
    }
    unset($editors[$email]);
    return saveEditors($editors);
}

/**
 * Verify a login against BOTH tiers, matching on EMAIL (the app logs in by
 * email address, not username). Checks the config manager first, then the JSON
 * editors. Returns a session-ready identity array on success, or null:
 *   ['email' => 'jane@club.com', 'name' => 'Jane Smith', 'role' => 'editor']
 * The manager always takes precedence over any colliding editor email.
 */
function verifyLogin(string $email, string $password): ?array
{
    $email = strtolower(trim($email));
    $cfg = sonaConfig();

    // 1) Config manager (the break-glass super-user)
    if (isset($cfg['admin_email'], $cfg['admin_password_hash'])
        && strtolower($cfg['admin_email']) === $email
        && is_string($cfg['admin_password_hash'])
        && password_verify($password, $cfg['admin_password_hash'])) {
        return [
            'email' => $cfg['admin_email'],
            'name'  => $cfg['admin_username'] ?? $cfg['admin_email'],
            'role'  => SONA_ROLE_MANAGER,
        ];
    }

    // 2) JSON editors (matched by email)
    $editor = findEditorByEmail($email);
    if ($editor && !empty($editor['hash']) && password_verify($password, $editor['hash'])) {
        return [
            'email' => $editor['email'],
            'name'  => $editor['name'] ?? $editor['email'],
            'role'  => SONA_ROLE_EDITOR,
        ];
    }

    return null;
}

// ---- Current-user helpers (used across the app after auth.php runs) --------

/** The logged-in user's email, or '' if not set. */
function currentUserEmail(): string
{
    return $_SESSION['user_email'] ?? '';
}

/** The logged-in user's display name, or '' if not set. */
function currentUserName(): string
{
    return $_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? '');
}

/** The logged-in user's role ('manager' | 'editor'), or '' if not set. */
function currentRole(): string
{
    return $_SESSION['role'] ?? '';
}

/** True if the current user is the manager. */
function isManager(): bool
{
    return currentRole() === SONA_ROLE_MANAGER;
}

/**
 * Guard a manager-only page: redirect editors away. Call after auth.php has
 * confirmed the user is logged in.
 */
function requireManager(): void
{
    if (!isManager()) {
        $home = dirname($_SERVER['SCRIPT_NAME']) . '/admin.php';
        header('Location: ' . $home);
        exit;
    }
}