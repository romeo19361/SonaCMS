<?php
// /SonaCMS/app/functions.php
//
// Core helper functions for reading and writing page content.
// Relies on constants defined in paths.php — require that first.

/**
 * Return an array of all pages in the content directory.
 * Each entry includes the filename (without .json) plus the decoded JSON fields.
 *
 * @return array
 */
function getAllPages(): array
{
    $pages = [];

    if (!is_dir(PAGES_DIR)) {
        return $pages;
    }

    $files = glob(PAGES_DIR . '/*.json');

    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);

        if (!is_array($data)) {
            // Skip malformed JSON rather than letting it break the whole listing
            continue;
        }

        $data['filename'] = basename($file, '.json');
        $pages[] = $data;
    }

    return $pages;
}

/**
 * Return a single page's data by filename (without .json extension).
 *
 * @param string $filename
 * @return array|null
 */
function getPage(string $filename): ?array
{
    $path = PAGES_DIR . '/' . basename($filename) . '.json';

    if (!file_exists($path)) {
        return null;
    }

    $data = json_decode(file_get_contents($path), true);

    if (!is_array($data)) {
        return null;
    }

    $data['filename'] = basename($filename);
    return $data;
}

/**
 * Save a page's data to disk as JSON.
 *
 * @param string $filename Filename without extension, e.g. "about-us"
 * @param array $data Page fields (title, slug, content, meta_description, date, status)
 * @return bool
 */
function savePage(string $filename, array $data): bool
{
    if (!is_dir(PAGES_DIR)) {
        mkdir(PAGES_DIR, 0755, true);
    }

    // filename is metadata for listing only — don't persist it inside the JSON body
    unset($data['filename']);

    $path = PAGES_DIR . '/' . basename($filename) . '.json';

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        return false;
    }

    return file_put_contents($path, $json) !== false;
}

/**
 * Build a nested tree of pages based on each page's 'page_parent' field.
 * 'page_parent' holds the filename (without extension) of the parent page,
 * or an empty string/missing for top-level pages.
 *
 * Each page in the returned tree gets a 'children' key containing its
 * direct child pages (recursively nested the same way).
 *
 * Orphaned pages (page_parent set to a filename that doesn't exist, or a
 * circular reference) fall back to top-level rather than being dropped,
 * so content never silently disappears from the admin listing.
 *
 * @param array $pages Flat array as returned by getAllPages()
 * @param string $parentFilename The parent to build children for (empty = top-level)
 * @param array $ancestry Filenames already visited on this branch, to guard against cycles
 * @return array
 */
function buildPageTree(array $pages, string $parentFilename = '', array $ancestry = []): array
{
    $tree = [];

    foreach ($pages as $page) {
        $pageParent = $page['page_parent'] ?? '';
        $filename = $page['filename'] ?? '';

        if ($pageParent !== $parentFilename) {
            continue;
        }

        // Guard against circular parent references (A -> B -> A)
        if (in_array($filename, $ancestry, true)) {
            continue;
        }

        $page['children'] = buildPageTree($pages, $filename, array_merge($ancestry, [$filename]));
        $tree[] = $page;
    }

    // Sort siblings by page_order (ascending), defaulting to 99 if not set
    usort($tree, function (array $a, array $b): int {
        $orderA = isset($a['page_order']) ? (int)$a['page_order'] : 99;
        $orderB = isset($b['page_order']) ? (int)$b['page_order'] : 99;
        return $orderA <=> $orderB;
    });

    return $tree;
}

/**
 * Return all pages as a nested tree, ready for hierarchical display.
 * Pages whose page_parent points at a non-existent filename are treated
 * as top-level, so nothing gets lost due to a stale/broken reference.
 *
 * @return array
 */
function getPageTree(): array
{
    $pages = getAllPages();

    $validFilenames = array_column($pages, 'filename');

    foreach ($pages as &$page) {
        $parent = $page['page_parent'] ?? '';
        if ($parent !== '' && !in_array($parent, $validFilenames, true)) {
            // Parent no longer exists — treat as top-level instead of hiding it
            $page['page_parent'] = '';
        }
    }
    unset($page);

    return buildPageTree($pages);
}

