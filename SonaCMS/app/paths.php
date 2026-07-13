<?php
// /SonaCMS-V1.1/app/paths.php
//
// Centralised path definitions for SonaCMS-V1.1.
// This file ONLY defines paths/constants — no logic, no file I/O.
// Keeping paths here means a developer (or future upgrade) only has
// to adjust one file if the on-disk layout ever changes.

// Site root.
// /app sits inside /SonaCMS-V1.1, and /assets sits one level above /SonaCMS-V1.1
// (i.e. /SonaCMS-V1.1 and /assets are siblings under the deploy folder).
// So we go up two levels from /app to reach the folder containing /assets.
define('SITE_ROOT', dirname(dirname(__DIR__)));

// Content storage
define('CONTENT_DIR', SITE_ROOT . '/assets/content');
define('PAGES_DIR', CONTENT_DIR . '/pages');

// Author tiles stored separately from pages
define('AUTHORS_DIR', CONTENT_DIR . '/authors');

// Uploads (images etc. added via upload.php)
define('UPLOADS_DIR', SITE_ROOT . '/assets/images/uploads');

// Forms directory (site root /forms), holds contact.php etc.
define('FORMS_DIR', SITE_ROOT . '/forms');

// App internals (core, upgradable, not user-edited)
// app/ lives inside /SonaCMS-V1.1.
define('APP_DIR', __DIR__);

// SonaCMS-V1.1 folder itself (where index.php and config.php live)
define('SONACMS_DIR', dirname(__DIR__));

// vendor/ and styles/ are siblings of /app, both inside /SonaCMS-V1.1 — not nested inside /app.
define('VENDOR_DIR', SONACMS_DIR . '/vendor');
define('STYLES_DIR', SONACMS_DIR . '/styles');

/**
 * Compute the public web path (URL path, not filesystem path) to the
 * site root — i.e. the folder containing /assets — regardless of what
 * directory SonaCMS-V1.1 is installed into.
 *
 * Mirrors the dirname() logic used in auth.php/logout.php, but one level
 * deeper since this accounts for the calling script's own filename too.
 *
 * @return string e.g. "" if installed at domain root, or "/some/subpath"
 */
function siteWebRoot(): string
{
    $path = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
    return $path === '/' || $path === '\\' ? '' : $path;
}