# Changelog

All notable changes to SonaCMS are documented here.

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