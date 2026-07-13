/**
 * ButtonTool — custom Editor.js Block Tool for inserting a call-to-action
 * button with editable text, URL, and a primary/secondary style.
 *
 * Written to replace the third-party editorjs-button (AnyButton) plugin,
 * whose blocks could not be removed once added. Because this is a standard
 * Block Tool, Editor.js's own block menu ("⋮⋮" → Delete) removes it cleanly.
 *
 * Usage: register in your EditorJS config, e.g.
 *   tools: { button: ButtonTool }
 *
 * Saved block data shape:
 *   { "type": "button", "data": { "text": "Click me", "url": "https://…", "style": "primary" } }
 */
class ButtonTool {
    static get toolbox() {
        return {
            title: 'Button',
            icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="8" width="18" height="8" rx="4" fill="currentColor"/></svg>'
        };
    }

    // Allow this block to be removed/converted like any other.
    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data, api }) {
        this.api = api;
        this.data = {
            text: (data && data.text) ? data.text : '',
            url: (data && data.url) ? data.url : '',
            style: (data && data.style === 'secondary') ? 'secondary' : 'primary'
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-button-tool');

        // Text input
        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.placeholder = 'Button text';
        textInput.value = this.data.text;
        textInput.classList.add('cms-button-tool__input');
        textInput.addEventListener('input', () => {
            this.data.text = textInput.value;
        });

        // URL input
        const urlInput = document.createElement('input');
        urlInput.type = 'text';
        urlInput.placeholder = 'https://example.com';
        urlInput.value = this.data.url;
        urlInput.classList.add('cms-button-tool__input');
        urlInput.addEventListener('input', () => {
            this.data.url = urlInput.value;
        });

        // Style selector (primary / secondary)
        const styleWrap = document.createElement('div');
        styleWrap.classList.add('cms-button-tool__styles');

        ['primary', 'secondary'].forEach((styleName) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = styleName.charAt(0).toUpperCase() + styleName.slice(1);
            btn.classList.add('cms-button-tool__style-btn');
            if (this.data.style === styleName) {
                btn.classList.add('cms-button-tool__style-btn--active');
            }
            btn.addEventListener('click', () => {
                this.data.style = styleName;
                // Refresh active state on both buttons
                styleWrap.querySelectorAll('.cms-button-tool__style-btn').forEach((b) => {
                    b.classList.remove('cms-button-tool__style-btn--active');
                });
                btn.classList.add('cms-button-tool__style-btn--active');
            });
            styleWrap.appendChild(btn);
        });

        this.wrapper.appendChild(textInput);
        this.wrapper.appendChild(urlInput);
        this.wrapper.appendChild(styleWrap);

        return this.wrapper;
    }

    save() {
        return {
            text: this.data.text.trim(),
            url: this.data.url.trim(),
            style: this.data.style
        };
    }

    // Basic validation — an empty button (no text or no URL) is dropped on save
    validate(savedData) {
        return savedData.text !== '' && savedData.url !== '';
    }
}