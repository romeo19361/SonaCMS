/**
 * TileTool — a "feature tile" / card block for Editor.js.
 *
 * Renders a card with: a coloured circle containing a chosen icon, a heading,
 * a short paragraph, and a coloured accent bar at the bottom. Designed to be
 * dropped into a Columns block (three across) to build a row of feature tiles,
 * as commonly seen on club / small-business homepages.
 *
 * Icons come from a small curated SVG set (SONA_TILE_ICONS) — no external
 * icon font or dependency, keeping SonaCMS lightweight. Colours are chosen
 * with native colour pickers (per-tile decorative choices, so a hex picker is
 * appropriate and gives every site full freedom).
 *
 * Register: tools: { tile: TileTool }
 *
 * Saved data:
 *   {
 *     "icon": "flag",
 *     "heading": "Play a Round",
 *     "text": "...",
 *     "circleColor": "#5a5f5c",
 *     "accentColor": "#ffd200"
 *   }
 */

// Curated icon set — clean line-style SVGs (stroke-based, currentColor so the
// circle's text colour drives them). Keys are stored in the block data.
const SONA_TILE_ICONS = {
    flag:      '<path d="M6 3v18M6 4h11l-2 4 2 4H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
    ball:      '<circle cx="12" cy="10" r="7" stroke="currentColor" stroke-width="2" fill="none"/><path d="M9 20h6M12 17v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    dining:    '<path d="M6 3v8a2 2 0 004 0V3M8 11v10M16 3c-1.5 0-2 2-2 4s.5 3 2 3 2-1 2-3-.5-4-2-4zM16 14v7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
    trophy:    '<path d="M8 4h8v5a4 4 0 01-8 0V4zM8 6H5v2a3 3 0 003 3M16 6h3v2a3 3 0 01-3 3M10 15h4M9 20h6M12 15v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
    card:      '<rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="8" cy="11" r="1.5" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M13 10h5M13 13h5M6 15h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
    calendar:  '<rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M4 9h16M9 3v4M15 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    clock:     '<circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 8v4l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    phone:     '<path d="M5 4h4l2 5-3 2a11 11 0 005 5l2-3 5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none"/>',
    email:     '<rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    location:  '<path d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none"/><circle cx="12" cy="11" r="2" stroke="currentColor" stroke-width="2" fill="none"/>',
    people:    '<circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="2" fill="none"/><path d="M3 20a6 6 0 0112 0M16 6a3 3 0 010 6M18 20a6 6 0 00-3-5.2" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>',
    handshake: '<path d="M3 12l4-4 5 3 2-2 4 4M12 11l-2 2M20 8l-4 4-2-1M8 20l-4-4M4 8v8M20 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
    star:      '<path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8-5.4 2.8 1-6L3.3 9.4l6-.9z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none"/>',
    info:      '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 11v5M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    camera:    '<path d="M3 8a2 2 0 012-2h2l1.5-2h7L18 6h1a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none"/><circle cx="12" cy="12.5" r="3.5" stroke="currentColor" stroke-width="2" fill="none"/>',
    cart:      '<path d="M3 4h2l2.5 12h10l2-8H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><circle cx="9" cy="20" r="1.4" fill="currentColor"/><circle cx="17" cy="20" r="1.4" fill="currentColor"/>',
    gift:      '<rect x="4" y="9" width="16" height="11" rx="1" stroke="currentColor" stroke-width="2" fill="none"/><path d="M4 13h16M12 9v11M12 9C10 9 8 8 8 6s2-2 4 3c2-5 4-4 4-2s-2 2-4 2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" fill="none"/>',
    music:     '<path d="M9 18V5l10-2v13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="16" cy="16" r="3" stroke="currentColor" stroke-width="2" fill="none"/>',
    parking:   '<rect x="4" y="4" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2" fill="none"/><path d="M9 17V8h4a3 3 0 010 6H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
    home:      '<path d="M4 11l8-7 8 7M6 10v9h12v-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>'
};

function sonaTileIconSvg(key, size = 28) {
    const inner = SONA_TILE_ICONS[key] || SONA_TILE_ICONS.star;
    return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="none" '
        + 'xmlns="http://www.w3.org/2000/svg">' + inner + '</svg>';
}

