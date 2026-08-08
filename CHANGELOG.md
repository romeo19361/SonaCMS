# Changelog

All notable changes to SonaCMS are documented here.

---

## [2.4] — 8 August 2026

### Added

- **One-click site backup.** A new manager-only Backup screen downloads a
  complete copy of the entire site as a single zip — pages, uploads, settings,
  and the CMS itself. Because SonaCMS has no database, that download is
  everything needed to restore: unzip it onto any PHP host and the site is back.
  A plain-English `HANDOVER.txt` is included in the zip so any developer can
  restore it, with or without you — no lock-in.

  The backup is built to a temporary file and streamed, so it works reliably on
  large sites (galleries, PDFs) rather than timing out. It's restricted to the
  manager and requires a valid session — the archive contains configuration and
  account data, so it's never reachable without logging in.

### Upgrade notes

Add `SonaCMS/app/backup.php`, and replace `SonaCMS/app/admin.php` (adds the
Backup link) and `SonaCMS/app/css/styles.css`. Requires PHP's Zip extension
(`ZipArchive`) — standard on almost all hosts; if it's missing, the backup
screen says so clearly. No config changes required.

---

## [2.3] — 4 August 2026

### Added

- **Inline PDF block.** A new block displays a PDF directly in the page, using
  the browser's built-in viewer (page thumbnails, zoom, download, print — all
  supplied by the browser). Upload a PDF or paste a link, set an optional
  height, and it renders in place. On devices that don't support inline PDFs
  (many mobile browsers), it falls back gracefully to a tidy "open the PDF"
  link rather than a blank frame. Available to all editors.

### Upgrade notes

Add `SonaCMS/vendor/pdf-tool.js`, and replace `SonaCMS/app/editor.php`,
`SonaCMS/app/functions.php`, and both stylesheets (`css/styles.css` and
`SonaCMS/app/css/styles.css`). No config changes required.

---

## [2.2] — 4 August 2026

### Added

- **A gentle licensing note in the admin.** When SonaCMS is running unlicensed
  (`'licensed' => false`), a soft notice now appears at the top of the admin
  dashboard, explaining the free evaluation/education/not-for-profit terms and
  how to license it for commercial use. It's non-blocking — just a reminder —
  and disappears entirely once `'licensed' => true`. Education and not-for-profit
  users can get in touch to have it cleared.

### Changed

- Licensing wording is now consistent across the admin, footer, and
  `config-sample.php`, and explains that a license supports ongoing development
  and includes direct developer support.

### Upgrade notes

Replace `SonaCMS/app/admin.php` and `SonaCMS/app/css/styles.css`, and update
`config-sample.php`. No config changes required — the notice keys off your
existing `'licensed'` setting.

---

## [2.1] — 28 July 2026

### Added

**Embed code block (manager only)**
A new block lets the site manager paste a third-party widget snippet — a
Booking.com property widget, a reservation or signup widget, and so on — which
then renders on the page. Because it outputs raw code, it's restricted to the
manager: editors can't add or change it, enforced on the server (not just
hidden in the interface). This is the general way to embed any provider without
a bespoke block for each.

**Branded admin**
The SonaCMS logo now appears on the login screen and top-left of every admin
screen (and links back to the dashboard).

**Site timezone**
A new `timezone` config value sets the timezone used for all dates (activity
log, publish dates, etc.), so times show in the site's local zone rather than
the server's. Defaults to `UTC` if not set.

### Security

- **The data folder is now locked down.** SonaCMS ships a `.htaccess` in
  `assets/content/` denying all direct web access, so the accounts file
  (`users.json`, which holds password hashes), the activity log, and page data
  can't be requested from a browser. After upgrading, confirm a request to
  `/assets/content/users.json` returns 403. On nginx, add the equivalent
  location block (see INSTALL). Images and downloads in `assets/images` and
  `assets/files` stay public.

### Changed

- `functions.php` is now self-guarding (like `paths.php`), so both are safe to
  include more than once per request.

### Upgrade notes