/**
 * Delete a page's JSON file by filename (without extension).
 *
 * @param string $filename
 * @return bool
 */
function deletePage(string $filename): bool
{
    $path = PAGES_DIR . '/' . basename($filename) . '.json';

    if (!file_exists($path)) {
        return false;
    }

    return unlink($path);
}

/**
 * Return a page matching a full hierarchical URL path, e.g. "blog/my-post".
 * Walks all pages, builds each one's full URL via parent chain, and compares.
 *
 * @param string $path         URL path, with or without leading slash.
 * @param bool   $publishedOnly  Exclude drafts (default true for frontend).
 * @return array|null
 */
function getPageByPath(string $path, bool $publishedOnly = true): ?array
{
    $path = trim($path, '/');
    if ($path === '') $path = 'home';

    $pages = getAllPages();

    $byFilename = [];
    foreach ($pages as $p) {
        $byFilename[$p['filename']] = $p;
    }

    foreach ($pages as $page) {
        if ($publishedOnly && ($page['status'] ?? '') !== 'published') {
            continue;
        }

        $parts   = [];
        $current = $page;
        $visited = [];

        while ($current) {
            $fn = $current['filename'] ?? '';
            if (isset($visited[$fn])) break;
            $visited[$fn] = true;
            array_unshift($parts, $current['slug'] ?? '');
            $parentFn = $current['page_parent'] ?? '';
            $current  = ($parentFn !== '' && isset($byFilename[$parentFn]))
                ? $byFilename[$parentFn]
                : null;
        }

        $builtPath = implode('/', array_filter($parts));

        if ($builtPath === $path) {
            return $page;
        }
    }

    // Fall back to bare slug match for flat/legacy slugs
    foreach ($pages as $page) {
        if ($publishedOnly && ($page['status'] ?? '') !== 'published') {
            continue;
        }
        if (($page['slug'] ?? '') === $path) {
            return $page;
        }
    }

    return null;
}

/**
 * Find and return a single page by its slug field.
 * Returns null if no page with that slug exists or is published.
 *
 * @param string $slug
 * @param bool $publishedOnly  If true, draft pages are excluded (default for frontend)
 * @return array|null
 */
function getPageBySlug(string $slug, bool $publishedOnly = true): ?array
{
    return getPageByPath($slug, $publishedOnly);
}

/**
 * Render Editor.js block JSON as HTML for frontend output.
 * Handles all block types used in editor.php.
 * Returns a safe HTML string ready to echo directly into a page.
 *
 * @param string $json  The raw JSON string stored in the 'content' field
 * @return string
 */
