/**
 * AlignmentTune — a custom Editor.js Block Tune that adds text-alignment
 * options (left, center, right, justify) to a block's settings menu (the
 * six-dot popover).
 *
 * Written in-house to avoid a third-party dependency, consistent with the
 * other SonaCMS editor tools. Registered as a *tune* (not a tool) and
 * attached to specific blocks via their `tunes` array.
 *
 * How it stores data: Block Tunes save into the block's `tunes` key, e.g.
 *   { "type": "paragraph", "data": { "text": "..." },
 *     "tunes": { "alignment": { "alignment": "center" } } }
 * The frontend renderer reads block.tunes.alignment.alignment to apply a
 * CSS class on output.
 *
 * Register in EditorJS config:
 *   tools: {
 *     alignment: { class: AlignmentTune },
 *     paragraph: { class: Paragraph, inlineToolbar: true, tunes: ['alignment'] },
 *     header:    { class: Header, inlineToolbar: true, tunes: ['alignment'] },
 *   }
 */
class AlignmentTune {
    static get isTune() {
        return true;
    }

    constructor({ data, api }) {
        this.api = api;
        this.alignments = ['left', 'center', 'right', 'justify'];
        this.data = (data && this.alignments.includes(data.alignment))
            ? data
            : { alignment: 'left' };
        this.wrapper = null;
    }

    // Simple inline SVG icons for each alignment option
    _icon(align) {
        const lines = {
            left:    ['M3 5h14', 'M3 9h9',  'M3 13h14', 'M3 17h9'],
            center:  ['M3 5h14', 'M5 9h10', 'M3 13h14', 'M5 17h10'],
            right:   ['M3 5h14', 'M8 9h9',  'M3 13h14', 'M8 17h9'],
            justify: ['M3 5h14', 'M3 9h14', 'M3 13h14', 'M3 17h14'],
        };
        const paths = lines[align].map(
            (d) => '<path d="' + d + '" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
        ).join('');
        return '<svg width="20" height="20" viewBox="0 0 20 22" fill="none" xmlns="http://www.w3.org/2000/svg">' + paths + '</svg>';
    }

    // Renders the alignment options into the block settings (six-dot) menu
    render() {
        this.wrapper = document.createElement('div');

        this.alignments.forEach((align) => {
            const item = document.createElement('div');
            item.classList.add('ce-popover-item');
            item.innerHTML =
                '<div class="ce-popover-item__icon">' + this._icon(align) + '</div>' +
                '<div class="ce-popover-item__title">' +
                align.charAt(0).toUpperCase() + align.slice(1) +
                '</div>';

            if (this.data.alignment === align) {
                item.classList.add('ce-popover-item--active');
            }

            item.addEventListener('click', () => {
                this.data.alignment = align;
                // Update active state in the menu
                this.wrapper.querySelectorAll('.ce-popover-item').forEach((el) =>
                    el.classList.remove('ce-popover-item--active'));
                item.classList.add('ce-popover-item--active');
                // Apply live to the block in the editor
                this._applyToBlock();
                this.api.toolbar.close();
            });

            this.wrapper.appendChild(item);
        });

        return this.wrapper;
    }

    // Wrap the block content visually while editing
    wrap(blockContent) {
        this.blockContent = blockContent;
        this._applyToBlock();
        return blockContent;
    }

    _applyToBlock() {
        if (!this.blockContent) return;
        this.alignments.forEach((a) =>
            this.blockContent.classList.remove('cms-align-' + a));
        this.blockContent.classList.add('cms-align-' + this.data.alignment);
    }

    save() {
        return { alignment: this.data.alignment };
    }
}