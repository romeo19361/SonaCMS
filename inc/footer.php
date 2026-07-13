<?php
// /inc/footer.php
//
// Frontend footer for SonaCMS-V1.1 public pages. Included by index.php inside
// the <footer class="site-footer"> region.
//
// Shows the licensing notice. Developers can add their own footer content
// around this — but the licensing line should remain per the SonaCMS-V1.1 licence.
//
// Expects $config to be available (index.php loads it). Falls back to
// loading it directly if not, so the include is safe to use anywhere.

if (!isset($config) || !is_array($config)) {
    $config = require __DIR__ . '/../SonaCMS/config.php';
}

// Frontend licensing notice only appears on UNLICENSED installs.
// Licensed sites get a completely clean frontend footer — removing the
// public notice is part of what a commercial licence buys. The licensing
// record still shows in the admin footer either way.
if (empty($config['licensed'])):
    ?>
    <p class="site-footer__license"><?php echo licenseFooterText($config); ?></p>
<?php endif; ?>