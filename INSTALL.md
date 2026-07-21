# Installing SonaCMS

SonaCMS is a flat-file CMS — it stores all content as JSON files on disk, so
there is **no database to set up**. Installation is mostly a matter of
uploading the files, setting a few folder permissions, and editing one
configuration file.

---

## Requirements

- PHP 8.0 or newer
- Apache with `mod_rewrite` enabled (for clean URLs), or Nginx with an
  equivalent rewrite rule
- The ability to set file/folder permissions (via SSH, SFTP, or your host's
  file manager)
- An email sending method — either the server's `mail()` function, or SMTP
  credentials (e.g. SMTP2GO) for reliable delivery

---

## 1. Upload the files

Upload the entire SonaCMS package to your web root (often `public_html`,
`www`, or a site-specific folder). After uploading, your directory should
look like this:

```
your-web-root/
├── index.php              ← front controller (public entry point)
├── .htaccess              ← URL rewriting + JSON/config protection
├── assets/
│   ├── content/
│   │   ├── pages/         ← page JSON files are stored here
│   │   └── authors/       ← author JSON files are stored here
│   ├── images/
│   │   └── uploads/       ← editor & social image uploads land here
│   └── files/
│       └── uploads/       ← document downloads (PDF, Word, etc.) land here
├── css/
│   ├── styles.css         ← frontend base styles
│   ├── navigationA.css    ← navigation + header layout
│   └── forms.css          ← frontend form styles
├── js/
│   └── lightbox.js        ← frontend lightbox for enlargeable images
├── images/
│   └── SonaCMS_logo.png
├── inc/                   ← developer-editable frontend files
│   ├── nav.php            ← navigation markup/logic
│   ├── footer.php         ← frontend footer (licensing notice)
│   ├── 404.php            ← "page not found" page
│   └── formHandler.php    ← generic form processor
├── forms/                 ← drop-in form files (appear in the editor)
│   └── contact.php
└── SonaCMS/               ← the CMS core (upgradeable)
    ├── config-sample.php  ← rename to config.php, then edit
    ├── index.php          ← admin login
    ├── app/               ← core logic (overwritten on upgrade)
    │   ├── admin.php      ← page list / dashboard
    │   ├── editor.php     ← page editor
    │   ├── authors.php    ← author management
    │   ├── footer.php     ← admin footer (licensing notice)
    │   ├── functions.php  ← core functions
    │   ├── paths.php      ← path definitions
    │   ├── auth.php, logout.php, upload.php
    │   └── css/styles.css ← admin styles
    └── vendor/            ← third-party + custom editor tools
        ├── PHPMailer/     ← SMTP sending
        ├── button-tool.js, form-tool.js, author-tool.js
        ├── video-embed-tool.js, emoji-inline-tool.js
```

SonaCMS can be installed at the domain root or in a subdirectory — it
resolves its own paths, so no hard-coded URLs need changing.

---

## 2. Set folder permissions

The web server needs to be able to **write** to these folders so it can save
pages, authors, and uploaded images:

```
assets/content/pages/
assets/content/authors/
assets/images/uploads/
assets/files/uploads/
```

Set these so the web server user can write to them. On most Linux hosts:

```bash
chown -R www-data:www-data assets/content assets/images assets/files
chmod -R 755 assets/content assets/images assets/files
```

Replace `www-data` with your server's PHP user if different (common
alternatives are `nginx`, `apache`, or a per-account user on shared hosting).
If you can't change ownership, `775` on those folders will also work, though
tighter is always better.

> **Tip:** If saving a page or author later gives a "could not save /
> permission denied" error, this step is almost always the cause.

---

## 3. Configure the CMS

SonaCMS ships with a file called `SonaCMS/config-sample.php`. **Rename (or copy)
it to `SonaCMS/config.php`**, then edit the values. SonaCMS reads `config.php` —
the sample is never used directly.

> **Why a sample file?** Shipping `config-sample.php` rather than `config.php`
> means that when you later upgrade SonaCMS, the update can never overwrite the
> real `config.php` you created — your settings and credentials are safe.

