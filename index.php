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
    <?php echo renderPageHead($page, $config, $currentPath); ?>
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

<?php echo renderHero($page); ?>

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

<!-- Lightbox overlay (used by image blocks set to "lightbox" mode). -->
<div id="cms-lightbox" class="cms-lightbox-overlay" aria-hidden="true">
    <span class="cms-lightbox-overlay__close" aria-label="Close">&times;</span>
    <img class="cms-lightbox-overlay__img" src="" alt="">
</div>
<script src="/js/lightbox.js"></script>

</body>
</html>