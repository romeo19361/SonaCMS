<?php
// /SonaCMS/config-sample.php
// SonaCMS — https://www.sonacms.com
// Copyright (c) 2026 Peter Bishop / Tecky
// SPDX-License-Identifier: MIT
//
// ┌─────────────────────────────────────────────────────────────────────────┐
// │  SETUP: Rename this file to  config.php  and fill in your own values.    │
// │  SonaCMS reads config.php — this sample file is never used directly.     │
// │  Shipping a *sample* means an upgrade can never overwrite the real       │
// │  config.php you create here.                                             │
// └─────────────────────────────────────────────────────────────────────────┘
//
// This is the ONLY file you need to edit to get SonaCMS running.

return [

    // ── Site URL ─────────────────────────────────────────────────────────
    // Your site's canonical address, with NO trailing slash. Used for
    // canonical URLs, the sitemap, and social share images so search engines
    // always attribute your content to the correct domain.
    'site_url'            => 'https://www.example.com',

    // ── Timezone ─────────────────────────────────────────────────────────
    // Your site's local timezone, used for all dates (activity log, publish
    // dates, etc.). Use a PHP timezone identifier — e.g. 'Australia/Sydney',
    // 'Europe/London', 'America/New_York'. Full list:
    // https://www.php.net/manual/en/timezones.php
    // Defaults to 'UTC' if omitted or invalid.
    'timezone'            => 'Australia/Sydney',

    // ── Admin login ──────────────────────────────────────────────────────
    // The username and password you'll use to log in at /SonaCMS/.
    'admin_username'      => 'admin',
    'admin_email'         => 'you@example.com',

    // The admin password is stored as a HASH, never as plain text. Generate
    // your own by running this on any machine with PHP (then delete the file):
    //
    //     <?php echo password_hash('your-chosen-password', PASSWORD_DEFAULT);
    //
    // Paste the resulting string (it starts with $2y$...) below.
    'admin_password_hash' => 'REPLACE_WITH_YOUR_PASSWORD_HASH',

    // ── Form email ───────────────────────────────────────────────────────
    // Where contact-form submissions are sent. Leave blank to use admin_email.
    'form_recipient'      => '',

    // ── Email sending ────────────────────────────────────────────────────
    // Leave 'smtp_host' as 'mail.example.com' (the default) to use PHP's
    // built-in mail() function. This works on some hosts but is often
    // unreliable (messages may be marked as spam or vanish silently).
    //
    // For dependable delivery, use an SMTP provider (e.g. SMTP2GO) and fill in
    // the values below. As soon as 'smtp_host' is set to anything other than
    // 'mail.example.com', SonaCMS automatically sends via SMTP instead.
    //
    // Example for SMTP2GO:
    //   'smtp_host' => 'mail.smtp2go.com',
    //   'smtp_port' => 587,
    'smtp_host'           => 'mail.example.com',
    'smtp_port'           => 587,
    'smtp_user'           => 'user@example.com',
    'smtp_pass'           => 'your-smtp-password',
    'smtp_from'           => 'noreply@example.com',   // From address for SMTP sends
    'smtp_from_name'      => 'Your Website',           // From name shown to recipients

    // ─── Form spam protection ────────────────────────────────────────────
    // When true, form submissions must carry a valid, freshly minted token.
    // This stops bots that POST straight to the handler without ever loading
    // the form (the spam the honeypot can't catch).
    //
    // Forms added with the editor's Form block get the token automatically.
    // If you have a hand-coded form that posts to /inc/formHandler.php some
    // other way, test it before turning this on, or it will be dropped too.
    'form_protection' => false,

    // Optional: a dedicated signing key. If omitted, a stable key is derived
    // automatically from existing config (zero-touch) — most sites can leave
    // this out. Set it only to rotate the form key independently; use a long
    // random string.
    // 'form_secret' => 'change-me-to-a-long-random-string',

    // Optional: drop any form submission containing one of these terms
    // (case-insensitive, checked across every field). Useful against a spam
    // campaign with a stable give-away value. Keep terms distinctive to avoid
    // catching real messages.
    // 'form_blocklist' => ['robertrip'],
];