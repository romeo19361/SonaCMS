/**
 * FacebookTool — custom Editor.js block for embedding a Facebook Page feed.
 *
 * SAFE-EMBED PATTERN: the author only supplies their Facebook PAGE URL. This
 * tool validates it's a facebook.com URL and stores it; the FRONTEND renderer
 * builds the official Page Plugin markup. No raw HTML from the author.
 *
 * NOTE (honest caveats, worth telling site owners):
 *   - The Page Plugin loads Facebook's SDK, which adds third-party tracking
 *     and slows the page a little.
 *   - Facebook periodically changes these embeds; a feed that works today can
 *     need attention later if Meta changes the plugin. That's outside the
 *     site's control.
 *
 * Register: tools: { facebook: FacebookTool }
 * Saved data: { "url": "https://www.facebook.com/YourPage", "height": 500 }
 */
class FacebookTool {
    static get toolbox() {
        return {
            title: 'Facebook Feed',
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 8h2V5h-2c-1.7 0-3 1.3-3 3v2H9v3h2v6h3v-6h2l1-3h-3V8z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            url: (data && data.url) ? data.url : '',
            height: (data && data.height) ? data.height : 500
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-fb-tool');

        const label = document.createElement('div');
        label.classList.add('cms-fb-tool__label');
        label.textContent = 'Facebook Page Feed';
        this.wrapper.appendChild(label);

        const input = document.createElement('input');
        input.type = 'text';
        input.classList.add('cms-fb-tool__input');
        input.placeholder = 'Your Facebook page URL, e.g. https://www.facebook.com/YourClub';
        input.value = this.data.url;
        input.addEventListener('input', () => { this.data.url = input.value.trim(); this._updateState(); });
        this.wrapper.appendChild(input);

        const hint = document.createElement('div');
        hint.classList.add('cms-fb-tool__hint');
        hint.textContent = 'Paste the address of your Facebook page. The latest posts will show on the published page.';
        this.wrapper.appendChild(hint);

        this.state = document.createElement('div');
        this.state.classList.add('cms-fb-tool__state');
        this.wrapper.appendChild(this.state);

        this._updateState();
        return this.wrapper;
    }

    _updateState() {
        const ok = FacebookTool.isValidPageUrl(this.data.url);
        if (!this.data.url) {
            this.state.textContent = '';
        } else if (ok) {
            // The live feed can't render inside the editor without loading
            // Facebook's SDK, so show a confirmation rather than the real feed.
            this.state.innerHTML = '<div class="cms-fb-tool__ok">\u2713 Facebook page set \u2014 the feed will appear on the published page.</div>';
        } else {
            this.state.innerHTML = '<div class="cms-fb-tool__warn">That doesn\u2019t look like a facebook.com page URL.</div>';
        }
    }

    static isValidPageUrl(raw) {
        if (!raw) return false;
        let url;
        try { url = new URL(raw); } catch (e) { return false; }
        const host = url.hostname.toLowerCase();
        return host === 'www.facebook.com' || host === 'facebook.com'
            || host === 'm.facebook.com' || host.endsWith('.facebook.com')
            || host === 'fb.com' || host === 'fb.me';
    }

    save() {
        return { url: this.data.url, height: this.data.height };
    }

    validate(data) {
        return FacebookTool.isValidPageUrl(data.url);
    }
}