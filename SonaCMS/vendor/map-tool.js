/**
 * MapTool — custom Editor.js block for embedding a Google Map.
 *
 * SAFE-EMBED PATTERN: the author only supplies a Google Maps URL. This tool
 * validates it and stores the URL; the FRONTEND renderer builds the <iframe>
 * from it. The author never pastes raw HTML, so there's no script-injection
 * risk — which matters because SonaCMS is distributed to many sites.
 *
 * Accepts either:
 *   - a Google Maps "Embed a map" iframe src (…/maps/embed?…), or
 *   - a normal Google Maps share link (google.com/maps/… or maps.app.goo.gl/…)
 * The renderer prefers a proper /maps/embed src; other Maps URLs are embedded
 * via the standard output=embed form.
 *
 * Register: tools: { map: MapTool }
 * Saved data: { "url": "https://www.google.com/maps/embed?..." }
 */
class MapTool {
    static get toolbox() {
        return {
            title: 'Google Map',
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 21s-6-5.686-6-10a6 6 0 1112 0c0 4.314-6 10-6 10z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="11" r="2" stroke="currentColor" stroke-width="2"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = { url: (data && data.url) ? data.url : '' };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-map-tool');

        const label = document.createElement('div');
        label.classList.add('cms-map-tool__label');
        label.textContent = 'Google Map';
        this.wrapper.appendChild(label);

        const input = document.createElement('input');
        input.type = 'text';
        input.classList.add('cms-map-tool__input');
        input.placeholder = 'Paste a Google Maps link or "Embed a map" URL';
        input.value = this.data.url;
        input.addEventListener('input', () => {
            this.data.url = input.value.trim();
            this._updatePreview();
        });
        this.wrapper.appendChild(input);

        const hint = document.createElement('div');
        hint.classList.add('cms-map-tool__hint');
        hint.innerHTML = 'In Google Maps: <b>Share</b> \u2192 <b>Embed a map</b> \u2192 <b>Copy HTML</b>, then paste it here. '
            + '(A normal map or \u201cSend a link\u201d share won\u2019t embed \u2014 Google blocks those.)';
        this.wrapper.appendChild(hint);

        this.preview = document.createElement('div');
        this.preview.classList.add('cms-map-tool__preview');
        this.wrapper.appendChild(this.preview);

        this._updatePreview();
        return this.wrapper;
    }

    _updatePreview() {
        const result = MapTool.toEmbedSrc(this.data.url);
        if (result.src) {
            this.preview.innerHTML = '<iframe src="' + result.src + '" width="100%" height="220" '
                + 'style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" '
                + 'allowfullscreen></iframe>';
        } else if (!this.data.url) {
            this.preview.innerHTML = '';
        } else if (result.reason === 'share') {
            // The single most common mistake: pasting a share / place link,
            // which Google blocks from embedding. Guide them to the right one.
            this.preview.innerHTML = '<div class="cms-map-tool__warn">'
                + 'That looks like a Google Maps <b>share link</b>, which Google won\u2019t allow on a '
                + 'website. Use <b>Share \u2192 Embed a map \u2192 Copy HTML</b> and paste that instead.'
                + '</div>';
        } else {
            this.preview.innerHTML = '<div class="cms-map-tool__warn">That doesn\u2019t look like a Google Maps '
                + '\u201cEmbed a map\u201d link. Use <b>Share \u2192 Embed a map \u2192 Copy HTML</b>.</div>';
        }
    }

    /**
     * Turn a user-supplied value into a safe embeddable src. ONLY accepts true
     * Google Maps EMBED URLs (…/maps/embed?…) or a pasted embed <iframe> whose
     * src is one — because those are the only URLs Google actually permits
     * inside an iframe. A share/place link (…/maps/place/…, maps.app.goo.gl,
     * goo.gl) is detected and reported so the user can grab the right link.
     *
     * Returns { src: string, reason: string }.
     *   src set        -> good, embeddable
     *   reason 'share' -> a share/place link (common mistake)
     *   reason 'other' -> not a recognised Google Maps embed
     */
    static toEmbedSrc(raw) {
        if (!raw) return { src: '', reason: '' };
        let url;
        try {
            const m = raw.match(/src=["']([^"']+)["']/i); // extract from pasted <iframe>
            url = new URL(m ? m[1] : raw);
        } catch (e) {
            return { src: '', reason: 'other' };
        }
        const host = url.hostname.toLowerCase();
        const isGoogleHost = host === 'www.google.com' || host === 'google.com'
            || host === 'maps.google.com' || host.endsWith('.google.com');

        // The only embeddable form
        if (isGoogleHost && url.pathname.includes('/maps/embed')) {
            return { src: url.href, reason: '' };
        }

        // Known share/place forms that will NOT embed
        const isShareLink =
            (isGoogleHost && url.pathname.includes('/maps'))   // /maps/place/…, /maps/@…
            || host === 'maps.app.goo.gl'
            || host === 'goo.gl';
        if (isShareLink) {
            return { src: '', reason: 'share' };
        }

        return { src: '', reason: 'other' };
    }

    save() {
        return { url: this.data.url };
    }

    validate(data) {
        return !!MapTool.toEmbedSrc(data.url).src;
    }
}