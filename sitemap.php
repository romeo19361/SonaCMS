<?php
// /sitemap.php
//
// Generates an XML sitemap dynamically from SonaCMS's published pages.
// Served at /sitemap.xml via a rewrite rule in .htaccess.
//
// Only published pages are included — drafts are excluded automatically.
// URLs are built hierarchically (/parent-slug/child-slug), matching how the
// front controller routes them, so the sitemap always reflects the real site
// structure with no manual maintenance.

error_reporting(E_ALL);
ini_set('display_errors', 0);

require __DIR__ . '/SonaCMS/app/paths.php';
require __DIR__ . '/SonaCMS/app/functions.php';

header('Content-Type: application/xml; charset=UTF-8');

$config = require __DIR__ . '/SonaCMS/config.php';

// Use the configured canonical site URL so the sitemap always lists the real
// domain — not whatever host happened to serve the request. This matters if
// another domain points at the same server.
$base = !empty($config['site_url'])
    ? rtrim($config['site_url'], '/')
    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? ''));

$pages = getAllPages();

// Index pages by filename so we can resolve parent chains
$byFilename = [];
foreach ($pages as $p) {
    $byFilename[$p['filename']] = $p;
}

/**
 * Build a page's full URL path by walking up its parent chain.
 * Mirrors the logic used by the front controller and navigation.
 */
function sitemapPageUrl(array $page, array $byFilename): string
{
    $parts   = [];
    $current = $page;
    $visited = [];

    while ($current) {
        $fn = $current['filename'] ?? '';
        if (isset($visited[$fn])) break; // circular reference guard
        $visited[$fn] = true;

        array_unshift($parts, $current['slug'] ?? '');

        $parentFn = $current['page_parent'] ?? '';
        $current  = ($parentFn !== '' && isset($byFilename[$parentFn]))
            ? $byFilename[$parentFn]
            : null;
    }

    $path = implode('/', array_filter($parts));

    // The "home" slug is the site root
    if ($path === 'home' || $path === '') {
        return '/';
    }

    return '/' . $path;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $page) {

    // Skip drafts — only published pages belong in a sitemap
    if (($page['status'] ?? '') !== 'published') {
        continue;
    }

    $loc = $base . sitemapPageUrl($page, $byFilename);

    // Use the page's date field for <lastmod> if it's a valid date,
    // otherwise fall back to the file's modification time.
    $lastmod = '';
    if (!empty($page['date']) && strtotime($page['date']) !== false) {
        $lastmod = date('Y-m-d', strtotime($page['date']));
    } else {
        $file = PAGES_DIR . '/' . $page['filename'] . '.json';
        if (file_exists($file)) {
            $lastmod = date('Y-m-d', filemtime($file));
        }
    }

    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
    if ($lastmod !== '') {
        echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    }
    echo "  </url>\n";
}

echo '</urlset>' . "\n";