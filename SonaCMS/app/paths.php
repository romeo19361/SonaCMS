<?php
// /SonaCMS/app/paths.php
//
// Centralised path definitions for SonaCMS.
// This file ONLY defines paths/constants — no logic, no file I/O.
// Keeping paths here means a developer (or future upgrade) only has
// to adjust one file if the on-disk layout ever changes.
//
// This file is SELF-GUARDING: every define()/function is wrapped so the file
// can be included more than once in a request (e.g. once by a page and again
// transitively via users.php) without a "cannot redeclare" fatal. This makes
// include-order across the app robust.

// Site root.
// /app sits inside /SonaCMS, and /assets sits one level above /SonaCMS
// (i.e. /SonaCMS and /assets are siblings under the deploy folder).
// So we go up two levels from /app to reach the folder containing /assets.
if (!defined('SITE_ROOT'))  define('SITE_ROOT', dirname(dirname(__DIR__)));

// Content storage
if (!defined('CONTENT_DIR')) define('CONTENT_DIR', SITE_ROOT . '/assets/content');
if (!defined('PAGES_DIR'))   define('PAGES_DIR', CONTENT_DIR . '/pages');

// Author tiles stored separately from pages
if (!defined('AUTHORS_DIR')) define('AUTHORS_DIR', CONTENT_DIR . '/authors');

// Uploads (images etc. added via upload.php)
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', SITE_ROOT . '/assets/images/uploads');

// Document downloads (PDF, Word, etc.) uploaded via the Download block
if (!defined('FILES_DIR'))   define('FILES_DIR', SITE_ROOT . '/assets/files/uploads');

// Forms directory (site root /forms), holds contact.php etc.
if (!defined('FORMS_DIR'))   define('FORMS_DIR', SITE_ROOT . '/forms');

// App internals (core, upgradable, not user-edited)
// app/ lives inside /SonaCMS.
if (!defined('APP_DIR'))     define('APP_DIR', __DIR__);

// SonaCMS folder itself (where index.php and config.php live)
if (!defined('SONACMS_DIR')) define('SONACMS_DIR', dirname(__DIR__));

// vendor/ and styles/ are siblings of /app, both inside /SonaCMS — not nested inside /app.
if (!defined('VENDOR_DIR'))  define('VENDOR_DIR', SONACMS_DIR . '/vendor');
if (!defined('STYLES_DIR'))  define('STYLES_DIR', SONACMS_DIR . '/styles');

/**
 * Compute the public web path (URL path, not filesystem path) to the
 * site root — i.e. the folder containing /assets — regardless of what
 * directory SonaCMS is installed into.
 *
 * @return string e.g. "" if installed at domain root, or "/some/subpath"
 */
if (!function_exists('siteWebRoot')) {
    function siteWebRoot(): string
    {
        $path = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
        return $path === '/' || $path === '\\' ? '' : $path;
    }
}