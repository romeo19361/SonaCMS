<?php
// /inc/form-token.php
//
// Same-origin endpoint that hands a freshly minted form token to the page's
// JavaScript (form-guard.js). Used when a form is served as static HTML with
// no server-side step to embed a token. Portable: no external services.
//
// If your forms are rendered through PHP, you don't need this endpoint or the
// JS — inject the token directly with sona_form_token_field($config) instead.

require_once __DIR__ . '/form-guard.php';
$config = require __DIR__ . '/../SonaCMS/config.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');

echo sona_form_token($config);