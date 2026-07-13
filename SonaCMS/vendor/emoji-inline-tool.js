/**
 * EmojiInlineTool — a custom Editor.js *inline* tool that adds an emoji
 * button to the inline toolbar. Clicking it opens a small palette of common
 * emojis; picking one inserts it at the current cursor position within the
 * text.
 *
 * Written in-house (rather than using a third-party ESM plugin) so it:
 *   - loads as a plain global via <script src>, like the other tools
 *   - has zero external dependencies / no CDN emoji database
 *   - works identically on every OS, including Linux where the native OS
 *     emoji picker is unreliable
 *
 * Register as an INLINE tool in your EditorJS config:
 *   tools: { emoji: { class: EmojiInlineTool } }
 * and make sure the blocks you want it on have inlineToolbar: true.
 *
 * Inserted emojis are just Unicode characters in the text — nothing special
 * is stored, and they render everywhere with no frontend support needed.
 */
class EmojiInlineTool {
    static get isInline() {
        return true;
    }

    static get title() {
        return 'Emoji';
    }

    // Icon shown in the inline toolbar
    static get sanitize() {
        return {}; // emojis are plain text; no tags to sanitize
    }

    constructor({ api }) {
        this.api = api;
        this.button = null;
        this.palette = null;
        this.savedRange = null;

        // A curated set — the common ones a page author actually reaches for.
        this.emojis = [
            '😀','😄','😉','😊','😍','😎','🤔','😅','😂','🙂',
            '👍','👎','👏','🙌','🙏','💪','👋','🤝','✌️','👌',
            '❤️','🧡','💛','💚','💙','💜','🖤','⭐','✨','🔥',
            '🎉','🎊','🚀','💡','✅','❌','⚠️','📌','📎','🔒',
            '📧','📞','🌟','🏆','🎯','💰','📈','🛒','🏷️','🎁'
        ];
    }

    render() {
        this.button = document.createElement('button');
        this.button.type = 'button';
        this.button.classList.add('ce-inline-tool');
        this.button.innerHTML = '<span style="font-size:15px; line-height:1;">😀</span>';
        return this.button;
    }

    // Called when text is selected — remember the range so we can insert
    // the emoji back into the right spot after the palette is used.
    surround(range) {
        if (range) {
            this.savedRange = range.cloneRange();
        }
        this._togglePalette();
    }

    // Editor.js calls this to decide whether the tool shows as "active".
    checkState() {
        return false;
    }

    _togglePalette() {
        if (this.palette) {
            this._closePalette();
            return;
        }
        this._openPalette();
    }

    _openPalette() {
        this.palette = document.createElement('div');
        this.palette.classList.add('cms-emoji-palette');

        this.emojis.forEach((emoji) => {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.classList.add('cms-emoji-palette__cell');
            cell.textContent = emoji;
            cell.addEventListener('click', (e) => {
                e.preventDefault();
                this._insertEmoji(emoji);
                this._closePalette();
            });
            this.palette.appendChild(cell);
        });

        // Position the palette just under the inline toolbar button
        const rect = this.button.getBoundingClientRect();
        this.palette.style.position = 'absolute';
        this.palette.style.top = (window.scrollY + rect.bottom + 6) + 'px';
        this.palette.style.left = (window.scrollX + rect.left) + 'px';
        this.palette.style.zIndex = 10000;

        document.body.appendChild(this.palette);

        // Close if the user clicks elsewhere
        this._outsideHandler = (e) => {
            if (this.palette && !this.palette.contains(e.target) && e.target !== this.button) {
                this._closePalette();
            }
        };
        setTimeout(() => document.addEventListener('mousedown', this._outsideHandler), 0);
    }

    _closePalette() {
        if (this.palette) {
            this.palette.remove();
            this.palette = null;
        }
        if (this._outsideHandler) {
            document.removeEventListener('mousedown', this._outsideHandler);
            this._outsideHandler = null;
        }
    }

    _insertEmoji(emoji) {
        const range = this.savedRange;
        if (!range) return;

        // Replace the selection (or collapse point) with the emoji text node
        range.deleteContents();
        const node = document.createTextNode(emoji);
        range.insertNode(node);

        // Move cursor to just after the inserted emoji
        range.setStartAfter(node);
        range.setEndAfter(node);

        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    }
}