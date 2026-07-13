# SonaCMS

A flat-file PHP CMS. No database — pages and blog posts are stored as plain JSON files on disk.

**[sonacms.com](https://sonacms.com)** — download, docs, and a live demo.

## Why

Most CMSs make you set up and maintain a database before you can publish a single page. SonaCMS skips that step entirely. Content lives as readable JSON files, so backups are a `cp`, there's no migration step between versions, and there's one less service to secure and keep patched.

## Features (v1.1)

- **Flat-file storage** — pages and posts saved as JSON, no database required
- **Page & post editor** — built on [Editor.js](https://editorjs.io/), with custom block tools self-hosted (no third-party CDN dependencies)
- **SEO out of the box** — dynamic sitemap, canonical URL support, Open Graph image upload
- **Forms with email delivery** — built-in mail handling via PHPMailer, with SMTP2GO support
- **Self-hosted** — deploy to any PHP-capable host over SSH/SFTP, no managed database service needed

## Requirements

- PHP 8.0+
- A web server (Apache/Nginx) with write access to the content directory
- No database

## Installation

1. Download the latest release from [sonacms.com](https://sonacms.com) or clone this repo.
2. Upload to your server via SSH/SFTP.
3. Set folder permissions on `assets/content/` and `assets/uploads/` so PHP can write to them.
4. Visit your domain and follow the setup wizard to create your admin account.

Full install and configuration docs: [sonacms.com/docs](https://sonacms.com)

## Project structure

```
/inc/           developer-customisable includes
/css/           site styling
/forms/         form handlers
config.php      site configuration
/SonaCMS/app/   upgradeable core (don't edit directly)
```

## Licensing

SonaCMS is **source-available, not open source**. The code here is public for transparency, audit, and customisation, but production use requires a paid licence:

- **Single-Site Licence — $49** — one production domain
- **Annual Developer Licence — $199/yr** — unlimited production instances, includes updates during the active subscription

See [LICENCE.md](./LICENCE.md) for full terms, and [THIRD-PARTY-LICENCES.md](./THIRD-PARTY-LICENCES.md) for bundled open-source components.

## Support

Questions or issues: open a GitHub issue, or reach out via [sonacms.com](https://sonacms.com).