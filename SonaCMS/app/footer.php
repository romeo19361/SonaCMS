<?php
// /SonaCMS/app/footer.php
//
// Admin footer for SonaCMS backend pages (admin.php, editor.php).
// Shows the licensing notice at the bottom of admin screens.
//
// Loads config directly (admin pages don't always have $config in scope).
// paths/functions are already loaded by the including page.

$__cfg = require __DIR__ . '/../config.php';
?>
<footer class="sona-footer">
    <div class="sona-wrap">
        <p class="sona-footer__license"><?php echo licenseFooterText($__cfg); ?></p>
    </div>
</footer>