class TileTool {
    static get toolbox() {
        return {
            title: 'Tile',
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            icon: (data && data.icon && SONA_TILE_ICONS[data.icon]) ? data.icon : 'flag',
            heading: (data && data.heading) ? data.heading : '',
            text: (data && data.text) ? data.text : '',
            circleColor: (data && data.circleColor) ? data.circleColor : '#5a5f5c',
            accentColor: (data && data.accentColor) ? data.accentColor : '#ffd200',
            url: (data && data.url) ? data.url : '',
            newTab: (data && typeof data.newTab === 'boolean') ? data.newTab : false
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-tile-tool');

        // Live preview of the circle + icon
        this.circle = document.createElement('div');
        this.circle.classList.add('cms-tile-tool__circle');
        this.circle.style.backgroundColor = this.data.circleColor;
        this.circle.innerHTML = sonaTileIconSvg(this.data.icon, 30);
        this.wrapper.appendChild(this.circle);

        // Icon picker grid
        const iconLabel = document.createElement('div');
        iconLabel.classList.add('cms-tile-tool__label');
        iconLabel.textContent = 'Icon';
        this.wrapper.appendChild(iconLabel);

        const grid = document.createElement('div');
        grid.classList.add('cms-tile-tool__icons');
        Object.keys(SONA_TILE_ICONS).forEach((key) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.classList.add('cms-tile-tool__icon-btn');
            if (key === this.data.icon) b.classList.add('cms-tile-tool__icon-btn--active');
            b.innerHTML = sonaTileIconSvg(key, 20);
            b.title = key;
            b.addEventListener('click', () => {
                this.data.icon = key;
                grid.querySelectorAll('.cms-tile-tool__icon-btn').forEach((el) =>
                    el.classList.remove('cms-tile-tool__icon-btn--active'));
                b.classList.add('cms-tile-tool__icon-btn--active');
                this.circle.innerHTML = sonaTileIconSvg(key, 30);
            });
            grid.appendChild(b);
        });
        this.wrapper.appendChild(grid);

        // Heading
        const heading = document.createElement('input');
        heading.type = 'text';
        heading.classList.add('cms-tile-tool__heading');
        heading.placeholder = 'Heading (e.g. Play a Round)';
        heading.value = this.data.heading;
        heading.addEventListener('input', () => { this.data.heading = heading.value; });
        this.wrapper.appendChild(heading);

        // Text
        const text = document.createElement('textarea');
        text.classList.add('cms-tile-tool__text');
        text.placeholder = 'Tile text';
        text.rows = 2;
        text.value = this.data.text;
        text.addEventListener('input', () => { this.data.text = text.value; });
        this.wrapper.appendChild(text);

        // Colour pickers
        const colors = document.createElement('div');
        colors.classList.add('cms-tile-tool__colors');

        colors.appendChild(this._colorControl('Circle', this.data.circleColor, (val) => {
            this.data.circleColor = val;
            this.circle.style.backgroundColor = val;
        }));
        colors.appendChild(this._colorControl('Accent bar', this.data.accentColor, (val) => {
            this.data.accentColor = val;
        }));

        this.wrapper.appendChild(colors);

        // Link URL
        const url = document.createElement('input');
        url.type = 'text';
        url.classList.add('cms-tile-tool__url');
        url.placeholder = 'Link URL (optional) \u2014 e.g. /membership or https://\u2026';
        url.value = this.data.url;
        url.addEventListener('input', () => { this.data.url = url.value.trim(); this._toggleNewTab(); });
        this.wrapper.appendChild(url);

        // Open in new tab (only relevant when a URL is set)
        this.newTabRow = document.createElement('label');
        this.newTabRow.classList.add('cms-tile-tool__newtab');
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.checked = this.data.newTab;
        cb.addEventListener('change', () => { this.data.newTab = cb.checked; });
        const cbText = document.createElement('span');
        cbText.textContent = 'Open link in a new tab';
        this.newTabRow.appendChild(cb);
        this.newTabRow.appendChild(cbText);
        this.wrapper.appendChild(this.newTabRow);

        this._toggleNewTab();

        return this.wrapper;
    }

    _toggleNewTab() {
        if (this.newTabRow) {
            this.newTabRow.style.display = this.data.url ? 'flex' : 'none';
        }
    }

    _colorControl(label, value, onChange) {
        const wrap = document.createElement('label');
        wrap.classList.add('cms-tile-tool__color');
        const span = document.createElement('span');
        span.textContent = label;
        const input = document.createElement('input');
        input.type = 'color';
        input.value = value;
        input.addEventListener('input', () => onChange(input.value));
        wrap.appendChild(span);
        wrap.appendChild(input);
        return wrap;
    }

    save() {
        return {
            icon: this.data.icon,
            heading: this.data.heading.trim(),
            text: this.data.text.trim(),
            circleColor: this.data.circleColor,
            accentColor: this.data.accentColor,
            url: this.data.url.trim(),
            newTab: this.data.newTab
        };
    }
}