/**
 * FormTool — custom Editor.js Block Tool for inserting a site form
 * (e.g. a contact form) into page content.
 *
 * The block only stores WHICH form to load (an identifier like "contact").
 * The actual form markup and logic live in /forms/{id}.php and are rendered
 * server-side by the frontend renderer — this keeps form logic out of the
 * CMS content and out of the editor.
 *
 * The dropdown of available forms is populated from a global the page sets:
 *   window.SONA_FORMS = ['contact', 'newsletter', ...];
 * (editor.php injects this by scanning /forms/ via getAvailableForms().)
 *
 * Usage: register in your EditorJS config, e.g.
 *   tools: { form: FormTool }
 *
 * Saved block data shape:
 *   { "type": "form", "data": { "formId": "contact" } }
 */
class FormTool {
    static get toolbox() {
        return {
            title: 'Form',
            icon: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="2"/><line x1="8" y1="8" x2="16" y2="8" stroke="currentColor" stroke-width="2"/><line x1="8" y1="12" x2="16" y2="12" stroke="currentColor" stroke-width="2"/><line x1="8" y1="16" x2="12" y2="16" stroke="currentColor" stroke-width="2"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            formId: (data && data.formId) ? data.formId : ''
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-form-tool');

        const forms = Array.isArray(window.SONA_FORMS) ? window.SONA_FORMS : [];

        if (forms.length === 0) {
            const notice = document.createElement('p');
            notice.classList.add('cms-form-tool__notice');
            notice.textContent = 'No forms found in /forms/. Add a form file first.';
            this.wrapper.appendChild(notice);
            return this.wrapper;
        }

        const label = document.createElement('label');
        label.classList.add('cms-form-tool__label');
        label.textContent = 'Insert form:';

        const select = document.createElement('select');
        select.classList.add('cms-form-tool__select');

        // Placeholder option
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '— Choose a form —';
        select.appendChild(placeholder);

        forms.forEach((formId) => {
            const opt = document.createElement('option');
            opt.value = formId;
            opt.textContent = formId;
            if (formId === this.data.formId) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });

        select.addEventListener('change', () => {
            this.data.formId = select.value;
            this._renderPreview();
        });

        this.wrapper.appendChild(label);
        this.wrapper.appendChild(select);

        this._previewEl = document.createElement('div');
        this._previewEl.classList.add('cms-form-tool__preview');
        this.wrapper.appendChild(this._previewEl);

        this._renderPreview();

        return this.wrapper;
    }

    _renderPreview() {
        if (!this._previewEl) return;
        if (this.data.formId) {
            this._previewEl.textContent = 'Form "' + this.data.formId + '" will appear here on the published page.';
        } else {
            this._previewEl.textContent = '';
        }
    }

    save() {
        return {
            formId: this.data.formId
        };
    }

    // Drop the block on save if no form was chosen
    validate(savedData) {
        return savedData.formId !== '';
    }
}