Replace `SonaCMS/app/` and `SonaCMS/vendor/` together, plus the SonaCMS
`index.php` (login) and both stylesheets. Add the new `.htaccess` to
`assets/content/`, and add `'timezone' => 'Your/Zone'` to your `config.php`
(e.g. `Australia/Sydney`). The logo lives at `SonaCMS/app/images/SonaCMS.svg` —
include it in your deploy.

---

## [2.0] — 27 July 2026

The big one: SonaCMS goes multi-user. Until now a site had a single shared
login. 2.0 makes SonaCMS something you can hand to a team — with per-user
accountability — while keeping the flat-file, no-database simplicity that's the
whole point.

### Added

**Multi-user accounts**
- The **site manager** is the account in `config.php` (your `admin_email` /
  `admin_password_hash`). It's the super-user and a "break-glass" account — it
  always works and can't be locked out or deleted through the app. One manager
  per site.
- **Editors** are added through a new manager-only **Users** screen (name,
  email, password). They can log in and edit pages, authors and files, but
  can't manage users or view the activity log. Passwords are hashed
  (`password_hash`), stored in `assets/content/users.json`.
- Remove an editor and their access is gone immediately — no shared password to
  change for everyone else.

**Activity log**
A manager-only **Activity** screen records who created, updated, or deleted
which page, and when. Flat-file (`assets/content/activity.log`), newest-first.

**Concurrent-edit warning**
If you open a page a colleague opened in the last few minutes, the editor shows
a gentle advisory ("⚠️ Jane opened this page 3 minutes ago…"). It never blocks
you — it just helps avoid two people unknowingly overwriting each other.

### Changed

- The admin login is unchanged for existing sites: your `config.php` manager
  account keeps working exactly as before, now as the "manager" role.
- `paths.php` is now self-guarding (safe to include more than once per request).

### Upgrade notes

This is a drop-in upgrade — **no config changes required**. Your existing
`admin_email` / `admin_password_hash` becomes the manager account automatically.

Replace `SonaCMS/app/` and `SonaCMS/vendor/` together, the SonaCMS `index.php`
(login), and both stylesheets (`css/styles.css` and `SonaCMS/app/css/styles.css`).
New core files this release: `users.php`, `activity.php`, `edit-markers.php`,
`users-admin.php`, `activity-log.php` (all in `app/`). The `users.json`,
`activity.log`, and `edit-markers/` are created automatically under
`assets/content/` on first use — make sure that folder is writable (it already
is if pages save).

---

## [1.9] — 26 July 2026

### Added