`config.php` is the **only** file you need to change to get running.

```php
return [
    'licensed'            => false, // true once you hold a commercial licence
    'licensee_name'       => '',    // shown in the admin footer when licensed

    // Your site's canonical address, no trailing slash. Used for canonical
    // URLs, the sitemap, and social image tags so search engines always
    // attribute content to the correct domain.
    'site_url'            => 'https://www.yoursite.com',

    'admin_username'      => 'admin',
    'admin_email'         => 'you@example.com',
    'admin_password_hash' => '...',  // see below — do NOT store a plain password

    'form_recipient'      => '',     // where form emails go (blank = admin_email)

    // Email sending. Leave smtp_host as 'mail.example.com' to use PHP mail().
    // Fill these in to send via SMTP (e.g. SMTP2GO) instead.
    'smtp_host'           => 'mail.example.com',
    'smtp_port'           => 587,
    'smtp_user'           => 'user@example.com',
    'smtp_pass'           => 'secure-password',
    'smtp_from'           => 'noreply@example.com',
    'smtp_from_name'      => 'Your Website',
];
```

### Generating your password hash

The admin password is stored as a **hash**, never as plain text. Generate one
by running this on any machine with PHP, or via a one-off script on your
server:

```php
<?php
echo password_hash('your-chosen-password', PASSWORD_DEFAULT);
```

Copy the resulting string (it starts with `$2y$...`) into
`admin_password_hash`. Delete the script afterwards if you made one on the
server.

### Licensing

- `licensed => false` (the default) shows an evaluation notice in the site
  footer and admin. Use this for evaluation, education, or not-for-profit use.
- `licensed => true` with a `licensee_name` removes the public footer notice
  and shows your licence record in the admin only. Requires a commercial
  licence from www.SonaCMS.com.

---

## 4. Email delivery (optional but recommended)

By default SonaCMS uses PHP's `mail()` function, which works on some hosts but
is frequently unreliable (messages land in spam or vanish silently).

For dependable delivery, use SMTP. Fill in the `smtp_*` values in
`config.php` with credentials from an SMTP provider. For **SMTP2GO**:

- `smtp_host` → `mail.smtp2go.com`
- `smtp_port` → `587` (or `2525` / `8025` if `587` is blocked)
- `smtp_user` / `smtp_pass` → an SMTP user you create in the SMTP2GO dashboard

As soon as `smtp_host` is set to anything other than the default
`mail.example.com`, SonaCMS automatically switches from `mail()` to SMTP (via
the bundled PHPMailer) — no code change needed.

---

## 5. Log in and create your first page

SonaCMS ships with a single example **home page** so your site works
immediately after install. Visit `https://your-site.com/` and you'll see it.
It's just a starting point — edit it to make it your own, or delete it and
create your own home page (give the replacement the **slug** `home`, which is
the page shown at your domain root).

To log in and start editing:

1. Visit `https://your-site.com/SonaCMS/` — you'll see the login screen.
2. Log in with the email and password you configured.
3. Click the example home page to edit it, or **+ New Page** to create your own.
4. When creating the home page yourself, give it the **filename** `home` and
   the **slug** `home`.
5. Set **Status** to *Published*, add your content, and save.
6. Visit `https://your-site.com/` — your home page is live.

Additional pages work the same way. Use the **Parent Page** field to nest
pages (their URLs become `/parent-slug/child-slug`), the **Page Order** field
to control menu order, and the **Show in navigation** toggle to include or
hide a page from the menu.

---

## 6. The editor

The page editor is block-based. Use the **+** button (or the toolbox icon) to
add content blocks:

- **Text, Heading, List, Quote** — standard formatting, with bold / italic /
  underline / emoji and text alignment on the inline toolbar and block menu
- **Image** — uploads to `assets/images/uploads/`. Each image can optionally be
  a clickable link, or a lightbox (click to enlarge on the frontend)
