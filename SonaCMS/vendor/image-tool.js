/**
 * ImageTool (custom) — SonaCMS image block.
 *
 * Replaces the third-party @editorjs/image tool so we can add per-image
 * behaviour: a plain image, a clickable link, or a lightbox (click to enlarge
 * on the frontend). Uploads go through the same upload.php endpoint as before.
 *
 * Backwards compatible: blocks saved by the old @editorjs/image tool use the
 * shape { file: { url }, caption }. This tool reads that same shape and simply
 * adds optional `mode` and `linkUrl` fields, so existing images load fine and
 * default to "none" mode.
 *
 * Register in EditorJS config:
 *   tools: { image: { class: ImageTool } }
 *
 * Saved data shape:
 *   { "file": { "url": "..." }, "caption": "...",
 *     "mode": "none" | "link" | "lightbox", "linkUrl": "https://…" }
 */
class ImageTool {
    static get toolbox() {
        return {
            title: 'Image',
            icon: '<svg width="17" height="15" viewBox="0 0 24 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="20" height="16" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="8" cy="8" r="2" fill="currentColor"/><path d="M4 16l5-5 4 4 3-3 4 4" stroke="currentColor" stroke-width="2" fill="none"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            file: (data && data.file) ? data.file : { url: '' },
            caption: (data && data.caption) ? data.caption : '',
            mode: (data && ['none', 'link', 'lightbox'].includes(data.mode)) ? data.mode : 'none',
            linkUrl: (data && data.linkUrl) ? data.linkUrl : ''
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-image-tool');
        this._rebuild();
        return this.wrapper;
    }

    _rebuild() {
        this.wrapper.innerHTML = '';

        if (this.data.file && this.data.file.url) {
            this._renderUploaded();
        } else {
            this._renderUploader();
        }
    }

    _renderUploader() {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.classList.add('cms-image-tool__upload');
        btn.textContent = 'Select an image';

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';

        btn.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            btn.textContent = 'Uploading…';
            btn.disabled = true;

            fetch('upload.php', { method: 'POST', body: formData })
                .then((r) => r.json())
                .then((res) => {
                    if (res && res.success === 1 && res.file && res.file.url) {
                        this.data.file = { url: res.file.url };
                        this._rebuild();
                    } else {
                        alert('Image upload failed. Please try a different file.');
                        btn.textContent = 'Select an image';
                        btn.disabled = false;
                    }
                })
                .catch(() => {
                    alert('Image upload failed. Please try again.');
                    btn.textContent = 'Select an image';
                    btn.disabled = false;
                });
        });

        this.wrapper.appendChild(btn);
        this.wrapper.appendChild(fileInput);
    }

    _renderUploaded() {
        // Preview
        const img = document.createElement('img');
        img.src = this.data.file.url;
        img.classList.add('cms-image-tool__preview');
        this.wrapper.appendChild(img);

        // Caption
        const caption = document.createElement('input');
        caption.type = 'text';
        caption.placeholder = 'Caption (optional)';
        caption.value = this.data.caption;
        caption.classList.add('cms-image-tool__caption');
        caption.addEventListener('input', () => { this.data.caption = caption.value; });
        this.wrapper.appendChild(caption);

        // Mode selector: None / Link / Lightbox
        const modeRow = document.createElement('div');
        modeRow.classList.add('cms-image-tool__modes');

        const modes = [
            ['none', 'No action'],
            ['link', 'Clickable link'],
            ['lightbox', 'Lightbox (enlarge)']
        ];

        modes.forEach(([value, label]) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = label;
            b.classList.add('cms-image-tool__mode-btn');
            if (this.data.mode === value) b.classList.add('cms-image-tool__mode-btn--active');
            b.addEventListener('click', () => {
                this.data.mode = value;
                modeRow.querySelectorAll('.cms-image-tool__mode-btn').forEach((el) =>
                    el.classList.remove('cms-image-tool__mode-btn--active'));
                b.classList.add('cms-image-tool__mode-btn--active');
                this._toggleLinkField();
            });
            modeRow.appendChild(b);
        });
        this.wrapper.appendChild(modeRow);

        // Link URL field — only visible in "link" mode
        this.linkField = document.createElement('input');
        this.linkField.type = 'text';
        this.linkField.placeholder = 'https://example.com';
        this.linkField.value = this.data.linkUrl;
        this.linkField.classList.add('cms-image-tool__link');
        this.linkField.addEventListener('input', () => { this.data.linkUrl = this.linkField.value.trim(); });
        this.wrapper.appendChild(this.linkField);

        // "Replace image" button
        const replace = document.createElement('button');
        replace.type = 'button';
        replace.textContent = 'Replace image';
        replace.classList.add('cms-image-tool__replace');
        replace.addEventListener('click', () => {
            this.data.file = { url: '' };
            this._rebuild();
        });
        this.wrapper.appendChild(replace);

        this._toggleLinkField();
    }

    _toggleLinkField() {
        if (this.linkField) {
            this.linkField.style.display = (this.data.mode === 'link') ? 'block' : 'none';
        }
    }

    save() {
        return {
            file: this.data.file,
            caption: this.data.caption.trim(),
            mode: this.data.mode,
            linkUrl: this.data.linkUrl.trim()
        };
    }

    validate(savedData) {
        return savedData.file && savedData.file.url !== '';
    }
}