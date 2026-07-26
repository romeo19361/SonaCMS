/**
 * FileLinkTool — an Editor.js INLINE tool that links the selected text to a
 * PDF (or other document). The author selects text, clicks the toolbar button,
 * and either uploads a new file or types/pastes a URL; the selection becomes an
 * <a> pointing at that file. No `download` attribute is added, so a PDF opens
 * in the browser's viewer rather than force-downloading (the requested
 * behaviour, and the complement to the Download block which forces a save).
 *
 * Inline tools differ from block tools:
 *   - render()      returns the toolbar button
 *   - surround()    wraps / unwraps the current selection when clicked
 *   - checkState()  reflects whether the selection is already our link
 *
 * Uploads reuse the existing /SonaCMS/upload-file.php endpoint, which returns
 * { success: 1, file: { url, name, size } }.
 *
 * Register as an INLINE tool:  filelink: { class: FileLinkTool }
 * and add 'filelink' to each block's inlineToolbar array (or inlineToolbar:true
 * includes all registered inline tools).
 */
class FileLinkTool {
    static get isInline() {
        return true;
    }

    static get title() {
        return 'Link to file';
    }

    static get sanitize() {
        // Preserve our anchor (and its attributes) when Editor.js sanitises.
        return {
            a: {
                href: true,
                class: true,
                target: true,
                rel: true
            }
        };
    }

    constructor({ api, config }) {
        this.api = api;
        this.config = config || {};
        // Endpoint that accepts a file upload and returns { file: { url } }.
        // RELATIVE path — the editor is served from /SonaCMS/app/, so this
        // resolves to /SonaCMS/app/upload-file.php, the same endpoint the
        // Download block uses. (An absolute path would be wrong and 404.)
        this.uploadUrl = this.config.uploadUrl || 'upload-file.php';
        this.button = null;
        this.state = false;
        this.tag = 'A';
        this.class = 'cms-filelink';
        this.savedRange = null;
    }

    render() {
        this.button = document.createElement('button');
        this.button.type = 'button';
        this.button.classList.add('ce-inline-tool');
        this.button.innerHTML =
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none">'
            + '<path d="M10 13a5 5 0 007 0l2-2a5 5 0 00-7-7l-1 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            + '<path d="M14 11a5 5 0 00-7 0l-2 2a5 5 0 007 7l1-1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            + '</svg>';
        return this.button;
    }

    /**
     * Called when the toolbar button is clicked with a text selection active.
     * If the selection is already our link, unwrap it; otherwise prompt for a
     * file (upload or URL) and wrap it.
     */
    surround(range) {
        if (!range) return;

        // If already wrapped in our link, toggle it off.
        const existing = this._findAnchor(range);
        if (existing) {
            this._unwrap(existing);
            return;
        }

        // Preserve the selection — opening the panel/file dialog can lose it.
        this.savedRange = range.cloneRange();
        this._openPanel();
    }

    /**
     * Reflect whether the current selection sits inside one of our links, so
     * the toolbar button shows an active state.
     */
    checkState() {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) {
            this.state = false;
        } else {
            const anchor = this._findAnchor(sel.getRangeAt(0));
            this.state = !!anchor;
        }
        if (this.button) {
            this.button.classList.toggle('ce-inline-tool--active', this.state);
        }
        return this.state;
    }

    // ---- internal helpers -------------------------------------------------

    _findAnchor(range) {
        let node = range.commonAncestorContainer;
        if (node.nodeType === Node.TEXT_NODE) node = node.parentNode;
        while (node && node !== document) {
            if (node.tagName === this.tag && node.classList.contains(this.class)) {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    }

    _unwrap(anchor) {
        const parent = anchor.parentNode;
        while (anchor.firstChild) parent.insertBefore(anchor.firstChild, anchor);
        parent.removeChild(anchor);
    }

    _wrapSelectionWith(url) {
        if (!this.savedRange) return;
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(this.savedRange);

        const a = document.createElement('a');
        a.href = url;
        a.className = this.class;
        // No `download` attribute -> browser opens PDFs inline. Open in a new
        // tab so the reader keeps their place on the page.
        a.target = '_blank';
        a.rel = 'noopener';

        try {
            this.savedRange.surroundContents(a);
        } catch (e) {
            // surroundContents throws if the range partially selects a node;
            // fall back to extract + wrap.
            const contents = this.savedRange.extractContents();
            a.appendChild(contents);
            this.savedRange.insertNode(a);
        }
        sel.removeAllRanges();
        this.savedRange = null;
    }

    _openPanel() {
        // Minimal inline panel: upload a file, or paste a URL.
        const wrap = document.createElement('div');
        wrap.className = 'cms-filelink-panel';
        wrap.innerHTML =
            '<div class="cms-filelink-panel__row">'
            + '<button type="button" class="cms-filelink-panel__upload">Upload PDF</button>'
            + '<span class="cms-filelink-panel__or">or</span>'
            + '<input type="text" class="cms-filelink-panel__url" placeholder="paste a file URL and press Enter">'
            + '</div>'
            + '<div class="cms-filelink-panel__status"></div>';

        // Position near the selection
        const rect = this.savedRange.getBoundingClientRect();
        wrap.style.position = 'absolute';
        wrap.style.top = (window.scrollY + rect.bottom + 8) + 'px';
        wrap.style.left = (window.scrollX + rect.left) + 'px';
        wrap.style.zIndex = '10000';
        document.body.appendChild(wrap);
        this.panel = wrap;

        const status = wrap.querySelector('.cms-filelink-panel__status');
        const urlInput = wrap.querySelector('.cms-filelink-panel__url');
        const uploadBtn = wrap.querySelector('.cms-filelink-panel__upload');

        // URL path
        urlInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = urlInput.value.trim();
                if (val) {
                    this._wrapSelectionWith(val);
                    this._closePanel();
                }
            }
        });

        // Upload path
        uploadBtn.addEventListener('click', () => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.pdf,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip';
            input.addEventListener('change', () => {
                if (!input.files || !input.files[0]) return;
                status.textContent = 'Uploading\u2026';
                const fd = new FormData();
                fd.append('file', input.files[0]);
                fetch(this.uploadUrl, { method: 'POST', body: fd })
                    .then((r) => {
                        // Surface HTTP-level problems (404/500/auth redirect)
                        // instead of failing opaquely when the body isn't JSON.
                        return r.text().then((text) => {
                            let json = null;
                            try { json = JSON.parse(text); } catch (e) { /* not JSON */ }
                            if (!r.ok || !json) {
                                throw new Error(
                                    'Server returned ' + r.status + ' '
                                    + (json && json.message ? json.message : '(unexpected response)')
                                );
                            }
                            return json;
                        });
                    })
                    .then((res) => {
                        if (res && res.success && res.file && res.file.url) {
                            this._wrapSelectionWith(res.file.url);
                            this._closePanel();
                        } else {
                            status.textContent = (res && res.message) ? res.message : 'Upload failed.';
                        }
                    })
                    .catch((err) => { status.textContent = err.message || 'Upload failed.'; });
            });
            input.click();
        });

        // Close panel on outside click
        this._outsideHandler = (ev) => {
            if (this.panel && !this.panel.contains(ev.target)) {
                this._closePanel();
            }
        };
        setTimeout(() => document.addEventListener('mousedown', this._outsideHandler), 0);

        urlInput.focus();
    }

    _closePanel() {
        if (this._outsideHandler) {
            document.removeEventListener('mousedown', this._outsideHandler);
            this._outsideHandler = null;
        }
        if (this.panel) {
            this.panel.remove();
            this.panel = null;
        }
    }
}