# Changelog

All notable changes to SonaCMS are documented here.

---

## [1.6] — 21 July 2026

### Added

**File manager**
A new "Files" area in the admin lists everything you've uploaded — images
shown as a thumbnail grid, documents in a list — each with a delete button.
Deleting is permanent and does not check whether a file is still in use, so a
clear warning is shown; this keeps the tool simple and lets you remove a file
for any reason (including compliance), even if it's currently used somewhere.

**Automatic image & file de-duplication**
Uploads are now content-addressed: each file is stored under a name derived
from a hash of its contents. Uploading the same image or document again reuses
the existing file instead of saving a duplicate — so reusing a logo or hero
image across many pages no longer fills your uploads folder with copies. No
database or clean-up process needed; it simply never creates the duplicate.

### Changed

- Page **slugs and filenames are now saved in lowercase**, so URLs behave
  consistently on case-sensitive (Linux) servers — no more `/About` and
  `/about` resolving to different pages. Existing pages keep their current
  values until re-saved.

### Upgrade notes

If you're upgrading from 1.5:

1. Replace the files in `SonaCMS/app/` (adds `files.php`, `file-delete.php`;
   updates `upload.php`, `upload-file.php`, `editor.php`, `paths.php`,
   `admin.php`) and the admin `SonaCMS/app/css/styles.css`.
2. No content or config changes are required. De-duplication applies to new
   uploads only; existing files are left as they are.
3. Your `config.php` and customisations in `/inc/`, `/css/`, `/forms/` are
   untouched by upgrades — as always.

---

## [1.5] — 21 July 2026

### Added

**Coloured sections**
New "Section Start" and "Section End" blocks let you wrap a group of blocks in
a coloured background band — ideal for highlighting offers, announcements, or
call-to-action areas. Choose from preset colours (Subtle, Muted, Highlight,
Accent, Dark) that map to CSS classes you can restyle to your brand, or enter a
specific hex colour. Dark sections automatically use light text.

**File downloads**
A new "Download" block lets you upload a document (PDF, Word, Excel,
PowerPoint, or ZIP) and present it as a tidy download button showing the file
name, type, and size. Uploaded documents are stored separately from images
under `assets/files/uploads/`, and both upload areas are protected against
executing scripts.

**Blog / news listings**
A new `renderBlogList()` function outputs a chronological list of posts — where
a "blog" is simply any parent page whose published child pages are the posts.
Each entry shows the social image, title, date, and meta description as an
excerpt, all reusing fields you already fill in. Supports a post limit (e.g. a
"latest 3" feed on the homepage) and optional `?page=N` pagination for a full
index.

**Sample config file**
SonaCMS now ships with `config-sample.php` instead of a ready-made
`config.php`. Rename it to `config.php` on first install. This means an upgrade
can never overwrite your real configuration, and the distributable never
carries anyone's live credentials.

### Fixed

**Contact form submissions silently dropped**
The spam-protection honeypot was a hidden text field, which some browsers'
autofill would populate with a stray value (e.g. a town or email) — causing
genuine submissions to be mistaken for spam and silently discarded. The
honeypot is now a hidden checkbox, which autofill leaves alone, so real
submissions always get through while bots are still caught. If you use a custom
form, update its honeypot field to a checkbox named `contact_time`.

### Upgrade notes

If you're upgrading from 1.4:

1. Replace `index.php`, and the files in `SonaCMS/app/` and `SonaCMS/vendor/`
   (new tools: `section-tool.js`, `download-tool.js`).
2. Replace your frontend `css/styles.css` and the admin `SonaCMS/app/css/styles.css`.
3. For file downloads: create `assets/files/uploads/` (writable by the web
   server) and place the hardening `.htaccess` in `assets/files/`.
4. Update your contact form and `inc/formHandler.php` for the checkbox honeypot
   (see Fixed, above).
5. Your `config.php` and customisations in `/inc/`, `/css/`, `/forms/` are
   untouched by upgrades — as always.

---

## [1.4] — 20 July 2026

### Added

**Photo galleries**
A new Gallery block holds multiple images in one block, shown as a tidy,
responsive grid on the frontend. In the editor you can add several images at
once, give each an optional caption, reorder them, and remove them individually.

**Gallery-aware lightbox**
Clicking any gallery image opens a full-screen lightbox with previous/next
navigation through that gallery — by clicking the arrows or using the keyboard
(left/right to move, Escape to close). Captions display beneath each image.
Standalone lightbox images (from the regular image block) continue to work as
before, now with caption support too. The lightbox remains vanilla JavaScript
with no external library.

**Optional publish date**
Pages now have a "Show publish date on the page" checkbox beneath the Date
field. When enabled, the date displays in a readable long format (e.g.
"20 July 2026") wrapped in a semantic `<time>` element for SEO — ideal for blog
posts and news. Off by default, so ordinary pages stay clean.

### Changed

- The frontend lightbox script (`/js/lightbox.js`) has been upgraded to support
  gallery navigation. If you're upgrading, replace this file.
- `index.php` now calls a `renderPublishDate()` helper above the content. It
  outputs nothing unless a page opts in, and developers can move the call
  anywhere in the template.

### Upgrade notes

If you're upgrading from 1.3:

1. Replace `js/lightbox.js` (now gallery-aware).
2. Replace `index.php`, and the files in `SonaCMS/app/` and `SonaCMS/vendor/`
   (the new gallery tool is `SonaCMS/vendor/gallery-tool.js`).