function renderContent(string $json): string
{
    if (empty($json)) {
        return '';
    }

    $data = json_decode($json, true);

    if (!isset($data['blocks']) || !is_array($data['blocks'])) {
        // Legacy plain-text content stored before Editor.js was added
        return '<p>' . nl2br(htmlspecialchars($json)) . '</p>';
    }

    $html = '';

    // Tracks whether we're currently inside an open coloured section, so a
    // "Section End" (or the end of the page) knows to close the wrapping <div>.
    $sectionOpen = false;

    foreach ($data['blocks'] as $block) {
        $type = $block['type'] ?? '';
        $d    = $block['data'] ?? [];

        // Read the alignment block-tune, if present. Stored as
        // block.tunes.alignment.alignment = 'left'|'center'|'right'|'justify'.
        $alignment = $block['tunes']['alignment']['alignment'] ?? '';
        $alignClass = in_array($alignment, ['left', 'center', 'right', 'justify'], true)
            ? ' class="cms-align-' . $alignment . '"'
            : '';

        switch ($type) {

            case 'sectionStart':
                // Close any already-open section first (author nested/forgot an end)
                if ($sectionOpen) {
                    $html .= '</div></div>';
                    $sectionOpen = false;
                }
                $preset = $d['preset'] ?? '';
                $hex    = $d['hex'] ?? '';

                $classAttr = ' class="cms-section';
                if ($preset !== '' && preg_match('/^[a-z]+$/', $preset)) {
                    $classAttr .= ' cms-section--' . $preset;
                }
                $classAttr .= '"';

                // Hex escape hatch — only accept a valid #rgb/#rrggbb value
                $styleAttr = '';
                if ($hex !== '' && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $hex)) {
                    $styleAttr = ' style="background-color: ' . htmlspecialchars($hex, ENT_QUOTES) . ';"';
                }

                // Outer full-width band; inner keeps content aligned to site width
                $html .= '<div' . $classAttr . $styleAttr . '><div class="cms-section__inner">';
                $sectionOpen = true;
                break;

            case 'sectionEnd':
                if ($sectionOpen) {
                    $html .= '</div></div>';
                    $sectionOpen = false;
                }
                break;

            case 'paragraph':
                $html .= '<p' . $alignClass . '>' . ($d['text'] ?? '') . '</p>';
                break;

            case 'header':
                $level = (int) ($d['level'] ?? 2);
                $level = max(1, min(6, $level)); // clamp 1-6
                // Editor.js already HTML-encodes its text and may include
                // inline formatting tags (<b>, <i>, <a>). Output as-is rather
                // than re-escaping, which would double-encode entities (& -> &amp;).
                $html .= "<h{$level}{$alignClass}>" . ($d['text'] ?? '') . "</h{$level}>";
                break;

            case 'list':
                $style = ($d['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
                $html .= "<{$style}>";
                foreach ($d['items'] ?? [] as $item) {
                    // Items may be plain strings or nested objects depending on list version
                    $text = is_array($item) ? ($item['content'] ?? '') : $item;
                    $html .= '<li>' . $text . '</li>';
                }
                $html .= "</{$style}>";
                break;

            case 'quote':
                $caption = $d['caption'] ?? '';
                $html .= '<blockquote>';
                $html .= '<p>' . ($d['text'] ?? '') . '</p>';
                if ($caption !== '') {
                    $html .= '<cite>' . $caption . '</cite>';
                }
                $html .= '</blockquote>';
                break;

            case 'image':
                $url        = htmlspecialchars($d['file']['url'] ?? '');
                $caption    = $d['caption'] ?? ''; // Editor.js-encoded; raw for figcaption
                $captionAlt = htmlspecialchars(strip_tags($caption)); // safe for alt attribute
                $mode       = $d['mode'] ?? 'none';
                $linkUrl    = htmlspecialchars($d['linkUrl'] ?? '', ENT_QUOTES);
                if ($url !== '') {
                    $imgTag = '<img src="' . $url . '" alt="' . $captionAlt . '" style="max-width:100%;">';

                    // Wrap the image according to its mode:
                    //  - link:     clickable anchor to the given URL (new tab)
                    //  - lightbox: anchor flagged for the frontend lightbox script
                    //  - none:     plain image
                    if ($mode === 'link' && $linkUrl !== '') {
                        // Open in a new tab only if the author chose to. rel="noopener"
                        // is a security must whenever target="_blank" is used.
                        $newTab = !isset($d['newTab']) || $d['newTab'] === true;
                        $attrs = $newTab ? ' target="_blank" rel="noopener"' : '';
                        $inner = '<a href="' . $linkUrl . '"' . $attrs . '>' . $imgTag . '</a>';
                    } elseif ($mode === 'lightbox') {
                        $capAttr = $captionAlt !== '' ? ' data-caption="' . $captionAlt . '"' : '';
                        $inner = '<a href="' . $url . '" class="cms-lightbox" data-lightbox="1"' . $capAttr . '>' . $imgTag . '</a>';
                    } else {
                        $inner = $imgTag;
                    }

                    // Alignment and size now live in the image block's own data
                    // (not the shared tune), so the editor can preview them live.
                    $figClasses = ['cms-figure'];
                    $imgAlign = $d['align'] ?? 'left';
                    if (in_array($imgAlign, ['left', 'center', 'right'], true)) {
                        $figClasses[] = 'cms-align-' . $imgAlign;
                    }
                    $size = $d['size'] ?? 'full';
                    if (in_array($size, ['small', 'medium', 'full'], true)) {
                        $figClasses[] = 'cms-figure--' . $size;
                    }
                    $figClass = ' class="' . implode(' ', $figClasses) . '"';
                    $html .= '<figure' . $figClass . '>' . $inner;
                    if ($caption !== '') {
                        $html .= '<figcaption>' . $caption . '</figcaption>';
                    }
                    $html .= '</figure>';
                }
                break;

            case 'gallery':
                $images = (isset($d['images']) && is_array($d['images'])) ? $d['images'] : [];
                if (!empty($images)) {
                    // Unique ID groups this gallery's images so the lightbox knows
                    // which images to navigate between. Static counter = one ID per
                    // gallery block on the page.
                    static $galleryCounter = 0;
                    $galleryCounter++;
                    $gid = 'g' . $galleryCounter;

                    $html .= '<div class="cms-block cms-block--gallery"><div class="cms-gallery">';
                    $i = 0;
                    foreach ($images as $image) {
                        $imgUrl = htmlspecialchars($image['url'] ?? '', ENT_QUOTES);
                        if ($imgUrl === '') continue;
                        $cap    = htmlspecialchars(strip_tags($image['caption'] ?? ''), ENT_QUOTES);
                        $capAttr = $cap !== '' ? ' data-caption="' . $cap . '"' : '';

                        $html .= '<a class="cms-gallery__item cms-lightbox" href="' . $imgUrl . '"'
                            . ' data-gallery="' . $gid . '" data-index="' . $i . '"' . $capAttr . '>'
                            . '<img src="' . $imgUrl . '" alt="' . $cap . '" loading="lazy">'
                            . '</a>';
                        $i++;
                    }
                    $html .= '</div></div>';
                }
                break;

            case 'download':
                $fileUrl = htmlspecialchars($d['url'] ?? '', ENT_QUOTES);
                if ($fileUrl !== '') {
                    $fileName = htmlspecialchars($d['name'] ?? 'Download');
                    $label    = trim($d['label'] ?? '');
                    $linkText = htmlspecialchars($label !== '' ? $label : ($d['name'] ?? 'Download'));

                    // Human-readable size
                    $size = (int)($d['size'] ?? 0);
                    $sizeStr = '';
                    if ($size > 0) {
                        $kb = $size / 1024;
                        $sizeStr = $kb < 1024
                            ? round($kb) . ' KB'
                            : round($kb / 1024, 1) . ' MB';
                    }

                    // File extension for a small type hint
                    $ext = strtoupper(pathinfo($d['name'] ?? '', PATHINFO_EXTENSION));

                    $meta = trim($ext . ($ext && $sizeStr ? ', ' : '') . $sizeStr);
                    $metaHtml = $meta !== '' ? ' <span class="cms-download__meta">(' . htmlspecialchars($meta) . ')</span>' : '';

                    $html .= '<div class="cms-block cms-block--download">'
                        . '<a class="cms-download" href="' . $fileUrl . '" download>'
                        . '<span class="cms-download__icon" aria-hidden="true">&#8681;</span>'
                        . '<span class="cms-download__text">' . $linkText . $metaHtml . '</span>'
                        . '</a></div>';
                }
                break;

            case 'video':
                $embedUrl = htmlspecialchars($d['embedUrl'] ?? '');
                if ($embedUrl !== '') {
                    $html .= '<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;">';
                    $html .= '<iframe src="' . $embedUrl . '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture"></iframe>';
                    $html .= '</div>';
                }
                break;

            case 'button':
                $text  = htmlspecialchars($d['text'] ?? '');
                $url   = htmlspecialchars($d['url'] ?? '#');
                $style = (($d['style'] ?? 'primary') === 'secondary') ? 'secondary' : 'primary';
                if ($text !== '') {
                    $html .= '<div class="cms-block cms-block--button">'
                        . '<a href="' . $url . '" class="cms-button cms-button--' . $style . '">'
                        . $text . '</a></div>';
                }
                break;

            case 'form':
                $formId = $d['formId'] ?? '';
                if ($formId !== '') {
                    $formHtml = renderForm($formId);
                    if ($formHtml !== '') {
                        $html .= '<div class="cms-block cms-block--form">' . $formHtml . '</div>';
                    }
                }
                break;

            case 'code':
                $code = $d['code'] ?? '';
                $lang = $d['language'] ?? '';
                if (trim($code) !== '') {
                    // Code is plain text — always escape it fully. Unlike
                    // Editor.js rich-text fields, code must never be treated
                    // as HTML, or a snippet containing tags would break the page.
                    $langClass = $lang !== ''
                        ? ' class="language-' . htmlspecialchars($lang, ENT_QUOTES) . '"'
                        : '';
                    $html .= '<div class="cms-block cms-block--code">'
                        . '<pre><code' . $langClass . '>'
                        . htmlspecialchars($code, ENT_QUOTES)
                        . '</code></pre></div>';
                }
                break;

            case 'author':
                $authorId = $d['authorId'] ?? '';
                if ($authorId !== '') {
                    $a = getAuthor($authorId);
                    if ($a) {
                        $aName = htmlspecialchars($a['name'] ?? '');
                        $aTitle = htmlspecialchars($a['title'] ?? '');
                        $aDesc = htmlspecialchars($a['description'] ?? '');
                        $aUrl = htmlspecialchars($a['url'] ?? '');
                        $aPic = htmlspecialchars($a['pic'] ?? '');

                        $html .= '<div class="cms-block cms-block--author">';
                        $html .= '<div class="cms-author-tile">';
                        if ($aPic !== '') {
                            $html .= '<div class="cms-author-tile__pic"><img src="' . $aPic . '" alt="' . $aName . '"></div>';
                        }
                        $html .= '<div class="cms-author-tile__body">';
                        // Name — linked if a URL is provided
                        if ($aUrl !== '') {
                            $html .= '<a class="cms-author-tile__name" href="' . $aUrl . '" rel="noopener">' . $aName . '</a>';
                        } else {
                            $html .= '<span class="cms-author-tile__name">' . $aName . '</span>';
                        }
                        if ($aTitle !== '') {
                            $html .= '<p class="cms-author-tile__title">' . $aTitle . '</p>';
                        }
                        if ($aDesc !== '') {
                            $html .= '<p class="cms-author-tile__desc">' . $aDesc . '</p>';
                        }
                        $html .= '</div>'; // body
                        $html .= '</div>'; // tile
                        $html .= '</div>'; // block
                    }
                }
                break;

            case 'columns':
                // Recursively render each column's blocks
                $html .= '<div class="cms-columns">';
                foreach ($d['cols'] ?? [] as $col) {
                    $html .= '<div class="cms-column">';
                    $colContent = json_encode(['blocks' => $col['blocks'] ?? []]);
                    $html .= renderContent($colContent);
                    $html .= '</div>';
                }
                $html .= '</div>';
                break;

            default:
                // Unknown block type — skip silently
                break;
        }
    }

    // If the author opened a section but never added a "Section End",
    // close it here so the page HTML stays valid.
    if ($sectionOpen) {
        $html .= '</div></div>';
    }

    return $html;
}

/**
 * Return a list of available form identifiers by scanning FORMS_DIR.
 * Each entry is the filename without the .php extension, e.g. "contact"
 * for /forms/contact.php. Used to populate the form-picker dropdown in
 * the editor and to validate form references on render.
 *
 * @return array  List of form identifiers (strings).
 */
function getAvailableForms(): array
{
    if (!is_dir(FORMS_DIR)) {
        return [];
    }

    $forms = [];
    foreach (glob(FORMS_DIR . '/*.php') as $file) {
        $forms[] = basename($file, '.php');
    }
    sort($forms);
    return $forms;
}

/**
 * Render a form by identifier, if it exists in FORMS_DIR.
 * Includes the form's PHP file and returns its output as a string.
 * Returns an empty string if the form doesn't exist — never exposes a path
 * or error to the visitor.
 *
 * The identifier is sanitised with basename() to prevent directory traversal.
 *
 * @param string $formId  e.g. "contact"
 * @return string
 */
function renderForm(string $formId): string
{
    $formId = basename($formId); // strip any path traversal attempt
    $path = FORMS_DIR . '/' . $formId . '.php';

    if ($formId === '' || !file_exists($path)) {
        return '';
    }

    ob_start();
    include $path;
    return ob_get_clean();
}

/**
 * Return the SonaCMS licensing footer text as an HTML string.
 *
 * Reads the CMS config: if 'licensed' is true, shows the registered
 * licensee's name; otherwise shows the evaluation/purchase notice.
 *
 * The returned string contains a link to www.SonaCMS.com and is safe to
 * echo directly. The licensee name is escaped.
 *
 * @param array $config The config array (from config.php).
 * @return string
 */
function licenseFooterText(array $config): string
{
    $site = '<a href="https://www.SonaCMS.com" rel="noopener">www.SonaCMS.com</a>';

    if (!empty($config['licensed'])) {
        $name = htmlspecialchars($config['licensee_name'] ?? '', ENT_QUOTES);
        return 'This is a licensed version of ' . $site
            . ' registered to ' . $name . '.';
    }

    return 'This version of SonaCMS is for evaluation, education or '
        . 'not-for-profit use. Purchase a commercial license at ' . $site . '.';
}

/**
 * Return an array of all authors in the authors directory.
 * Each entry includes the filename (without .json) plus the decoded fields.
 *
 * @return array
 */
function getAllAuthors(): array
{
    $authors = [];

    if (!is_dir(AUTHORS_DIR)) {
        return $authors;
    }

    foreach (glob(AUTHORS_DIR . '/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) {
            continue; // skip malformed JSON rather than break the listing
        }
        $data['filename'] = basename($file, '.json');
        $authors[] = $data;
    }

    return $authors;
}

/**
 * Return a single author's data by filename (without .json extension).
 *
 * @param string $filename
 * @return array|null
 */
function getAuthor(string $filename): ?array
{
    $path = AUTHORS_DIR . '/' . basename($filename) . '.json';

    if (!file_exists($path)) {
        return null;
    }

    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) {
        return null;
    }

    $data['filename'] = basename($filename);
    return $data;
}

