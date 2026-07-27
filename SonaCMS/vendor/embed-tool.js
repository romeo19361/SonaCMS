/**
 * EmbedCodeTool — a raw HTML/JS embed block for Editor.js.
 *
 * PURPOSE: lets the site MANAGER paste an official third-party widget snippet
 * (Booking.com property widget, a booking/reservation widget, a mailing-list
 * signup, etc.) that renders on the page. This is the general answer to "embed
 * any third-party service" — rather than building a bespoke safe-embed block
 * per provider.
 *
 * SECURITY MODEL — read carefully:
 *   - This block outputs RAW HTML/JS, so it is intentionally restricted to the
 *     MANAGER role. The manager is the site owner / host (a config-level,
 *     break-glass account) who already has full server/SFTP access — so letting
 *     them paste code grants no capability they don't already have. It is NOT a
 *     privilege escalation for them.
 *   - EDITORS must never be able to create or alter this block. That is enforced
 *     TWO ways: (1) this tool is only registered for the manager (the editor
 *     never sees it in the toolbar), and (2) — the part that actually matters —
 *     the SERVER refuses to accept embed-block content on save from a non-manager
 *     (see editor.php). UI hiding alone is not security; the server check is.
 *
 * The tool is passed `config.canEdit` (true only for the manager). When false,
 * the block renders read-only so an editor viewing a page that already contains
 * an embed can't tamper with it.
 *
 * Saved data: { "html": "<script>...</script>" }
 */
class EmbedCodeTool {
    static get toolbox() {
        return {
            title: 'Embed code',
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M8 9l-4 3 4 3M16 9l4 3-4 3M13 6l-2 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data, config }) {
        this.data = { html: (data && typeof data.html === 'string') ? data.html : '' };
        // canEdit is true only for the manager. Defaults to false (safe) if the
        // host page didn't pass it — so the block is never editable by accident.
        this.canEdit = !!(config && config.canEdit);
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-embed-tool');

        const label = document.createElement('div');
        label.classList.add('cms-embed-tool__label');
        label.textContent = 'Embed code (manager only)';
        this.wrapper.appendChild(label);

        if (!this.canEdit) {
            // Read-only view for editors: show that an embed exists, but don't
            // expose an editable field. The content is preserved on save by the
            // server regardless.
            const ro = document.createElement('div');
            ro.classList.add('cms-embed-tool__readonly');
            ro.textContent = this.data.html
                ? 'An embedded widget is set here by the site manager. You can\u2019t edit it, and it will be kept as-is when you save.'
                : 'Embed code blocks can only be added by the site manager.';
            this.wrapper.appendChild(ro);
            return this.wrapper;
        }

        const warn = document.createElement('div');
        warn.classList.add('cms-embed-tool__warn');
        warn.innerHTML = '\u26a0\ufe0f Only paste code from services you trust '
            + '(e.g. Booking.com, a booking widget). This runs on your live page exactly as pasted.';
        this.wrapper.appendChild(warn);

        const ta = document.createElement('textarea');
        ta.classList.add('cms-embed-tool__input');
        ta.placeholder = 'Paste the widget\u2019s embed code here (e.g. the snippet from Booking.com).';
        ta.rows = 6;
        ta.value = this.data.html;
        ta.addEventListener('input', () => { this.data.html = ta.value; });
        this.wrapper.appendChild(ta);

        return this.wrapper;
    }

    save() {
        // Editors' read-only render has no input; return the original html so an
        // editor's save preserves (never strips) the manager's embed. The server
        // independently enforces that editors can't CHANGE it.
        return { html: this.data.html };
    }
}