3. Replace your frontend `css/styles.css` (adds gallery, upgraded lightbox, and
   publish-date styles) and the admin `SonaCMS/app/css/styles.css`.
4. Your customisations in `/inc/`, `/css/`, `/forms/` and `config.php` are
   untouched by upgrades — as always.

---

## [1.3] — 19 July 2026

### Added

**Hero banners**
Pages now have optional Hero Image, Hero Title, and Hero Subtitle fields. When a
hero image is set, it renders as a full-width banner at the top of the page with
the title and subtitle overlaid. Leave the image blank and the page displays as
before, with no banner. The banner is fully responsive and, like everything on
the frontend, styled with plain CSS you can restyle freely (`.site-hero`).

**Clickable and lightbox images**
The image block now offers a per-image choice: a plain image, a clickable link
(opens a URL you specify), or a lightbox (clicking the image enlarges it in a
full-screen overlay on the frontend). Link-mode images include an "Open in new
tab" toggle, so you control whether the link opens in the same tab or a new one.
The lightbox is vanilla JavaScript with no external library.

### Changed

- The image block is now a self-hosted tool rather than a third-party CDN
  package — one less external dependency. Existing images are unaffected and
  continue to display normally.
- The frontend lightbox script now lives in its own file at `/js/lightbox.js`.
- `index.php` is tidier: the SEO `<head>` tags and hero banner are now generated
  by helper functions (`renderPageHead()` and `renderHero()`), leaving the page
  template cleaner and easier to customise.

### Upgrade notes

If you're upgrading from 1.1:

1. Create a `/js/` folder in your web root and upload `js/lightbox.js`.
2. Replace `index.php`, and the files in `SonaCMS/app/` and `SonaCMS/vendor/`
   (the image tool is now `SonaCMS/vendor/image-tool.js`).
3. Replace your frontend `css/styles.css` (adds hero and lightbox styles) and
   the admin `SonaCMS/app/css/styles.css`.
4. Your customisations in `/inc/`, `/css/`, `/forms/` and `config.php` are
   untouched by upgrades — as always.

---

## [1.1] — 11 July 2026

### Added

**Automatic XML sitemap**
SonaCMS now generates an SEO sitemap dynamically from your published pages,
served at `/sitemap.xml`. It builds hierarchical URLs from your page tree,
excludes drafts automatically, and includes `<lastmod>` dates — so it stays
accurate with no manual maintenance. Submit it to Google Search Console and
forget about it.

**Canonical URLs**
Every page now outputs a `<link rel="canonical">` tag built from a new
`site_url` config value. This tells search engines which domain owns your
content, even if the site is reachable through another domain pointing at the
same server — preventing duplicate-content issues and making sure your real
domain gets the credit. Open Graph image URLs and the sitemap use the same
canonical base.

**Code block in the editor**
A new Code block for the page editor, with an optional language label. Preserves
indentation and whitespace exactly, escapes content safely, and renders inside
`<pre><code>` on the frontend. Built for documentation and tutorial pages.

**robots.txt**
Ships with a sensible default: allows crawling of public pages, blocks the
admin and internal directories, and points crawlers at the sitemap.

### Changed

- `.htaccess` now includes a rewrite rule serving `/sitemap.xml` from the
  sitemap generator.
- Font stacks no longer lead with `Helvetica`. On some Linux systems Helvetica
  is aliased to a font with mismapped glyphs, which could render editor menu
  labels as garbled text. Stacks now start with Arial, which resolves correctly
  everywhere.

### Upgrade notes

If you're upgrading from 1.0:

1. Add `site_url` to `SonaCMS/config.php` — your site's canonical address, with
   no trailing slash. For example: `'site_url' => 'https://www.yoursite.com',`
2. Upload the new `sitemap.php` and `robots.txt` to your web root.
3. Add the sitemap rewrite rule to your `.htaccess`, **above** the existing
   `RewriteCond` lines:
   `RewriteRule ^sitemap\.xml$ sitemap.php [L]`
4. Replace `index.php`, and the files in `SonaCMS/app/` and `SonaCMS/vendor/`.
5. Your customisations in `/inc/`, `/css/`, `/forms/` and `config.php` are
   untouched by upgrades — as always.

---

## [1.0] — 9 July 2026

Initial public release.

A flat-file CMS with no database. Content is stored as JSON files, so there's
nothing to install, nothing to back up, and nothing to pay for month after month.

**Content management**
- Block-based page editor built on Editor.js
- Hierarchical pages with parent/child nesting and clean URLs
- Page ordering and show/hide control for navigation menus
- Draft and published states
- Reusable author tiles, managed separately and inserted anywhere

**Editor blocks**
Text, headings, lists, quotes, images, video embeds, buttons, multi-column
layouts, forms, and author tiles — plus inline emoji and text alignment.

**Forms**
Drop any PHP form file into `/forms/` and it appears in the editor automatically.
A generic handler emails submissions to you, with honeypot spam protection and
automatic switching between PHP `mail()` and SMTP (e.g. SMTP2GO).

**SEO and sharing**
Per-page meta descriptions and keywords, plus per-page social share images for
link previews on X, Facebook, LinkedIn and elsewhere.

**Built to be restyled**
The frontend ships deliberately unstyled. Everything a developer touches —
navigation, footer, 404 page, form handling, and all CSS — lives outside the
CMS core and survives upgrades.