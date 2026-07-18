/**
 * CodeTool — custom Editor.js Block Tool for code snippets.
 *
 * Renders a monospace textarea in the editor that preserves whitespace and
 * indentation, and saves the raw code as plain text. The frontend renderer
 * outputs it inside <pre><code>, so formatting is preserved exactly.
 *
 * Written in-house (no CDN dependency) to match the other SonaCMS tools.
 * Includes an optional language label, useful for documentation pages.
 *
 * Register in your EditorJS config:
 *   tools: { code: CodeTool }
 *
 * Saved block data shape:
 *   { "type": "code", "data": { "code": "...", "language": "bash" } }
 */
class CodeTool {
    static get toolbox() {
        return {
            title: 'Code',
            icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l-6 6 6 6M15 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    // Code is stored as plain text — never treat it as HTML.
    static get sanitize() {
        return {
            code: false,
            language: false
        };
    }

    // Allow Enter/Tab inside the textarea without Editor.js hijacking them
    static get enableLineBreaks() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            code: (data && typeof data.code === 'string') ? data.code : '',
            language: (data && data.language) ? data.language : ''
        };
        this.wrapper = null;
        this.textarea = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-code-tool');

        // Optional language label (e.g. "bash", "php") — helps readers and
        // gives the frontend a hook for syntax styling later.
        const langInput = document.createElement('input');
        langInput.type = 'text';
        langInput.placeholder = 'Language (optional) — e.g. bash, php';
        langInput.value = this.data.language;
        langInput.classList.add('cms-code-tool__lang');
        langInput.addEventListener('input', () => {
            this.data.language = langInput.value.trim();
        });

        this.textarea = document.createElement('textarea');
        this.textarea.placeholder = 'Paste or type your code here…';
        this.textarea.value = this.data.code;
        this.textarea.classList.add('cms-code-tool__area');
        this.textarea.spellcheck = false;

        // Keep the textarea sized to its content as the user types
        const autoGrow = () => {
            this.textarea.style.height = 'auto';
            this.textarea.style.height = (this.textarea.scrollHeight + 2) + 'px';
        };

        this.textarea.addEventListener('input', () => {
            this.data.code = this.textarea.value;
            autoGrow();
        });

        // Tab should insert a tab character, not move focus out of the field
        this.textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.textarea.selectionStart;
                const end   = this.textarea.selectionEnd;
                this.textarea.value =
                    this.textarea.value.substring(0, start) +
                    '    ' +
                    this.textarea.value.substring(end);
                this.textarea.selectionStart = this.textarea.selectionEnd = start + 4;
                this.data.code = this.textarea.value;
                autoGrow();
            }
            // Stop Editor.js from intercepting Enter and creating a new block
            e.stopPropagation();
        });

        this.wrapper.appendChild(langInput);
        this.wrapper.appendChild(this.textarea);

        // Size correctly when loading existing content
        setTimeout(autoGrow, 0);

        return this.wrapper;
    }

    save() {
        return {
            code: this.data.code,
            language: this.data.language
        };
    }

    validate(savedData) {
        return savedData.code.trim() !== '';
    }
}