<?php
// /SonaCMS-V1.1/app/functions.php
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
                if ($url !== '') {
                    $html .= '<figure>';
                    $html .= '<img src="' . $url . '" alt="' . $captionAlt . '" style="max-width:100%;">';
                    if ($caption !== '') {
                        $html .= '<figcaption>' . $caption . '</figcaption>';
                    }
                    $html .= '</figure>';
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
 * Return the SonaCMS-V1.1 licensing footer text as an HTML string.
 *
 * Reads the CMS config: if 'licensed' is true, shows the registered
 * licensee's name; otherwise shows the evaluation/purchase notice.
 *
 * The returned string contains a link to www.SonaCMS-V1.1.com and is safe to
 * echo directly. The licensee name is escaped.
 *
 * @param array $config The config array (from config.php).
 * @return string
 */
function licenseFooterText(array $config): string
{
    $site = '<a href="https://www.SonaCMS-V1.1.com" rel="noopener">www.SonaCMS-V1.1.com</a>';

    if (!empty($config['licensed'])) {
        $name = htmlspecialchars($config['licensee_name'] ?? '', ENT_QUOTES);
        return 'This is a licensed version of ' . $site
            . ' registered to ' . $name . '.';
    }

    return 'This version of SonaCMS-V1.1 is for evaluation, education or '
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