/**
 * PricingCardTool — a membership / pricing plan card for Editor.js.
 *
 * Renders a card with: a coloured header bar (plan name), an optional diagonal
 * corner ribbon ("POPULAR" etc.), a prominent price (symbol / amount / period),
 * a checkmark feature list (add/remove), a call-to-action button, and a
 * coloured accent bar at the bottom.
 *
 * Designed to be placed inside a Columns block — two cards per row — so a set
 * of four plans is two Columns blocks (2 + 2). Reuses proven patterns: the
 * gallery's add/remove list, the tile's colour pickers, Columns-based layout.
 *
 * Register: tools: { pricing: PricingCardTool }
 *
 * Saved data:
 *   {
 *     "plan": "Gold Golf",
 *     "ribbon": "Popular",
 *     "currency": "$", "amount": "840", "period": "Per Year",
 *     "features": ["$21 Competition Fee", "Free Social Golf", ...],
 *     "buttonText": "Download Form", "buttonUrl": "/form.pdf", "newTab": true,
 *     "headerColor": "#1BA7DE", "ribbonColor": "#1d232b", "accentColor": "#ffd200"
 *   }
 */
class PricingCardTool {
    static get toolbox() {
        return {
            title: 'Pricing Card',
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M4 8h16M8 12h8M8 15h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({ data }) {
        this.data = {
            plan: (data && data.plan) ? data.plan : '',
            ribbon: (data && data.ribbon) ? data.ribbon : '',
            currency: (data && data.currency) ? data.currency : '$',
            amount: (data && data.amount) ? data.amount : '',
            period: (data && data.period) ? data.period : 'Per Year',
            features: (data && Array.isArray(data.features)) ? data.features.slice() : [],
            buttonText: (data && data.buttonText) ? data.buttonText : '',
            buttonUrl: (data && data.buttonUrl) ? data.buttonUrl : '',
            newTab: (data && typeof data.newTab === 'boolean') ? data.newTab : false,
            headerColor: (data && data.headerColor) ? data.headerColor : '#1BA7DE',
            ribbonColor: (data && data.ribbonColor) ? data.ribbonColor : '#1d232b',
            accentColor: (data && data.accentColor) ? data.accentColor : '#ffd200'
        };
        this.wrapper = null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('cms-pricing-tool');

        this._field('Plan name', 'plan', 'e.g. Gold Golf');
        this._field('Ribbon text (optional)', 'ribbon', 'e.g. Popular \u2014 leave blank for none');

        // Price row: currency / amount / period
        const priceLabel = document.createElement('div');
        priceLabel.classList.add('cms-pricing-tool__label');
        priceLabel.textContent = 'Price';
        this.wrapper.appendChild(priceLabel);

        const priceRow = document.createElement('div');
        priceRow.classList.add('cms-pricing-tool__price-row');
        priceRow.appendChild(this._inlineInput('currency', '$', 'cms-pricing-tool__cur'));
        priceRow.appendChild(this._inlineInput('amount', '840', 'cms-pricing-tool__amt'));
        priceRow.appendChild(this._inlineInput('period', 'Per Year', 'cms-pricing-tool__per'));
        this.wrapper.appendChild(priceRow);

        // Features (add / remove list)
        const featLabel = document.createElement('div');
        featLabel.classList.add('cms-pricing-tool__label');
        featLabel.textContent = 'Features';
        this.wrapper.appendChild(featLabel);

        this.featureList = document.createElement('div');
        this.featureList.classList.add('cms-pricing-tool__features');
        this.wrapper.appendChild(this.featureList);
        this._renderFeatures();

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.classList.add('cms-pricing-tool__add');
        addBtn.textContent = '+ Add feature';
        addBtn.addEventListener('click', () => {
            this.data.features.push('');
            this._renderFeatures();
        });
        this.wrapper.appendChild(addBtn);

        // Button text + URL
        this._field('Button text (optional)', 'buttonText', 'e.g. Download Form');
        this._field('Button URL', 'buttonUrl', 'e.g. /membership-form.pdf');

        // New tab toggle
        const newTabRow = document.createElement('label');
        newTabRow.classList.add('cms-pricing-tool__newtab');
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.checked = this.data.newTab;
        cb.addEventListener('change', () => { this.data.newTab = cb.checked; });
        const cbText = document.createElement('span');
        cbText.textContent = 'Open button link in a new tab';
        newTabRow.appendChild(cb);
        newTabRow.appendChild(cbText);
        this.wrapper.appendChild(newTabRow);

        // Colours
        const colorLabel = document.createElement('div');
        colorLabel.classList.add('cms-pricing-tool__label');
        colorLabel.textContent = 'Colours';
        this.wrapper.appendChild(colorLabel);

        const colors = document.createElement('div');
        colors.classList.add('cms-pricing-tool__colors');
        colors.appendChild(this._colorControl('Header', 'headerColor'));
        colors.appendChild(this._colorControl('Ribbon', 'ribbonColor'));
        colors.appendChild(this._colorControl('Accent', 'accentColor'));
        this.wrapper.appendChild(colors);

        return this.wrapper;
    }

    _field(label, key, placeholder) {
        const l = document.createElement('div');
        l.classList.add('cms-pricing-tool__label');
        l.textContent = label;
        this.wrapper.appendChild(l);

        const input = document.createElement('input');
        input.type = 'text';
        input.classList.add('cms-pricing-tool__input');
        input.placeholder = placeholder;
        input.value = this.data[key];
        input.addEventListener('input', () => { this.data[key] = input.value; });
        this.wrapper.appendChild(input);
    }

    _inlineInput(key, placeholder, cls) {
        const input = document.createElement('input');
        input.type = 'text';
        input.classList.add('cms-pricing-tool__input', cls);
        input.placeholder = placeholder;
        input.value = this.data[key];
        input.addEventListener('input', () => { this.data[key] = input.value; });
        return input;
    }

    _renderFeatures() {
        this.featureList.innerHTML = '';
        this.data.features.forEach((feat, i) => {
            const row = document.createElement('div');
            row.classList.add('cms-pricing-tool__feature');

            const input = document.createElement('input');
            input.type = 'text';
            input.classList.add('cms-pricing-tool__input');
            input.placeholder = 'e.g. Unlimited Golf';
            input.value = feat;
            input.addEventListener('input', () => { this.data.features[i] = input.value; });

            const del = document.createElement('button');
            del.type = 'button';
            del.classList.add('cms-pricing-tool__del');
            del.textContent = '\u00d7';
            del.title = 'Remove feature';
            del.addEventListener('click', () => {
                this.data.features.splice(i, 1);
                this._renderFeatures();
            });

            row.appendChild(input);
            row.appendChild(del);
            this.featureList.appendChild(row);
        });
    }

    _colorControl(label, key) {
        const wrap = document.createElement('label');
        wrap.classList.add('cms-pricing-tool__color');
        const span = document.createElement('span');
        span.textContent = label;
        const input = document.createElement('input');
        input.type = 'color';
        input.value = this.data[key];
        input.addEventListener('input', () => { this.data[key] = input.value; });
        wrap.appendChild(span);
        wrap.appendChild(input);
        return wrap;
    }

    save() {
        return {
            plan: this.data.plan.trim(),
            ribbon: this.data.ribbon.trim(),
            currency: this.data.currency.trim(),
            amount: this.data.amount.trim(),
            period: this.data.period.trim(),
            features: this.data.features.map((f) => f.trim()).filter((f) => f !== ''),
            buttonText: this.data.buttonText.trim(),
            buttonUrl: this.data.buttonUrl.trim(),
            newTab: this.data.newTab,
            headerColor: this.data.headerColor,
            ribbonColor: this.data.ribbonColor,
            accentColor: this.data.accentColor
        };
    }
}