/**
 * Save an author's data to disk as JSON.
 *
 * @param string $filename Filename without extension.
 * @param array  $data     Author fields.
 * @return bool
 */
function saveAuthor(string $filename, array $data): bool
{
    if (!is_dir(AUTHORS_DIR)) {
        mkdir(AUTHORS_DIR, 0755, true);
    }

    unset($data['filename']); // don't persist the filename inside the JSON body

    $path = AUTHORS_DIR . '/' . basename($filename) . '.json';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        return false;
    }

    return file_put_contents($path, $json) !== false;
}

/**
 * Delete an author's JSON file by filename (without extension).
 *
 * @param string $filename
 * @return bool
 */
function deleteAuthor(string $filename): bool
{
    $path = AUTHORS_DIR . '/' . basename($filename) . '.json';

    if (!file_exists($path)) {
        return false;
    }

    return unlink($path);
}

/**
 * Render the contents of the page <head> — title, meta description/keywords,
 * canonical URL, and Open Graph / Twitter image tags.
 *
 * Kept as a function (rather than inline in index.php) because it's logic-heavy
 * SEO markup that shouldn't need editing per-site — every SonaCMS install gets
 * correct tags for free. Stylesheet/icon <link>s stay in the template, since
 * those are things a developer may want to change.
 *
 * @param array  $page         The current page data.
 * @param array  $config       The site config (for site_url).
 * @param string $currentPath  The request path (e.g. "/about"), for canonical.
 * @return string HTML for inside <head>.
 */
