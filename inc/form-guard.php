<?php
// /inc/form-guard.php
//
// Portable, dependency-free anti-spam helpers for SonaCMS forms.
// No external services, no accounts, no database — works on any PHP host,
// which is the whole point: a spam defence that required a Cloudflare (or
// similar) account would break SonaCMS's runs-anywhere premise.
//
// Provides:
//   sona_form_secret($config)        — the HMAC signing key (explicit or derived)
//   sona_form_token($config)         — mint a signed, timestamped token
//   sona_form_token_field($config)   — the hidden <input> to drop into a form
//   sona_form_token_valid($t,$c,...) — verify signature + submission timing
//   sona_form_rate_ok($ip,$max,$win) — flat-file per-IP rate limit
//
// Guarded against double-include (same pattern as paths.php / functions.php).

if (!defined('SONA_FORM_GUARD_LOADED')) {
    define('SONA_FORM_GUARD_LOADED', true);

    /**
     * Signing key for form tokens.
     *
     * Prefers an explicit config value ('form_secret') so a site can rotate it
     * independently. If absent, DERIVES a stable key from secrets already in
     * config.php — so existing sites gain protection with ZERO config edits and
     * the 2.5 "rebuild config" flow stays unchanged. The derived key rotates if
     * the admin password is reset, which merely invalidates outstanding
     * (unsubmitted) tokens — harmless.
     */
    function sona_form_secret(array $config): string
    {
        if (!empty($config['form_secret'])) {
            return (string) $config['form_secret'];
        }
        $material = ($config['admin_password_hash'] ?? '')
            . '|' . ($config['site_url'] ?? '')
            . '|sona-form-guard-v1';
        return hash('sha256', $material);
    }

    /** Mint a token: "<issued_ts>.<hex_hmac>". Stateless — nothing stored. */
    function sona_form_token(array $config): string
    {
        $ts  = time();
        $sig = hash_hmac('sha256', (string) $ts, sona_form_secret($config));
        return $ts . '.' . $sig;
    }

    /** The hidden field to place inside a <form> when rendering server-side. */
    function sona_form_token_field(array $config): string
    {
        $t = htmlspecialchars(sona_form_token($config), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="form_token" value="' . $t . '">';
    }

    /**
     * Validate a submitted token.
     *   - signature must verify (constant-time compare)
     *   - age >= $minAge : reject inhuman, instant posts (no one read the form)
     *   - age <= $maxAge : reject stale / replayed tokens
     */
    function sona_form_token_valid(
        string $token,
        array $config,
        int $minAge = 2,
        int $maxAge = 7200
    ): bool {
        if (strpos($token, '.') === false) {
            return false;
        }
        [$ts, $sig] = explode('.', $token, 2);
        if ($ts === '' || $sig === '' || !ctype_digit($ts)) {
            return false;
        }
        $expected = hash_hmac('sha256', $ts, sona_form_secret($config));
        if (!hash_equals($expected, $sig)) {
            return false;
        }
        $age = time() - (int) $ts;
        return $age >= $minAge && $age <= $maxAge;
    }

    /**
     * Writable directory for the rate-limit store. Prefers a dedicated,
     * web-denied folder under assets/; falls back to the system temp dir if
     * assets/ isn't writable. On first create it drops a deny-all .htaccess so
     * the store is never web-readable (Apache; harmless on nginx).
     */
    function sona_form_store_dir(): string
    {
        $dir = __DIR__ . '/../assets/formguard';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
            @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }
        if (is_dir($dir) && is_writable($dir)) {
            return $dir;
        }
        return rtrim(sys_get_temp_dir(), '/\\');
    }

    /**
     * Flat-file per-IP rate limit. Returns true if this hit is allowed (and
     * records it), false if the IP is over $max hits in the last $window secs.
     * Self-pruning; no database. Keyed by a hash of the IP, not the IP itself.
     */
    function sona_form_rate_ok(string $ip, int $max = 5, int $window = 600): bool
    {
        $ip   = $ip !== '' ? $ip : 'unknown';
        $file = sona_form_store_dir() . '/rl_' . hash('sha256', $ip) . '.json';

        $now  = time();
        $hits = [];
        if (is_file($file)) {
            $raw  = @file_get_contents($file);
            $data = $raw !== false ? json_decode($raw, true) : [];
            if (is_array($data)) {
                foreach ($data as $t) {
                    if (is_int($t) && ($now - $t) < $window) {
                        $hits[] = $t;
                    }
                }
            }
        }

        if (count($hits) >= $max) {
            return false;
        }

        $hits[] = $now;
        @file_put_contents($file, json_encode($hits), LOCK_EX);
        return true;
    }
}