- **Gallery** — multiple images shown as a responsive grid; clicking one opens a
  lightbox with next/previous navigation through the set
- **Video** — paste a YouTube or Vimeo link
- **Button** — a call-to-action with primary / secondary styling
- **Columns** — multi-column layouts (other blocks can be nested inside)
- **Form** — inserts any form file from `/forms/`
- **Author** — inserts an author tile (managed under **Authors**)
- **Code** — a monospace code block with an optional language label, for
  documentation and tutorials
- **Download** — upload a document (PDF, Word, Excel, PowerPoint, ZIP) and
  present it as a download button showing the file name and size
- **Section Start / Section End** — wrap a group of blocks in a coloured
  background band, using a preset colour or a specific hex value

Each page also has SEO fields (meta description, keywords) and a **Social
Share Image** for link previews on X, Facebook, LinkedIn, etc. (recommended
size 1200 × 630px).

Pages can also optionally display their **publish date** on the frontend — tick
"Show publish date on the page" beneath the Date field. It shows in a readable
long format and is ideal for blog posts and news. Off by default.

### Hero banners

Each page has optional **Hero Image**, **Hero Title**, and **Hero Subtitle**
fields (below the page settings, above the content editor). When a hero image
is set, it displays as a full-width banner at the top of the page with the
title and subtitle overlaid. Leave the image blank for no banner. The ideal
image size depends on your frontend design — a wide landscape image works best.

### Authors

The **Authors** link in the admin lets you create reusable author tiles
(name, title, description, URL, and a 100 × 100px picture). Insert them into
any page with the Author block. Editing an author updates every page that
references them.

### Forms

Any `.php` file placed in `/forms/` automatically appears in the editor's
Form block. Forms post to `/inc/formHandler.php`, which emails all submitted
fields to your configured recipient, includes spam (honeypot) protection, and
redirects to the form's `redirect` value. A `contact.php` example is included.

### Files

The **Files** link in the admin lists everything you've uploaded — images as
a thumbnail grid, documents in a list — each with a delete button. Deleting is
permanent and doesn't check whether a file is still used, so a warning is
shown before you confirm. Uploads are automatically de-duplicated: uploading
the same file twice reuses the existing copy rather than storing a duplicate.

---

## 7. Verify clean URLs

Visit a non-home page (e.g. `https://your-site.com/about`). If it loads, URL
rewriting is working. If you get a "Not Found" server error instead of the
SonaCMS page, `mod_rewrite` may be disabled or `.htaccess` overrides may not
be permitted — check with your host, or ensure `AllowOverride All` is set for
your directory in the Apache config.

---

## Customising (for developers)

These files are yours to edit and **survive CMS upgrades**:

- `inc/nav.php` — navigation markup and logic
- `inc/footer.php` — frontend footer
- `inc/404.php` — the "page not found" page
- `inc/formHandler.php` — form processing
- `forms/*.php` — form files; any `.php` here appears in the editor's Form
  block automatically
- `css/*.css` — all frontend styling
- `js/lightbox.js` — frontend lightbox behaviour
- `SonaCMS/config.php` — configuration

Everything inside `SonaCMS/app/` is core and may be overwritten when you
upgrade, so avoid editing files there.

---

## Troubleshooting

**"Could not save the page/author" / permission denied** — revisit step 2;
the `assets/` folders aren't writable by the web server user.

**Pages other than home give a server 404** — `mod_rewrite` / `.htaccess`
isn't active (step 7).

**A stylesheet or editor tool doesn't load** — check the file actually
uploaded to the expected path; a request for a missing file gets routed
through `index.php` and returns the wrong content type.

**Form emails don't arrive** — `mail()` is unreliable on many hosts; switch
to SMTP (step 4) and check your spam folder.

**Editor menu text looks garbled (e.g. on some Linux systems)** — this is a
system font-substitution issue (a broken "Helvetica" alias), not a SonaCMS
bug. Installing standard fonts (`sudo apt install fonts-liberation`) resolves
it. It does not affect published pages or other systems.