function renderPageHead(array $page, array $config, string $currentPath): string
{
    $out = '';

    $title = htmlspecialchars($page['title'] ?? '');
    $out .= '<title>' . $title . '</title>' . "\n";

    if (!empty($page['meta_description'])) {
        $out .= '    <meta name="description" content="'
            . htmlspecialchars($page['meta_description']) . '">' . "\n";
    }

    if (!empty($page['meta_keywords'])) {
        $out .= '    <meta name="keywords" content="'
            . htmlspecialchars($page['meta_keywords']) . '">' . "\n";
    }

    // Canonical base — from configured site_url, NOT the request host, so the
    // real domain gets attribution even if another domain points at this server.
    $canonicalBase = !empty($config['site_url'])
        ? rtrim($config['site_url'], '/')
        : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? ''));

    // OG / Twitter image — only when the page has one; absolute URL required.
    if (!empty($page['og_image'])) {
        $ogImageUrl = htmlspecialchars($canonicalBase . $page['og_image']);
        $out .= '    <meta property="og:image" content="' . $ogImageUrl . '">' . "\n";
        $out .= '    <meta property="og:image:width" content="1200">' . "\n";
        $out .= '    <meta property="og:image:height" content="630">' . "\n";
        $out .= '    <meta name="twitter:card" content="summary_large_image">' . "\n";
        $out .= '    <meta name="twitter:image" content="' . $ogImageUrl . '">' . "\n";
    }

    $out .= '    <link rel="canonical" href="'
        . htmlspecialchars($canonicalBase . $currentPath, ENT_QUOTES) . '">' . "\n";

    return $out;
}

