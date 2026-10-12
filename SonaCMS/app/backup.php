<?php
// /SonaCMS/app/backup.php
// SonaCMS — https://www.sonacms.com
// Copyright (c) 2026 Peter Bishop / Tecky
// SPDX-License-Identifier: MIT
//
// One-click full-site backup. Builds a zip of the entire site (everything needed
// to restore on any PHP host) and streams it to the browser as a download.
//
// SECURITY: this route is gated behind the normal admin auth AND restricted to
// the manager. config.php is deliberately EXCLUDED from the zip (see the
// exclusion list below) so live secrets — the manager hash, SMTP credentials
// and payment/API keys — never travel inside a backup that gets stored offsite,
// emailed, or handed to a developer. But the zip still contains users.json
// (editor password hashes) and all site content, so an unauthenticated or
// lower-privilege route here would still hand the whole site to whoever hits
// the URL. The auth gate remains the security-critical part of this feature.
//
// DESIGN: the zip is generated to a TEMP FILE (not stream-zipped inline), then
// streamed and deleted. Inline stream-zipping hits max_execution_time / memory
// limits and fails silently on exactly the large sites (galleries, PDFs) that
// most need a backup. Temp-file-then-serve is reliable at size.
//
// Out of scope (this is the product core, not Tecky's hosting layer): off-site
// push, scheduled/cron backups, email delivery. Core is just: button → zip →
// download → cleanup.

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/auth.php';        // enforces login; loads paths.php + users.php
require __DIR__ . '/functions.php';   // licenseFooterText() for the footer

// Manager only. Editors don't get the whole site (with everyone's hashes).
requireManager();

$config = require __DIR__ . '/../config.php';

// ---------------------------------------------------------------------------
// If this is the download request, build and stream the zip. Otherwise, show
// the admin page with the button (further below).
// ---------------------------------------------------------------------------
if (($_GET['action'] ?? '') === 'download') {

    // CSRF: the download is triggered by a GET link carrying the token, so a
    // cross-site request can't trigger a backup download on the admin's behalf.
    if (($_GET['csrf'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Security check failed.');
    }

    // ZipArchive may not be present in a stranger's PHP build — detect and fail
    // clearly rather than fatalling.
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        exit("This server's PHP is missing the Zip extension (ZipArchive), which "
            . "is required to build a backup. Ask your host to enable php-zip, or "
            . "back up the site by copying its folder manually.");
    }

    // Give big sites room. Time first; memory is raised only around the build.
    @set_time_limit(0);

    $siteRoot = SITE_ROOT;

    // Derive a filename-safe site name from site_url (fallback 'sonacms').
    $host = parse_url($config['site_url'] ?? '', PHP_URL_HOST) ?: 'sonacms';
    $siteName = preg_replace('/[^a-z0-9]+/i', '-', strtolower($host));
    $siteName = trim($siteName, '-') ?: 'sonacms';
    $downloadName = $siteName . '-backup-' . date('Y-m-d') . '.zip';

    // Build the temp zip OUTSIDE the web root so a half-written archive is never
    // web-accessible. Use the system temp dir.
    $tmpZip = tempnam(sys_get_temp_dir(), 'sona_backup_');
    if ($tmpZip === false) {
        http_response_code(500);
        exit('Could not create a temporary file for the backup.');
    }

    // Ensure the temp file is always removed, however we leave this block.
    $cleanup = static function () use ($tmpZip) {
        if (is_file($tmpZip)) {
            @unlink($tmpZip);
        }
    };
    register_shutdown_function($cleanup);

    // Paths/patterns to exclude — kept consistent with a shell-script backup so
    // the two produce equivalent archives. Nothing volatile or regenerable.
    $excludeDirs = [
        $siteRoot . '/assets/content/edit-markers',   // transient edit markers
        $siteRoot . '/backups',                        // never zip the backups dir
        $siteRoot . '/tmp',
        $siteRoot . '/cache',
    ];
    $excludeExt = ['lock'];               // *.lock
    $excludeNames = ['.DS_Store', 'Thumbs.db'];

    // config.php is deliberately kept OUT of the backup. It is the only file
    // holding plaintext secrets (manager hash, SMTP credentials, payment/API
    // keys), so excluding it means the zip carries no passwords or keys and is
    // safe to store or hand off. The bundled config-sample.php + HANDOVER.txt
    // cover rebuilding it on restore. Matched by CANONICAL path (not basename)
    // so an unrelated config.php elsewhere in the site is never caught, and a
    // separator/symlink quirk can never cause a silent miss that leaks the key.
    $configReal = realpath(SONACMS_DIR . '/config.php');

    $isExcluded = static function (string $absPath) use ($excludeDirs, $excludeExt, $excludeNames, $configReal): bool {
        // Exact config.php match. Cheap basename pre-check keeps realpath() off
        // the hot path for the thousands of other files in a large site.
        if (basename($absPath) === 'config.php'
            && $configReal !== false
            && realpath($absPath) === $configReal) {
            return true;
        }
        foreach ($excludeDirs as $dir) {
            if ($absPath === $dir || strpos($absPath, $dir . DIRECTORY_SEPARATOR) === 0) {
                return true;
            }
        }
        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
        if (in_array($ext, $excludeExt, true)) {
            return true;
        }
        if (in_array(basename($absPath), $excludeNames, true)) {
            return true;
        }
        return false;
    };

    $prevMem = ini_get('memory_limit');
    @ini_set('memory_limit', '512M');

    $zip = new ZipArchive();
    if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
        @ini_set('memory_limit', $prevMem);
        http_response_code(500);
        exit('Could not open the backup archive for writing.');
    }

    try {
        // Bundle the plain-English restore note at the zip root. This file IS
        // the no-lock-in promise — a dev can restore the whole site from it.
        $zip->addFromString('HANDOVER.txt', backupHandoverText($siteName));

        $rootLen = strlen($siteRoot) + 1;

        $it = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($siteRoot, FilesystemIterator::SKIP_DOTS),
                function ($current) use ($isExcluded) {
                    // Prune excluded directories (and skip excluded files).
                    return !$isExcluded($current->getPathname());
                }
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $abs = $file->getPathname();
            if ($isExcluded($abs)) {
                continue;
            }
            $relative = substr($abs, $rootLen);
            $zip->addFile($abs, $relative);
        }

        $zip->close();
    } catch (\Throwable $e) {
        $zip->close();
        @ini_set('memory_limit', $prevMem);
        $cleanup();
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        exit('The backup could not be completed. Please try again.');
    }

    @ini_set('memory_limit', $prevMem);

    // Sanity: make sure we actually produced a readable, non-empty zip.
    if (!is_file($tmpZip) || filesize($tmpZip) === 0) {
        $cleanup();
        http_response_code(500);
        exit('The backup archive came out empty. Please try again.');
    }

    // Stream it. Clear any buffered output first so the zip isn't corrupted.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($tmpZip));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($tmpZip);
    $cleanup();
    exit;
}

