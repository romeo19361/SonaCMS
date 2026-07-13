<?php
// /public_html/index.php
//
// Frontend entry point — front controller for all public pages.
// Reads the URL path, maps it to a page slug, and renders the matching page.

error_reporting(E_ALL);
ini_set('display_errors', 0); // never show errors to visitors on the frontend

require __DIR__ . '/SonaCMS/app/paths.php';
require __DIR__ . '/SonaCMS/app/functions.php';
require __DIR__ . '/inc/nav.php';

$config = require __DIR__ . '/SonaCMS/config.php';

// Determine the current slug from the URL path.
// A request to the domain root (/) maps to "home".
$urlPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$currentSlug = $urlPath === '' ? 'home' : $urlPath;
$currentPath = $urlPath === '' ? '/' : '/' . $urlPath;

$page = getPageBySlug($currentSlug);

// If no matching published page found, 404
if (!$page) {
    http_response_code(404);
    require __DIR__ . '/inc/404.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($page['title'] ?? ''); ?></title>

    <?php if (!empty($page['meta_description'])): ?>
        <meta name="description" content="<?php echo htmlspecialchars($page['meta_description']); ?>">
    <?php endif; ?>

    <?php if (!empty($page['meta_keywords'])): ?>
        <meta name="keywords" content="<?php echo htmlspecialchars($page['meta_keywords']); ?>">
    <?php endif; ?>

    <?php
    // Canonical base URL for this site. Built from the configured site_url —
    // NOT the request host — so if the site is also reachable via another
    // domain pointing at this server, search engines and social scrapers still
    // attribute content to the real domain. Falls back to the request host only
    // if site_url isn't configured.
    $canonicalBase = !empty($config['site_url'])
        ? rtrim($config['site_url'], '/')
        : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? ''));
    ?>

    <?php if (!empty($page['og_image'])):
        // Absolute URL required — social scrapers reject relative paths.
        $ogImageUrl = $canonicalBase . $page['og_image'];
        ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($ogImageUrl); ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImageUrl); ?>">
    <?php endif; ?>

    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalBase . $currentPath, ENT_QUOTES); ?>">

    <link rel="icon" href="/images/favicon.ico" sizes="any">
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/navigationA.css">
    <link rel="stylesheet" href="/css/forms.css">
</head>
<body>

<header class="site-header">
    <div class="site-wrap site-header__inner">
        <a class="site-logo" href="/">
            <img src="/images/SonaCMS_logo.png" alt="SonaCMS">
        </a>
        <?php echo navigationA(getPageTree(), 0, $currentPath); ?>
    </div>
</header>

<main class="site-main">
    <div class="site-wrap">

        <div class="cms-content">
            <?php echo renderContent($page['content'] ?? ''); ?>
        </div>

    </div>
</main>

<footer class="site-footer">
    <div class="site-wrap">
        <?php require __DIR__ . '/inc/footer.php'; ?>
    </div>
</footer>

</body>
</html>