/**
 * Render the hero banner for a page, or an empty string if no hero image is set.
 * The title and subtitle overlay the image. Deliberately minimal markup so
 * designers can restyle freely via .site-hero in the frontend CSS.
 *
 * @param array $page The current page data.
 * @return string
 */
function renderHero(array $page): string
{
    if (empty($page['hero_image'])) {
        return '';
    }

    $img = htmlspecialchars($page['hero_image'], ENT_QUOTES);

    $out  = '<section class="site-hero" style="background-image: url(\'' . $img . '\');">' . "\n";
    $out .= '    <div class="site-hero__overlay">' . "\n";

    if (!empty($page['hero_title'])) {
        $out .= '        <h1 class="site-hero__title">'
            . htmlspecialchars($page['hero_title']) . '</h1>' . "\n";
    }
    if (!empty($page['hero_subtitle'])) {
        $out .= '        <p class="site-hero__subtitle">'
            . htmlspecialchars($page['hero_subtitle']) . '</p>' . "\n";
    }

    $out .= '    </div>' . "\n";
    $out .= '</section>' . "\n";

    return $out;
}

/**
 * Render a page's publish date in long format (e.g. "19 July 2026"), wrapped in
 * a <time> element with a machine-readable datetime attribute for SEO.
 *
 * Returns an empty string unless the page has "show_date" enabled and a valid
 * date — so the developer can safely call this anywhere in their template and
 * it simply outputs nothing when the author hasn't opted in.
 *
 * @param array $page The current page data.
 * @return string
 */
