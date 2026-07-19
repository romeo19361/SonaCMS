/**
 * GalleryTool — custom Editor.js Block Tool for a photo gallery.
 *
 * Holds multiple images in one block, rendered as a responsive grid on the
 * frontend. Clicking any image opens the shared lightbox and lets the visitor
 * navigate next/previous through the gallery.
 *
 * Editor UI: add images (each uploaded via upload.php), reorder with
 * move-left/move-right buttons, remove individually, and give each an optional
 * caption shown in the lightbox.
 *
 * Register in EditorJS config:
 *   tools: { gallery: GalleryTool }
 *
 * Saved data shape:
 *   { "images": [ { "url": "...", "caption": "..." }, ... ] }
 */
class GalleryTool {
    static get toolbox() {
        return {
            title: 'Gallery',
            icon: '<svg width="18" height="16" viewBox="0 0 24 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="2" width="9" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="13" y="2" width="9" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="2" y="11" width="9" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="13" y="11" width="9" height="7" rx="1" stroke="currentColor" stroke-width="2"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            images: (data && Array.isArray(data.images)) ? data.images.slice() : []
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-gallery-tool');
        this._rebuild();
        return this.wrapper;
    }

    _rebuild() {
        this.wrapper.innerHTML = '';

        // Grid of current images
        if (this.data.images.length > 0) {
            const grid = document.createElement('div');
            grid.classList.add('cms-gallery-tool__grid');

            this.data.images.forEach((image, index) => {
                grid.appendChild(this._renderTile(image, index));
            });

            this.wrapper.appendChild(grid);
        }

        // "Add images" button
        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.classList.add('cms-gallery-tool__add');
        addBtn.textContent = this.data.images.length ? '+ Add more images' : '+ Add images';

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.multiple = true; // allow selecting several at once
        fileInput.style.display = 'none';

        addBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => this._handleFiles(fileInput.files, addBtn));

        this.wrapper.appendChild(addBtn);
        this.wrapper.appendChild(fileInput);
    }

    _renderTile(image, index) {
        const tile = document.createElement('div');
        tile.classList.add('cms-gallery-tool__tile');

        const img = document.createElement('img');
        img.src = image.url;
        tile.appendChild(img);

        // Caption input
        const caption = document.createElement('input');
        caption.type = 'text';
        caption.placeholder = 'Caption (optional)';
        caption.value = image.caption || '';
        caption.classList.add('cms-gallery-tool__caption');
        caption.addEventListener('input', () => { this.data.images[index].caption = caption.value; });
        tile.appendChild(caption);

        // Controls row: move left, move right, remove
        const controls = document.createElement('div');
        controls.classList.add('cms-gallery-tool__controls');

        const left = document.createElement('button');
        left.type = 'button';
        left.innerHTML = '&larr;';
        left.title = 'Move left';
        left.disabled = (index === 0);
        left.addEventListener('click', () => this._move(index, index - 1));

        const right = document.createElement('button');
        right.type = 'button';
        right.innerHTML = '&rarr;';
        right.title = 'Move right';
        right.disabled = (index === this.data.images.length - 1);
        right.addEventListener('click', () => this._move(index, index + 1));

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.innerHTML = '&times;';
        remove.title = 'Remove';
        remove.classList.add('cms-gallery-tool__remove');
        remove.addEventListener('click', () => {
            this.data.images.splice(index, 1);
            this._rebuild();
        });

        controls.appendChild(left);
        controls.appendChild(right);
        controls.appendChild(remove);
        tile.appendChild(controls);

        return tile;
    }

    _move(from, to) {
        if (to < 0 || to >= this.data.images.length) return;
        const moved = this.data.images.splice(from, 1)[0];
        this.data.images.splice(to, 0, moved);
        this._rebuild();
    }

    _handleFiles(fileList, addBtn) {
        const files = Array.from(fileList);
        if (files.length === 0) return;

        addBtn.textContent = 'Uploading…';
        addBtn.disabled = true;

        // Upload sequentially so order is predictable and we don't hammer the server
        const uploadNext = (i) => {
            if (i >= files.length) {
                addBtn.disabled = false;
                this._rebuild();
                return;
            }

            const formData = new FormData();
            formData.append('image', files[i]);

            fetch('upload.php', { method: 'POST', body: formData })
                .then((r) => r.json())
                .then((res) => {
                    if (res && res.success === 1 && res.file && res.file.url) {
                        this.data.images.push({ url: res.file.url, caption: '' });
                    }
                })
                .catch(() => { /* skip failed file, continue */ })
                .finally(() => uploadNext(i + 1));
        };

        uploadNext(0);
    }

    save() {
        // Drop any entries without a URL, just in case
        return {
            images: this.data.images.filter((im) => im && im.url)
        };
    }

    validate(savedData) {
        return savedData.images && savedData.images.length > 0;
    }
}