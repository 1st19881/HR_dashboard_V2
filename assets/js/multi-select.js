// ============================================================
// MultiSelect Component Class (Shared)
// ============================================================
class MultiSelect {
    constructor(el, options = {}) {
        this.el = typeof el === 'string' ? document.getElementById(el) : el;
        if (!this.el) return;
        this.placeholder = this.el.dataset.placeholder || 'เลือกทั้งหมด';
        this.onChange = options.onChange || null;
        this.maxTagsVisible = options.maxTagsVisible || 2;

        this.trigger = this.el.querySelector('.multi-select-trigger');
        this.dropdown = this.el.querySelector('.multi-select-dropdown');
        this.optionsContainer = this.el.querySelector('.ms-options');
        this.searchInput = this.el.querySelector('.ms-search-wrap input');
        this.countEl = this.el.querySelector('.ms-count');
        this.selectAllBtn = this.el.querySelector('.ms-select-all');
        this.clearAllBtn = this.el.querySelector('.ms-clear-all');

        this._bindEvents();
    }

    _bindEvents() {
        // Toggle dropdown
        this.trigger.addEventListener('click', (e) => {
            // Don't toggle if clicking on tag remove button
            if (e.target.closest('.ms-tag-remove')) return;
            this.toggle();
        });

        // Search
        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => this._filterOptions());
            // Prevent dropdown close when clicking search
            this.searchInput.addEventListener('click', (e) => e.stopPropagation());
        }

        // Option click (delegated)
        this.optionsContainer.addEventListener('click', (e) => {
            const opt = e.target.closest('.ms-option');
            if (!opt) return;
            opt.classList.toggle('selected');
            this._updateDisplay();
            if (this.onChange) this.onChange(this.getValues());
        });

        // Select All
        if (this.selectAllBtn) {
            this.selectAllBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.optionsContainer.querySelectorAll('.ms-option:not(.hidden)').forEach(o => o.classList.add('selected'));
                this._updateDisplay();
                if (this.onChange) this.onChange(this.getValues());
            });
        }

        // Clear All
        if (this.clearAllBtn) {
            this.clearAllBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.optionsContainer.querySelectorAll('.ms-option.selected').forEach(o => o.classList.remove('selected'));
                this._updateDisplay();
                if (this.onChange) this.onChange(this.getValues());
            });
        }

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!this.el.contains(e.target)) {
                this.close();
            }
        });
    }

    toggle() {
        if (this.el.classList.contains('open')) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        // Close all other multi-selects
        document.querySelectorAll('.multi-select.open').forEach(ms => {
            if (ms !== this.el) ms.classList.remove('open');
        });
        this.el.classList.add('open');
        if (this.searchInput) {
            this.searchInput.value = '';
            this._filterOptions();
            setTimeout(() => this.searchInput.focus(), 50);
        }
    }

    close() {
        this.el.classList.remove('open');
    }

    getValues() {
        const selected = this.optionsContainer.querySelectorAll('.ms-option.selected');
        return Array.from(selected).map(o => o.dataset.value);
    }

    getValuesString() {
        return this.getValues().join(',');
    }

    setOptions(items, valueKey) {
        // items = [{TYPE_NAME: '...'}, ...] or [{FUNC_NAME: '...'}, ...]
        const currentValues = this.getValues();
        let html = '';
        items.forEach(item => {
            const val = typeof item === 'string' ? item : item[valueKey];
            if (!val || val === '') return;
            const isSelected = currentValues.includes(val) ? ' selected' : '';
            html += `<div class="ms-option${isSelected}" data-value="${this._escapeHtml(val)}">
                        <div class="ms-checkbox"></div>
                        <span class="ms-option-text">${this._escapeHtml(val)}</span>
                     </div>`;
        });
        this.optionsContainer.innerHTML = html;
        this._updateDisplay();
    }

    clearSelection() {
        this.optionsContainer.querySelectorAll('.ms-option.selected').forEach(o => o.classList.remove('selected'));
        this._updateDisplay();
    }

    _filterOptions() {
        const query = (this.searchInput?.value || '').toLowerCase();
        this.optionsContainer.querySelectorAll('.ms-option').forEach(opt => {
            const text = opt.querySelector('.ms-option-text').textContent.toLowerCase();
            opt.classList.toggle('hidden', query !== '' && !text.includes(query));
        });
    }

    _updateDisplay() {
        const values = this.getValues();
        const total = this.optionsContainer.querySelectorAll('.ms-option').length;

        // Update count
        if (this.countEl) {
            this.countEl.textContent = values.length > 0 ? `${values.length}/${total}` : '';
        }

        // Update trigger display
        if (values.length === 0) {
            this.trigger.innerHTML = `<span class="ms-placeholder">${this.placeholder}</span>
                                      <i class="fa-solid fa-chevron-down ms-arrow"></i>`;
        } else {
            let tagsHtml = '<span class="ms-tags">';
            const showCount = Math.min(values.length, this.maxTagsVisible);
            for (let i = 0; i < showCount; i++) {
                tagsHtml += `<span class="ms-tag">${this._escapeHtml(values[i])}
                              <span class="ms-tag-remove" data-value="${this._escapeHtml(values[i])}">&times;</span>
                             </span>`;
            }
            if (values.length > this.maxTagsVisible) {
                tagsHtml += `<span class="ms-more">+${values.length - this.maxTagsVisible}</span>`;
            }
            tagsHtml += '</span>';
            this.trigger.innerHTML = `${tagsHtml}<i class="fa-solid fa-chevron-down ms-arrow"></i>`;

            // Bind tag remove events
            this.trigger.querySelectorAll('.ms-tag-remove').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const val = btn.dataset.value;
                    const opt = this.optionsContainer.querySelector(`.ms-option[data-value="${val}"]`);
                    if (opt) opt.classList.remove('selected');
                    this._updateDisplay();
                    if (this.onChange) this.onChange(this.getValues());
                });
            });
        }
    }

    _escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}