function renderPublishDate(array $page): string
{
    if (empty($page['show_date']) || empty($page['date'])) {
        return '';
    }

    $ts = strtotime($page['date']);
    if ($ts === false) {
        return '';
    }

    $iso  = date('Y-m-d', $ts);
    $long = date('j F Y', $ts); // e.g. 19 July 2026

    return '<p class="page-date"><time datetime="' . htmlspecialchars($iso, ENT_QUOTES) . '">'
        . htmlspecialchars($long) . '</time></p>';
}

/**
 * renderBlogList — outputs a chronological list of "blog" posts.
 *
 * A blog is simply a normal parent page (e.g. slug "blog", "news", "my-blog")
 * whose published child pages are the posts. This lists those children,
 * newest first by their `date` field, as linked cards showing the social
 * image, title, date, and meta description (as an excerpt).
 *
 * Reuses fields the author already fills in (og_image, meta_description, date)
 * so posts need no extra "blog-specific" data entry.
 *
 * Usage:
 *   renderBlogList('news')            // all posts, no pagination
 *   renderBlogList('news', 3)         // 3 latest, no pagination (e.g. homepage)
 *   renderBlogList('news', 10, true)  // 10 per page, with ?page=N controls
 *
 * @param string   $parentSlug The slug of the blog/news parent page.
 * @param int|null $limit      Max posts to show (per page if paginating). Null = all.
 * @param bool     $paginate   Whether to show ?page=N pagination controls.
 * @return string
 */
