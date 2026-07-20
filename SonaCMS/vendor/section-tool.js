/**
 * SectionTool — paired "Section Start" / "Section End" marker blocks.
 *
 * Editor.js is a flat list of blocks with no native nesting, so a continuous
 * coloured band behind several blocks can't be done with a tune or a wrapper.
 * Instead, the author drops a "Section Start" block (choosing a colour) where
 * the band should begin and a "Section End" block where it should stop. The
 * frontend renderer (renderContent) wraps everything between them in a coloured
 * <div>.
 *
 * Two separate tool classes share this file:
 *   SectionStartTool — has the colour picker (presets + hex escape hatch)
 *   SectionEndTool   — a plain marker
 *
 * Register in EditorJS config:
 *   tools: {
 *     sectionStart: { class: SectionStartTool },
 *     sectionEnd:   { class: SectionEndTool }
 *   }
 *
 * Saved data:
 *   sectionStart -> { "preset": "highlight", "hex": "" }
 *   sectionEnd   -> {}
 *
 * Note: colour shows on the published page, not live in the editor — the editor
 * shows a labelled marker so it's clear where a section opens and closes.
 */

// Preset options. The value is a CSS class suffix; the frontend defines what
// each actually looks like (brand colour lives in CSS, not in content).
const SECTION_PRESETS = [
    { value: '',          label: 'None' },
    { value: 'subtle',    label: 'Subtle' },
    { value: 'muted',     label: 'Muted' },
    { value: 'highlight', label: 'Highlight' },
    { value: 'accent',    label: 'Accent' },
    { value: 'dark',      label: 'Dark' }
];

class SectionStartTool {
    static get toolbox() {
        return {
            title: 'Section Start',
            icon: '<svg width="18" height="16" viewBox="0 0 24 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2" stroke-dasharray="1 0"/><path d="M2 8h20" stroke="currentColor" stroke-width="2"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            preset: (data && typeof data.preset === 'string') ? data.preset : 'subtle',
            hex: (data && data.hex) ? data.hex : ''
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-section-marker', 'cms-section-marker--start');

        const heading = document.createElement('div');
        heading.classList.add('cms-section-marker__label');
        heading.textContent = '▼ Section start';
        this.wrapper.appendChild(heading);

        const row = document.createElement('div');
        row.classList.add('cms-section-marker__row');

        // Preset dropdown
        const select = document.createElement('select');
        select.classList.add('cms-section-marker__select');
        SECTION_PRESETS.forEach((p) => {
            const opt = document.createElement('option');
            opt.value = p.value;
            opt.textContent = p.label;
            if (p.value === this.data.preset) opt.selected = true;
            select.appendChild(opt);
        });
        select.addEventListener('change', () => { this.data.preset = select.value; });
        row.appendChild(select);

        // Hex escape hatch
        const hex = document.createElement('input');
        hex.type = 'text';
        hex.placeholder = 'or #hex (optional)';
        hex.value = this.data.hex;
        hex.classList.add('cms-section-marker__hex');
        hex.addEventListener('input', () => { this.data.hex = hex.value.trim(); });
        row.appendChild(hex);

        this.wrapper.appendChild(row);

        const hint = document.createElement('div');
        hint.classList.add('cms-section-marker__hint');
        hint.textContent = 'Everything until "Section end" sits inside this coloured band. A hex value overrides the preset.';
        this.wrapper.appendChild(hint);

        return this.wrapper;
    }

    save() {
        return {
            preset: this.data.preset,
            hex: this.data.hex
        };
    }
}

class SectionEndTool {
    static get toolbox() {
        return {
            title: 'Section End',
            icon: '<svg width="18" height="16" viewBox="0 0 24 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M2 12h20" stroke="currentColor" stroke-width="2"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor() {
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-section-marker', 'cms-section-marker--end');
        this.wrapper.textContent = '▲ Section end';
        return this.wrapper;
    }

    save() {
        return {};
    }
}