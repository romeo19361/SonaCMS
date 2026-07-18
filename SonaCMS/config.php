<?php
// SonaCMS/config.php

return [
    'licensed' => false, //true or false (purchase the relevant licence at www.sonacms.com)
    'licensee_name' => 'Name', // Name shown in the licensed footer

    // Canonical site URL — no trailing slash. Used to build <link rel="canonical">
    // tags so search engines always attribute content to THIS domain, even if the
    // site is reachable via another domain pointing at the same server.
    // Leave blank to fall back to the requesting host (not recommended in production).
    'site_url' => 'https://www.yourdomain.com',

    'admin_username' => 'Name',
    'admin_email' => 'you@yourdomain.com',
    'admin_password_hash' => 'passwordhash',

    // Where form submissions are emailed. Leave blank to use admin_email.
    'form_recipient' => '',

    // SMTP Settings.
    // Leave smtp_host as 'mail.example.com' (the default) to use PHP mail()
    // instead. Fill these with real credentials (e.g. SMTP2GO) to send via SMTP.
    'smtp_host'           => 'mail.example.com',
    'smtp_port'           => 2525,
    'smtp_user'           => 'user',
    'smtp_pass'           => 'pass',
    'smtp_from'           => 'you@yourdomain.com', // From address for SMTP sends
    'smtp_from_name'      => 'From',
];