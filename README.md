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
- **Built for SEO & AI.** Clean URLs, automatic sitemap, canonical tags, and
  per-page social share images out of the box.

---

## Features

- Block-based page editor (Editor.js) with headings, lists, quotes, images,
  video embeds, buttons, columns, forms, author tiles, code blocks, emoji and
  text alignment
- Hierarchical pages with clean URLs, ordering, and draft/published states
- Optional hero banners per page
- Images can be plain, clickable links, or click-to-enlarge lightboxes
- Reusable author profiles
- Drop-in forms with spam protection and email delivery (PHP mail or SMTP)
- Automatic XML sitemap and canonical URLs
- Per-page SEO fields and social share images

---

## Requirements

- PHP 8.0 or newer
- Apache with `mod_rewrite` (or an Nginx equivalent)

---

## Quick start

1. Download or clone this repository.
2. Copy `SonaCMS/config.sample.php` to `SonaCMS/config.php` and fill in your
   details (admin login, site URL, email settings).
3. Make these folders writable by your web server:
   `assets/content/pages/`, `assets/content/authors/`, `assets/images/uploads/`
4. Visit `/SonaCMS/` to log in and create your first page.

Full instructions are in [INSTALL.md](INSTALL.md).

---

## Licence

SonaCMS is free to download and use for **evaluation, education, and
not-for-profit** websites. A commercial licence is required to use it for
commercial projects — see [www.SonaCMS.com](https://www.sonacms.com).

See [LICENSE.md](LICENSE.md) for full terms.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.
