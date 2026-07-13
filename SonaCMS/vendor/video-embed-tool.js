/**
 * VideoEmbedTool — custom Editor.js Block Tool for embedding YouTube and
 * Vimeo videos via a manual URL input, rather than relying on Editor.js's
 * built-in paste-pattern auto-detection (which proved unreliable for this
 * use case).
 *
 * Usage: register as a tool in your EditorJS config, e.g.
 *   tools: { video: VideoEmbedTool }
 *
 * Saved block data shape:
 *   { "type": "video", "data": { "service": "youtube", "originalUrl": "...", "embedUrl": "..." } }
 */
class VideoEmbedTool {
    static get toolbox() {
        return {
            title: 'Video',
            icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>'
        };
    }

    static get sanitize() {
        // This tool's data is generated internally (parsed URL), not
        // typed/pasted as rich HTML, so nothing here needs sanitizing.
        return { service: false, originalUrl: false, embedUrl: false };
    }

    constructor({ data }) {
        this.data = data && data.embedUrl ? data : {};
        this.wrapper = null;
    }

    /**
     * Parse a pasted/typed URL into a YouTube or Vimeo embed.
     * Returns null if the URL isn't recognised.
     */
    static parseUrl(url) {
        if (!url) return null;

        let match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/);
        if (match) {
            return {
                service: 'youtube',
                originalUrl: url,
                embedUrl: 'https://www.youtube.com/embed/' + match[1]
            };
        }

        match = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
        if (match) {
            return {
                service: 'vimeo',
                originalUrl: url,
                embedUrl: 'https://player.vimeo.com/video/' + match[1]
            };
        }

        return null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('video-embed-tool');

        if (this.data && this.data.embedUrl) {
            this._renderPlayer();
        } else {
            this._renderInput();
        }

        return this.wrapper;
    }

    _renderInput() {
        this.wrapper.innerHTML = '';

        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'Paste a YouTube or Vimeo link, then press Enter';
        input.style.cssText = 'width:100%; padding:8px; box-sizing:border-box; border:1px solid #ccc; border-radius:3px; font-family:inherit;';

        const tryUrl = () => {
            const url = input.value.trim();
            const parsed = VideoEmbedTool.parseUrl(url);
            if (parsed) {
                this.data = parsed;
                this._renderPlayer();
            } else if (url) {
                input.style.borderColor = '#d94f5c';
                input.title = 'Could not recognise that as a YouTube or Vimeo link.';
            }
        };

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                tryUrl();
            }
        });
        input.addEventListener('paste', () => {
            setTimeout(tryUrl, 50);
        });

        this.wrapper.appendChild(input);
    }

    _renderPlayer() {
        this.wrapper.innerHTML = '';

        const container = document.createElement('div');
        container.style.cssText = 'position:relative; padding-bottom:56.25%; height:0; overflow:hidden; max-width:100%; background:#000;';

        const iframe = document.createElement('iframe');
        iframe.src = this.data.embedUrl;
        iframe.style.cssText = 'position:absolute; top:0; left:0; width:100%; height:100%; border:0;';
        iframe.setAttribute('allowfullscreen', 'true');
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');

        container.appendChild(iframe);
        this.wrapper.appendChild(container);

        const replaceBtn = document.createElement('button');
        replaceBtn.type = 'button';
        replaceBtn.textContent = 'Replace video';
        replaceBtn.style.cssText = 'margin-top:6px; font-size:0.8em; background:none; border:none; color:#0073aa; cursor:pointer; padding:0;';
        replaceBtn.addEventListener('click', () => {
            this.data = {};
            this._renderInput();
        });
        this.wrapper.appendChild(replaceBtn);
    }

    save() {
        return this.data;
    }
}