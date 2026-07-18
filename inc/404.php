<?php
// /inc/404.php
//
// The "page not found" fallback, shown by index.php when no published page
// matches the requested URL. Lives in /inc so developers can restyle it
// freely — it survives CMS upgrades.
//
// Expects: navigationA() and getPageTree() to be available (index.php has
// already required nav.php and the SonaCMS core before including this).
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found</title>
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
        <?php echo navigationA(getPageTree()); ?>
    </div>
</header>
<main class="site-main">
    <div class="site-wrap">
        <h1 class="page-title">Page Not Found</h1>
        <p>Sorry, the page you were looking for doesn't exist.</p>
    </div>
</main>
<footer class="site-footer">
    <div class="site-wrap">
        <!-- Footer content will go here -->
    </div>
</footer>
</body>
</html>