// ---------------------------------------------------------------------------
// Otherwise: render the admin page with the download button.
// ---------------------------------------------------------------------------

/**
 * The plain-English restore note bundled at the zip root. Deliberately simple:
 * it's the no-lock-in promise — any developer can restore from this.
 *
 * NOTE: this backup intentionally EXCLUDES config.php. That file holds the only
 * plaintext secrets in the site (admin hash, SMTP credentials, payment/API
 * keys), so keeping it out means the zip is safe to store and transfer. The
 * restore steps below cover rebuilding it from config-sample.php.
 */
function backupHandoverText(string $siteName): string
{
    $date = date('j F Y');
    return <<<TXT
SonaCMS site backup — {$siteName}
Created: {$date}

WHAT THIS IS
------------
A complete copy of a SonaCMS website. SonaCMS is a flat-file CMS: there is NO
database. Everything the site needs is in these files — pages and content,
uploaded images and documents, and the CMS itself.

ONE THING IS DELIBERATELY LEFT OUT: config.php
----------------------------------------------
This backup does NOT contain config.php. That is on purpose, not a mistake.

config.php is the only file holding plaintext secrets — the admin login, the
SMTP (email) credentials, and any payment/API keys. Leaving it out means this
zip carries no passwords or keys, so it's safe to store offsite, email, or hand
to a developer without leaking access to anything.

You rebuild config.php during the restore, from the included config-sample.php.
It's a short, one-time step (below).

HOW TO RESTORE (any developer, any standard PHP host)
-----------------------------------------------------
1. Unzip this archive onto a server running PHP 8.0 or newer (Apache with
   mod_rewrite, or an nginx equivalent).
2. Point the web root at the folder's index.php.
3. Make the 'assets' folder writable by the web server (see INSTALL.md inside
   SonaCMS, and check the note about ownership vs permissions).
4. Rebuild the config file:
     a. In the SonaCMS/ folder, rename  config-sample.php  to  config.php
     b. Open it and fill in the values (it's commented throughout):
          - site_url        — the domain you're restoring to (no trailing slash)
          - timezone        — e.g. Australia/Sydney
          - admin login     — username, email, and a fresh password hash
                              (the sample shows the one-line command to make one)
          - SMTP + keys     — email credentials and any payment/API keys
     The site's owner holds these secret values in their own secure records —
     they are NOT in this backup, by design. site_url and timezone you can set
     yourself; the credentials come from the owner.
5. That's it. There is no database to import.

Until config.php exists and has a valid admin password hash, you won't be able
to log in at /SonaCMS/ — that's expected. Completing step 4 unlocks it.

NOTES
-----
- Content lives in assets/content (pages, authors, user accounts, activity log).
- Uploaded media lives in assets/images/uploads and assets/files/uploads.
- The CMS code lives in the SonaCMS/ folder; the public site is served from
  index.php and the /inc, /css folders at the root.

This backup was produced by SonaCMS's built-in one-click backup. You are never
locked in: the whole site is just files, restorable by hand on any PHP host.
SonaCMS is free and open source under the MIT License.

www.SonaCMS.com
TXT;
}

$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>Backup | SonaCMS</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="images/favicon.ico" sizes="any">
</head>
<body class="sona-admin">
<div class="sona-wrap sona-wrap--wide">

    <div class="sona-top-bar">
        <div class="sona-top-bar__brand">
            <a href="admin.php" class="sona-brand-link"><img src="images/SonaCMS.svg" alt="SonaCMS" class="sona-brand-logo"></a>
            <h2>Backup</h2>
        </div>
        <div>
            <a href="admin.php">Pages</a>
            <a href="logout.php">Log out</a>
        </div>
    </div>

    <div class="sona-backup">
        <p class="sona-backup__lead">
            Download a complete copy of this site &mdash; a full backup you can
            keep safe or hand to any developer.
        </p>
        <p class="sona-backup__note">
            Because SonaCMS has no database, the download is everything: your
            pages, uploads, settings, and the CMS itself. It restores by simply
            unzipping onto any PHP host &mdash; a plain-English guide is included
            inside.
        </p>

        <a class="sona-btn sona-backup__btn" href="backup.php?action=download&amp;csrf=<?php echo $csrf; ?>">
            &#8681; Download a full backup of this site
        </a>

        <p class="sona-backup__hint">
            Large sites (lots of images or PDFs) may take a moment to prepare
            before the download begins.
        </p>
    </div>

</div>

<?php require __DIR__ . '/footer.php'; ?>

</body>
</html>