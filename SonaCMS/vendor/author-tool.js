/**
 * AuthorTool — custom Editor.js Block Tool for inserting an author tile
 * into page content.
 *
 * The block only stores WHICH author to show (their filename identifier).
 * The actual tile markup is built server-side by the frontend renderer from
 * the author's JSON, so tile styling/structure stays in one place and out
 * of the saved content.
 *
 * The dropdown is populated from a global the page sets:
 *   window.SONA_AUTHORS = [{ filename: 'jane-smith', name: 'Jane Smith' }, ...];
 * (editor.php injects this via getAllAuthors().)
 *
 * Register in your EditorJS config:
 *   tools: { author: AuthorTool }
 *
 * Saved block data shape:
 *   { "type": "author", "data": { "authorId": "jane-smith" } }
 */
class AuthorTool {
    static get toolbox() {
        return {
            title: 'Author',
            icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6" stroke="currentColor" stroke-width="2" fill="none"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            authorId: (data && data.authorId) ? data.authorId : ''
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-author-tool');

        const authors = Array.isArray(window.SONA_AUTHORS) ? window.SONA_AUTHORS : [];

        if (authors.length === 0) {
            const notice = document.createElement('p');
            notice.classList.add('cms-author-tool__notice');
            notice.textContent = 'No authors found. Add one under Authors first.';
            this.wrapper.appendChild(notice);
            return this.wrapper;
        }

        const label = document.createElement('label');
        label.classList.add('cms-author-tool__label');
        label.textContent = 'Insert author:';

        const select = document.createElement('select');
        select.classList.add('cms-author-tool__select');

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '— Choose an author —';
        select.appendChild(placeholder);

        authors.forEach((author) => {
            const opt = document.createElement('option');
            opt.value = author.filename;
            opt.textContent = author.name || author.filename;
            if (author.filename === this.data.authorId) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });

        select.addEventListener('change', () => {
            this.data.authorId = select.value;
            this._renderPreview(authors);
        });

        this.wrapper.appendChild(label);
        this.wrapper.appendChild(select);

        this._previewEl = document.createElement('div');
        this._previewEl.classList.add('cms-author-tool__preview');
        this.wrapper.appendChild(this._previewEl);

        this._renderPreview(authors);

        return this.wrapper;
    }

    _renderPreview(authors) {
        if (!this._previewEl) return;
        if (this.data.authorId) {
            const match = authors.find((a) => a.filename === this.data.authorId);
            const name = match ? (match.name || match.filename) : this.data.authorId;
            this._previewEl.textContent = 'Author tile for "' + name + '" will appear here on the published page.';
        } else {
            this._previewEl.textContent = '';
        }
    }

    save() {
        return {
            authorId: this.data.authorId
        };
    }

    validate(savedData) {
        return savedData.authorId !== '';
    }
}