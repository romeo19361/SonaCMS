<?php
// /inc/nav.php
//
// Site navigation functions — this is the developer's space to customise
// how menus are rendered. Edit freely; it lives outside /SonaCMS-V1.1 so it
// survives CMS upgrades.
//
// Requires SonaCMS-V1.1 core to already be loaded (paths.php + functions.php),
// since it calls getPageTree() / getAllPages() from the CMS.

/**
 * Build a full URL path for a page by walking up its parent chain.
 * e.g. a page with slug "my-post" whose parent has slug "blog"
 * returns "/blog/my-post".
 *
 * "home" always returns "/" regardless of nesting.
 *
 * @param array $page     The page to build a URL for.
 * @param array $allPages Flat array of all pages (from getAllPages()), used
 *                        to look up parent slugs by filename.
 * @return string
 */
function buildPageUrl(array $page, array $allPages): string
{
    $slug = $page['slug'] ?? '';

    if ($slug === 'home' || $slug === '') {
        return '/';
    }

    // Index flat pages by filename for quick parent lookup
    $byFilename = [];
    foreach ($allPages as $p) {
        $byFilename[$p['filename']] = $p;
    }

    // Walk up the parent chain collecting slugs, guarding against cycles
    $parts    = [];
    $current  = $page;
    $visited  = [];

    while ($current) {
        $fn = $current['filename'] ?? '';
        if (isset($visited[$fn])) break; // circular reference guard
        $visited[$fn] = true;

        array_unshift($parts, $current['slug'] ?? '');

        $parentFilename = $current['page_parent'] ?? '';
        $current = ($parentFilename !== '' && isset($byFilename[$parentFilename]))
            ? $byFilename[$parentFilename]
            : null;
    }

    return '/' . implode('/', array_filter($parts));
}

/**
 * navigationA — recursive ul/li navigation with nested ul for child pages.
 *
 * Renders only pages where show_in_nav is true.
 * Uses nav_label if set, falls back to title, then filename.
 * Adds a class of "has-children" to any li that contains sub-pages.
 * Adds a class of "active" to the li whose full URL matches the current path.
 * URLs are built hierarchically: /parent-slug/child-slug
 *
 * @param array       $pages       Page tree array as returned by getPageTree().
 * @param int         $depth       Current nesting depth — 0 = top level.
 * @param string|null $currentPath Current URL path for active detection.
 * @param array       $allPages    Flat page list for URL building — auto-loaded
 *                                 on first call if not provided.
 * @return string HTML string — echo it directly in your template.
 */
function navigationA(array $pages, int $depth = 0, ?string $currentPath = null, array $allPages = []): string
{
    // Auto-detect current path on first call
    if ($currentPath === null) {
        $currentPath = '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        if ($currentPath === '/') $currentPath = '/';
    }

    // Load flat page list once on first call for URL building
    if ($depth === 0 && empty($allPages)) {
        $allPages = getAllPages();
    }

    // On the first call, wrap everything in the nav shell: a hidden checkbox
    // (the CSS-only toggle), a hamburger label, an overlay, and the list.
    if ($depth === 0) {
        $html  = '<nav class="nav-a">' . "\n";
        // Hidden checkbox drives the mobile drawer open/closed state (no JS)
        $html .= '<input type="checkbox" id="nav-a-toggle" class="nav-a__toggle" aria-hidden="true">' . "\n";
        // Hamburger button (label tied to the checkbox)
        $html .= '<label for="nav-a-toggle" class="nav-a__hamburger" aria-label="Toggle menu">'
            . '<span></span><span></span><span></span>'
            . '</label>' . "\n";
        // Dark overlay behind the drawer — tapping it closes the menu
        $html .= '<label for="nav-a-toggle" class="nav-a__overlay" aria-hidden="true"></label>' . "\n";
        $html .= '<ul class="nav-a__list nav-a__list--top">' . "\n";
    } else {
        // Child list (only ever depth 1 — deeper nesting is intentionally
        // flattened, since the spec caps display at parent > child).
        $html = '<ul class="nav-a__submenu">' . "\n";
    }

    foreach ($pages as $page) {
        // Skip pages explicitly hidden from navigation
        if (isset($page['show_in_nav']) && $page['show_in_nav'] === false) {
            continue;
        }

        $label = !empty($page['nav_label'])
            ? $page['nav_label']
            : ($page['title'] ?? $page['filename']);

        $href        = buildPageUrl($page, $allPages);
        // Only treat children as a submenu at the top level — deeper levels
        // are not rendered as further flyouts (spec: two levels max).
        $hasChildren = ($depth === 0) && !empty($page['children']);
        $isActive    = ($href === $currentPath);

        $liClasses = ['nav-a__item'];
        if ($hasChildren) $liClasses[] = 'has-children';
        if ($isActive)    $liClasses[] = 'active';

        $html .= '<li class="' . implode(' ', $liClasses) . '">' . "\n";
        $html .= '<a class="nav-a__link" href="' . htmlspecialchars($href, ENT_QUOTES) . '">'
            . htmlspecialchars($label, ENT_QUOTES)
            . '</a>' . "\n";

        if ($hasChildren) {
            $html .= navigationA($page['children'], $depth + 1, $currentPath, $allPages);
        }

        $html .= '</li>' . "\n";
    }

    $html .= $depth === 0 ? '</ul></nav>' . "\n" : '</ul>' . "\n";

    return $html;
}