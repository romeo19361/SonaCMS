<?php
// /inc/formHandler.php
//
// Generic form handler for SonaCMS. Works with ANY form that posts here —
// it doesn't expect specific field names. It:
//   1. Drops submissions where the honeypot field ("contact_time") is filled.
//   2. Emails all submitted fields to the configured recipient.
//   3. Redirects to the form's "redirect" value (or "/" as a fallback).
//
// Sending method is chosen automatically:
//   - If SMTP is configured in config.php (smtp_host is not the default
//     placeholder), send via PHPMailer over SMTP (e.g. SMTP2GO).
//   - Otherwise fall back to PHP's mail().
//
// Reserved field names (not emailed as content):
//   subject       — the email subject line
//   redirect      — where to send the visitor after submitting
//   contact_time  — honeypot (a hidden checkbox; must stay unticked)
//
// NOTE ON THE HONEYPOT: the hidden anti-spam field is a CHECKBOX, not a text
// input. Browser autofill and password managers fill hidden TEXT fields with
// stray values (a town, an email) even with autocomplete="off" — which would
// make genuine human submissions look like bots and be silently dropped. They
// do NOT tick hidden checkboxes, so a checkbox honeypot avoids that false
// positive while still catching bots that blindly tick every field.

error_reporting(E_ALL);
ini_set('display_errors', 0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

// Load CMS config. config.php lives in /SonaCMS (sibling of /inc).
$config = require __DIR__ . '/../SonaCMS/config.php';

// Recipient: prefer form_recipient, fall back to admin_email.
$recipient = !empty($config['form_recipient'])
    ? $config['form_recipient']
    : ($config['admin_email'] ?? '');

// Validate redirect — same-site relative paths only (prevents open redirect)
$redirect = $_POST['redirect'] ?? '/';
if (!preg_match('#^/[^/]#', $redirect)) {
    $redirect = '/';
}

// Honeypot — silently redirect bots without sending.
// This is a hidden CHECKBOX (see the form). Real users never tick it; browser
// autofill won't tick it either (unlike hidden text fields, which autofill may
// populate with stray values). An unticked box isn't sent in POST at all, so
// genuine submissions pass; bots that tick everything are dropped here.
if (!empty($_POST['contact_time'])) {
    header('Location: ' . $redirect);
    exit;
}

// No recipient configured — fail safe
if ($recipient === '') {
    header('Location: ' . $redirect);
    exit;
}

// ─── Build the email content ─────────────────────────────────────────────

$reserved = ['subject', 'redirect', 'contact_time'];
$subject  = str_replace(["\r", "\n"], '', $_POST['subject'] ?? 'Website Form Submission');

$lines = [];
$replyTo = '';
foreach ($_POST as $key => $value) {
    if (in_array($key, $reserved, true)) {
        continue;
    }
    if (is_array($value)) {
        $value = implode(', ', $value);
    }
    // Capture first valid email as Reply-To
    if ($replyTo === '' && !is_array($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $replyTo = $value;
    }
    // "fullName" -> "Full Name"
    $label = ucwords(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $key)));
    $lines[] = $label . ': ' . trim($value);
}

$body = "You have received a new form submission:\n\n"
    . implode("\n", $lines)
    . "\n\n---\nSent from " . ($_SERVER['HTTP_HOST'] ?? 'your website');

// ─── Decide sending method ───────────────────────────────────────────────

// SMTP is considered "configured" if smtp_host is set and is not the default
// placeholder value shipped in config.php.
$smtpConfigured = !empty($config['smtp_host'])
    && $config['smtp_host'] !== 'mail.example.com';

if ($smtpConfigured) {
    // ── Send via PHPMailer / SMTP ──
    require_once __DIR__ . '/../SonaCMS/vendor/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../SonaCMS/vendor/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../SonaCMS/vendor/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'] ?? '';
        $mail->Password   = $config['smtp_pass'] ?? '';
        $mail->Port       = (int)($config['smtp_port'] ?? 587);

        // STARTTLS for port 587, implicit TLS for 465
        $mail->SMTPSecure = ((int)($config['smtp_port'] ?? 587) === 465)
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        $fromAddress = $config['smtp_from'] ?? ($config['admin_email'] ?? 'noreply@localhost');
        $fromName    = $config['smtp_from_name'] ?? 'Website';
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($recipient);

        if ($replyTo !== '') {
            $mail->addReplyTo($replyTo);
        }

        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (PHPMailerException $e) {
        // Fail quietly to the visitor; the redirect still happens below.
        // (Optional: log $mail->ErrorInfo somewhere for the admin.)
    }
} else {
    // ── Fall back to PHP mail() ──
    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if ($replyTo !== '') {
        $headers[] = 'Reply-To: ' . str_replace(["\r", "\n"], '', $replyTo);
    }
    @mail($recipient, $subject, $body, implode("\r\n", $headers));
}

header('Location: ' . $redirect);
exit;