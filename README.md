# SonaCMS

**The Zero-Drag Flat-File CMS.**

SonaCMS is a fast, secure, database-free content management system. Content is
stored as simple JSON files on disk — there's nothing to install, nothing to
back up, and no ongoing platform fees. Drop it on any server that runs PHP and
you have a complete, editable website.

- **No database.** Nothing to configure, nothing to hack, nothing to migrate.
- **Portable.** Runs anywhere PHP 8+ and Apache `mod_rewrite` are available.
- **Yours to shape.** The frontend ships deliberately unstyled — a clean canvas
  for designers to restyle freely, with everything you touch kept separate from
  the upgradeable core.
- **Team-ready.** Multiple user accounts with roles, an activity log, and a
  gentle concurrent-edit warning.
- **Built for SEO & AI.** Clean URLs, automatic sitemap, canonical tags, and
  per-page social share images out of the box.

---

## Features

**Editing**
- Block-based page editor (Editor.js): headings, lists, quotes, images,
  galleries, video embeds, buttons, columns, forms, author tiles, code blocks,
  emoji, and text alignment
- Layout blocks: full-width coloured sections, feature tiles, pricing cards,
  and multi-column blog/news listings
- Embeds: Google Maps and Facebook page feeds via a safe paste-a-URL pattern,
  plus a manager-only raw **Embed code** block for any third-party widget
- Inline "link to a file" tool for PDFs and documents
- Images can be plain, clickable links, or click-to-enlarge lightboxes, in four
  sizes with alignment

**Structure & content**
- Hierarchical pages with clean URLs, ordering, and draft/published states
- Optional hero banners per page
- Reusable author profiles
- Blog/news listings from any parent page, with pagination and pinned posts

**Multi-user (2.0+)**
- A manager account (set in config) plus named editor accounts managed in-app
- Two roles — editor and manager — no database, just flat files
- Activity log: who created, updated, or deleted which page, and when
- A non-blocking warning if two people open the same page at once

**Delivery & SEO**
- Drop-in forms with layered spam protection (honeypot, signed tokens, rate
  limiting, optional blocklist) and email delivery (PHP mail or SMTP)
- Automatic XML sitemap and canonical URLs
- Per-page SEO fields and social share (Open Graph) images
- Configurable site timezone

---

## Requirements

- PHP 8.0 or newer
- Apache with `mod_rewrite` (an Nginx equivalent also works)

---

## Quick start

1. Download or clone this repository.
2. Copy `SonaCMS/config-sample.php` to `SonaCMS/config.php` and fill in your
   details (admin login, site URL, timezone, email settings).
3. Make the `assets` folder writable by your web server. The simplest way is to
   set it recursively, so every sub-folder is covered in one go:

   ```bash
   chown -R youruser:www-data assets
   chmod -R 775 assets
   ```

   Replace `youruser` with your login user and `www-data` with your server's PHP
   user if different (e.g. `nginx`, `apache`, or a per-account user on shared
   hosting). Ownership is usually what matters most — see
   [INSTALL.md](INSTALL.md) for detail.
4. Confirm the data folder is protected: requesting
   `/assets/content/users.json` in a browser should return **403 Forbidden**,
   not the file. SonaCMS ships an `.htaccess` in `assets/content/` that blocks
   direct web access to accounts, the activity log, and page data. (On Nginx,
   add the equivalent `location` block — see INSTALL.)
5. Visit `/SonaCMS/` to log in and create your first page.

Full instructions are in [INSTALL.md](INSTALL.md).

---

## License

SonaCMS is free and open source under the **MIT License**. Use it for anything,
commercial sites included: change it, fork it, build on it. The only condition
is keeping the copyright notice. No license fee, nothing to register.

See [LICENSE](LICENSE) for the full text, and
[THIRD-PARTY-LICENSES.md](THIRD-PARTY-LICENSES.md) for the components SonaCMS
uses, which keep their own licenses.

Want a hand building, hosting or supporting a SonaCMS site? That's what
[Tecky](https://www.tecky.au) does.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history. Latest: **3.0**.