**Link text to a file (inline tool)**
A new inline toolbar button lets you select text in a paragraph and link it to
a PDF (or other document) — either uploading a new file or pasting a URL. The
link opens the file in the browser (it doesn't force a download), which
complements the Download block's "save the file" behaviour. Ideal for
"see our [scorecard]" style links.

**Four image sizes**
The image block's size options are now Small, Medium, Large, and Very Large
(replacing the previous three), giving finer control over how prominently an
image sits in the content. Existing images are unaffected.

### Fixed

- **Navigation:** a parent page whose children are all hidden from the menu no
  longer renders an empty dropdown (previously showed as a thin sliver on
  hover, with a stray caret). The menu now counts only visible children.
- **Slugs & filenames:** page slugs and filenames are now cleaned automatically
  on save — lowercased, spaces converted to hyphens, and other characters
  stripped — so "About Us" becomes "about-us" without an error. This keeps URLs
  consistent and avoids case-sensitivity issues on Linux servers.

### Upgrade notes

Upgrade `SonaCMS/app/` and `SonaCMS/vendor/` together as a pair (new inline tool
`filelink-tool.js` is in `vendor/`). Also replace your frontend `css/styles.css`
and the admin `SonaCMS/app/css/styles.css`. If you've customised `inc/nav.php`,
apply the navigation fix to your copy rather than overwriting it. Your
`config.php` and customisations in `/inc/`, `/css/`, `/forms/` are never touched
by an upgrade.

---

## [1.8] — 24 July 2026

### Added

**Feature tiles**
A new Tile block: a card with a coloured icon circle, heading, text, and a
coloured accent bar — chosen from a curated icon set with per-tile circle and
accent colours. The whole tile can link to a URL. Drop several into a Columns
block for a row of feature tiles.

**Pricing cards**
A new Pricing Card block for membership/pricing plans: a coloured header bar,
an optional diagonal corner ribbon ("Popular" etc.), a prominent price, a
checkmark feature list (add/remove), a call-to-action button, and a coloured
accent bar. Place two per row in a Columns block; stack two rows for four
plans. Header, ribbon, and accent colours are all configurable.

**Google Map block**
Embed a Google Map by pasting the "Embed a map" link (Share → Embed a map →
Copy HTML). The editor validates the link and warns clearly if a plain share
link is used instead (which Google blocks from embedding).

**Facebook feed block**
Embed a Facebook Page feed by pasting your page URL. (Note: the Facebook plugin
loads Meta's SDK and may change over time — that's outside the site's control.)

**Full-width coloured sections**
Coloured sections now span the full width of the browser window, with their
content still constrained to the site's content width — matching the "full
bleed band" look common on modern sites.

**Multi-column blog listings**
`renderBlogList()` takes an optional fourth argument for the number of columns,
e.g. `renderBlogList('news', 3, false, 3)` for a 3-across homepage feed or
`renderBlogList('news', 10, true, 2)` for a 2-column paginated index. Cards
become vertical (image on top) in multi-column layouts and stack on mobile.

### Changed

- The header block now offers **H1** as a heading level (H2 remains the
  default). Use one H1 per page for good SEO — typically the page's main title.

### Upgrade notes

**Remember to upgrade `SonaCMS/app/` and `SonaCMS/vendor/` together as a pair.**
New editor tools this release: `tile-tool.js`, `pricing-tool.js`, `map-tool.js`,
`facebook-tool.js` (in `vendor/`). Also replace your frontend `css/styles.css`
and the admin `SonaCMS/app/css/styles.css`. Your `config.php` and your
customisations in `/inc/`, `/css/`, `/forms/` are never touched by an upgrade.

---

## [1.7] — 23 July 2026

### Added

**Automatic image resizing on upload**
Large images (from a phone or camera) are now resized down on upload so their
longest edge is at most 1800px, and re-compressed for the web — dramatically
reducing page weight, especially in galleries. Images already within that size
are stored untouched, so previously-optimised images aren't re-compressed (no
quality loss on re-uploads or imports). Requires PHP's GD extension; if GD
isn't available, the original is stored unchanged.

**Image sizing**
Each image now has a Small / Medium / Full size control in the block, so you
can display an image at 25%, 50%, or 100% width. Sizes scale sensibly on
mobile.

**Image alignment**
Each image now has a Left / Centre / Right alignment control in the block.
Combined with a Small or Medium size, you can position an image neatly within
the content (a full-width image fills the column, so alignment applies once an
image is smaller than full width). Both size and alignment preview live in the
editor.

### Changed

- Image size and alignment are set in the image block itself (previously
  alignment used the shared tune). Existing images default to full width,
  left-aligned, so nothing changes until you adjust them.

### Upgrade notes

**From now on, always upgrade the `SonaCMS/app/` and `SonaCMS/vendor/` folders
together as a pair.** Features increasingly span both (PHP in `app/`, editor
tools in `vendor/`), and replacing only one can leave them mismatched. Your
`config.php` and your customisations in `/inc/`, `/css/`, `/forms/` are never
touched by an upgrade.

If upgrading from 1.6:

1. Replace **both** `SonaCMS/app/` and `SonaCMS/vendor/` (adds image resizing,
   sizing and alignment).
2. Replace your frontend `css/styles.css` and the admin
   `SonaCMS/app/css/styles.css`.
3. Confirm PHP's GD extension is enabled for image resizing (`php -m | grep -i
   gd`). Without it, uploads still work — images just aren't resized.

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