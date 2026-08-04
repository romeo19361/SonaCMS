/**
 * PdfTool — an inline PDF viewer block for Editor.js.
 *
 * Lets an author embed a PDF that renders IN THE PAGE using the browser's own
 * built-in PDF viewer (thumbnails, zoom, download, print — all supplied by the
 * browser). SonaCMS just outputs the embed; it does not render the PDF itself,
 * so there's no heavy PDF library to ship or maintain.
 *
 * The author uploads a PDF (or pastes a URL to one) and optionally sets a
 * display height. Frontend rendering + the mobile fallback live in functions.php.
 *
 * Uploads reuse the existing document endpoint (upload-file.php), same as the
 * Download block and the inline file-link tool. RELATIVE path so it resolves to
 * /SonaCMS/app/upload-file.php regardless of install directory.
 *
 * Saved data: { "url": "/assets/files/uploads/thing.pdf", "name": "thing.pdf", "height": 600 }
 */
class PdfTool {
    static get toolbox() {
        return {
            title: 'PDF',
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 3h7l4 4v14a0 0 0 01 0 0H7a2 2 0 01-2-2V5a2 2 0 012-2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 3v4h4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8.5 15.5h1a1 1 0 000-2h-1v4M13 13.5v4M13 13.5h1.2a1.3 1.3 0 010 2.6H13" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };
    }

    constructor({ data, config }) {
        this.data = {
            url:    (data && data.url)  ? data.url  : '',
            name:   (data && data.name) ? data.name : '',
            height: (data && data.height) ? parseInt(data.height, 10) : 600
        };
        this.config = config || {};
        this.uploadUrl = this.config.uploadUrl || 'upload-file.php';
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-pdf-tool');
        this._draw();
        return this.wrapper;
    }

    _draw() {
        this.wrapper.innerHTML = '';

        const label = document.createElement('div');
        label.classList.add('cms-pdf-tool__label');
        label.textContent = 'Inline PDF';
        this.wrapper.appendChild(label);

        if (this.data.url) {
            // Show a compact "set" state with a live preview + controls.
            const info = document.createElement('div');
            info.classList.add('cms-pdf-tool__info');
            info.textContent = this.data.name || this.data.url;
            this.wrapper.appendChild(info);

            const preview = document.createElement('iframe');
            preview.classList.add('cms-pdf-tool__preview');
            preview.src = this.data.url;
            preview.style.height = (this.data.height || 600) + 'px';
            this.wrapper.appendChild(preview);

            // Height control
            const hRow = document.createElement('div');
            hRow.classList.add('cms-pdf-tool__row');
            const hLabel = document.createElement('span');
            hLabel.textContent = 'Height (px): ';
            const hInput = document.createElement('input');
            hInput.type = 'number';
            hInput.min = '200';
            hInput.max = '2000';
            hInput.value = this.data.height || 600;
            hInput.classList.add('cms-pdf-tool__height');
            hInput.addEventListener('change', () => {
                let v = parseInt(hInput.value, 10);
                if (isNaN(v) || v < 200) v = 200;
                if (v > 2000) v = 2000;
                this.data.height = v;
                preview.style.height = v + 'px';
            });
            hRow.appendChild(hLabel);
            hRow.appendChild(hInput);

            const replaceBtn = document.createElement('button');
            replaceBtn.type = 'button';
            replaceBtn.classList.add('cms-pdf-tool__btn');
            replaceBtn.textContent = 'Replace PDF';
            replaceBtn.addEventListener('click', () => {
                this.data.url = '';
                this.data.name = '';
                this._draw();
            });
            hRow.appendChild(replaceBtn);

            this.wrapper.appendChild(hRow);
        } else {
            // Empty state: upload button + URL paste.
            const help = document.createElement('div');
            help.classList.add('cms-pdf-tool__help');
            help.textContent = 'Upload a PDF to display it in the page, or paste a link to one.';
            this.wrapper.appendChild(help);

            const row = document.createElement('div');
            row.classList.add('cms-pdf-tool__row');

            const uploadBtn = document.createElement('button');
            uploadBtn.type = 'button';
            uploadBtn.classList.add('cms-pdf-tool__btn');
            uploadBtn.textContent = 'Upload PDF';

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'application/pdf,.pdf';
            fileInput.style.display = 'none';

            const status = document.createElement('span');
            status.classList.add('cms-pdf-tool__status');

            uploadBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => {
                if (!fileInput.files || !fileInput.files[0]) return;
                status.textContent = 'Uploading\u2026';
                const fd = new FormData();
                fd.append('file', fileInput.files[0]);
                fetch(this.uploadUrl, { method: 'POST', body: fd })
                    .then((r) => {
                        return r.text().then((text) => {
                            let json = null;
                            try { json = JSON.parse(text); } catch (e) { /* not JSON */ }
                            if (!r.ok || !json) {
                                throw new Error('Server returned ' + r.status + ' '
                                    + (json && json.message ? json.message : '(unexpected response)'));
                            }
                            return json;
                        });
                    })
                    .then((res) => {
                        if (res && res.success && res.file && res.file.url) {
                            this.data.url = res.file.url;
                            this.data.name = res.file.name || '';
                            this._draw();
                        } else {
                            status.textContent = (res && res.message) ? res.message : 'Upload failed.';
                        }
                    })
                    .catch((err) => { status.textContent = err.message || 'Upload failed.'; });
            });

            row.appendChild(uploadBtn);
            row.appendChild(fileInput);
            row.appendChild(status);
            this.wrapper.appendChild(row);

            // URL paste alternative
            const urlRow = document.createElement('div');
            urlRow.classList.add('cms-pdf-tool__row');
            const urlInput = document.createElement('input');
            urlInput.type = 'text';
            urlInput.placeholder = '\u2026or paste a PDF URL and press Enter';
            urlInput.classList.add('cms-pdf-tool__url');
            urlInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const v = urlInput.value.trim();
                    if (v !== '') {
                        this.data.url = v;
                        this.data.name = v.split('/').pop() || '';
                        this._draw();
                    }
                }
            });
            urlRow.appendChild(urlInput);
            this.wrapper.appendChild(urlRow);
        }
    }

    save() {
        return {
            url:    this.data.url || '',
            name:   this.data.name || '',
            height: this.data.height || 600
        };
    }
}