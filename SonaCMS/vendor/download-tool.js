/**
 * DownloadTool — custom Editor.js Block Tool for a file download.
 *
 * Uploads a document (PDF, Word, Excel, PowerPoint, ZIP) via upload-file.php
 * and renders a styled download link/button on the frontend showing the file
 * name and size. Ideal for forms, brochures, price lists, entry forms, etc.
 *
 * Register in EditorJS config:
 *   tools: { download: DownloadTool }
 *
 * Saved data shape:
 *   { "url": "...", "name": "Entry Form.pdf", "size": 240128, "label": "" }
 * (label is an optional custom link text; falls back to the file name.)
 */
class DownloadTool {
    static get toolbox() {
        return {
            title: 'Download',
            icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3v12m0 0l-4-4m4 4l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 17v2a1 1 0 001 1h14a1 1 0 001-1v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            url: (data && data.url) ? data.url : '',
            name: (data && data.name) ? data.name : '',
            size: (data && typeof data.size === 'number') ? data.size : 0,
            label: (data && data.label) ? data.label : ''
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-download-tool');
        this._rebuild();
        return this.wrapper;
    }

    _rebuild() {
        this.wrapper.innerHTML = '';
        if (this.data.url) {
            this._renderChosen();
        } else {
            this._renderUploader();
        }
    }

    _renderUploader() {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.classList.add('cms-download-tool__upload');
        btn.textContent = 'Select a file to upload';

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip';
        fileInput.style.display = 'none';

        btn.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            btn.textContent = 'Uploading…';
            btn.disabled = true;

            fetch('upload-file.php', { method: 'POST', body: formData })
                .then((r) => r.json())
                .then((res) => {
                    if (res && res.success === 1 && res.file && res.file.url) {
                        this.data.url = res.file.url;
                        this.data.name = res.file.name || 'download';
                        this.data.size = res.file.size || 0;
                        this._rebuild();
                    } else {
                        alert((res && res.message) ? res.message : 'File upload failed.');
                        btn.textContent = 'Select a file to upload';
                        btn.disabled = false;
                    }
                })
                .catch(() => {
                    alert('File upload failed. Please try again.');
                    btn.textContent = 'Select a file to upload';
                    btn.disabled = false;
                });
        });

        this.wrapper.appendChild(btn);
        this.wrapper.appendChild(fileInput);
    }

    _renderChosen() {
        const info = document.createElement('div');
        info.classList.add('cms-download-tool__info');
        info.textContent = this.data.name + ' (' + this._humanSize(this.data.size) + ')';
        this.wrapper.appendChild(info);

        // Optional custom label
        const label = document.createElement('input');
        label.type = 'text';
        label.placeholder = 'Link text (optional) — defaults to the file name';
        label.value = this.data.label;
        label.classList.add('cms-download-tool__label');
        label.addEventListener('input', () => { this.data.label = label.value; });
        this.wrapper.appendChild(label);

        const replace = document.createElement('button');
        replace.type = 'button';
        replace.textContent = 'Replace file';
        replace.classList.add('cms-download-tool__replace');
        replace.addEventListener('click', () => {
            this.data = { url: '', name: '', size: 0, label: this.data.label };
            this._rebuild();
        });
        this.wrapper.appendChild(replace);
    }

    _humanSize(bytes) {
        if (!bytes) return '0 KB';
        const kb = bytes / 1024;
        if (kb < 1024) return Math.round(kb) + ' KB';
        return (kb / 1024).toFixed(1) + ' MB';
    }

    save() {
        return {
            url: this.data.url,
            name: this.data.name,
            size: this.data.size,
            label: this.data.label.trim()
        };
    }

    validate(savedData) {
        return savedData.url !== '';
    }
}