function renderBlogList(string $parentSlug, ?int $limit = null, bool $paginate = false): string
{
    $allPages = getAllPages();

    // Find the parent page by slug
    $parent = null;
    foreach ($allPages as $p) {
        if (($p['slug'] ?? '') === $parentSlug) {
            $parent = $p;
            break;
        }
    }
    if (!$parent) {
        return '<p class="cms-bloglist__empty">No posts found.</p>';
    }

    // Collect published children of that parent
    $parentFilename = $parent['filename'] ?? '';
    $posts = [];
    foreach ($allPages as $p) {
        if (($p['page_parent'] ?? '') === $parentFilename
            && ($p['status'] ?? '') === 'published') {
            $posts[] = $p;
        }
    }

    if (empty($posts)) {
        return '<p class="cms-bloglist__empty">No posts yet.</p>';
    }

    // Sort newest-first by date (fall back to 0 for missing/invalid dates)
    usort($posts, function ($a, $b) {
        $da = !empty($a['date']) ? strtotime($a['date']) : 0;
        $db = !empty($b['date']) ? strtotime($b['date']) : 0;
        return $db <=> $da;
    });

    $totalPosts = count($posts);
    $totalPages = 1;
    $currentPage = 1;

    if ($paginate && $limit && $limit > 0) {
        $totalPages = (int) ceil($totalPosts / $limit);
        $currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $limit;
        $posts = array_slice($posts, $offset, $limit);
    } elseif ($limit && $limit > 0) {
        // Non-paginated limit (e.g. homepage "latest 3")
        $posts = array_slice($posts, 0, $limit);
    }

    // Build the cards
    $html = '<div class="cms-bloglist">';
    foreach ($posts as $post) {
        $url   = htmlspecialchars(buildPageUrl($post, $allPages), ENT_QUOTES);
        $title = htmlspecialchars($post['title'] ?? 'Untitled');
        $image = $post['og_image'] ?? '';
        $desc  = trim($post['meta_description'] ?? '');

        $dateHtml = '';
        if (!empty($post['date']) && strtotime($post['date']) !== false) {
            $ts = strtotime($post['date']);
            $dateHtml = '<time class="cms-bloglist__date" datetime="' . date('Y-m-d', $ts) . '">'
                . date('j F Y', $ts) . '</time>';
        }

        $html .= '<a class="cms-bloglist__card" href="' . $url . '">';

        if ($image !== '') {
            $html .= '<div class="cms-bloglist__image">'
                . '<img src="' . htmlspecialchars($image, ENT_QUOTES) . '" alt="' . $title . '" loading="lazy">'
                . '</div>';
        }

        $html .= '<div class="cms-bloglist__body">';
        $html .= '<h3 class="cms-bloglist__title">' . $title . '</h3>';
        $html .= $dateHtml;
        if ($desc !== '') {
            $html .= '<p class="cms-bloglist__excerpt">' . htmlspecialchars($desc) . '</p>';
        }
        $html .= '</div>'; // body
        $html .= '</a>';   // card
    }
    $html .= '</div>'; // bloglist

    // Pagination controls
    if ($paginate && $totalPages > 1) {
        // Preserve the current path, vary only the page query
        $path = strtok($_SERVER['REQUEST_URI'], '?');
        $html .= '<nav class="cms-bloglist__pagination">';
        if ($currentPage > 1) {
            $html .= '<a class="cms-bloglist__pagelink" href="' . htmlspecialchars($path . '?page=' . ($currentPage - 1), ENT_QUOTES) . '">&larr; Newer</a>';
        }
        $html .= '<span class="cms-bloglist__pageinfo">Page ' . $currentPage . ' of ' . $totalPages . '</span>';
        if ($currentPage < $totalPages) {
            $html .= '<a class="cms-bloglist__pagelink" href="' . htmlspecialchars($path . '?page=' . ($currentPage + 1), ENT_QUOTES) . '">Older &rarr;</a>';
        }
        $html .= '</nav>';
    }

    return $html;
}