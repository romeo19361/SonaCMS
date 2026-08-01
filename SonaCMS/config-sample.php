<?php
// /SonaCMS/config-sample.php
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

    // ── Licensing ────────────────────────────────────────────────────────
    // Leave 'licensed' => false for evaluation, education, or not-for-profit
    // use. While false, a small notice appears in the admin and the footer.
    //
    // If you're using SonaCMS commercially, please purchase a license at
    // www.sonacms.com. A license isn't just about removing the notice — it's
    // what keeps SonaCMS being actively developed and supported, and it gets
    // you direct WhatsApp access to Peter, the developer (you're talking to
    // the person who built it, not a support queue).
    //
    // Using SonaCMS for education or a not-for-profit? That's free — contact
    // Peter and he'll clear the notice for you.
    //
    // Once you hold a license, set 'licensed' => true and put your name (or
    // your organisation's) in 'licensee_name'.
    'licensed'            => false,
    'licensee_name'       => '',

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
];