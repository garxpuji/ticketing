/**
 * Universal Searchable Dropdown (Live Search Combobox)
 * Central Ticketing System - Modern Tailwind & Dark Mode Compatible
 * 
 * Automatically enhances standard <select> elements into searchable comboboxes
 * while maintaining two-way data sync, form submission, validation, and event handling.
 */

export class SearchableSelect {
    constructor(selectElement, options = {}) {
        this.select = selectElement;
        this.options = Object.assign({
            placeholder: 'Ketik untuk mencari...',
            emptyText: 'Tidak ada pilihan yang cocok',
            autoFlip: true,
            maxHeight: 'max-h-56'
        }, options);

        if (this.select.dataset.searchableEnhanced === 'true') {
            return;
        }

        this.isOpen = false;
        this.focusedOptionIndex = -1;
        this.init();
    }

    init() {
        this.select.dataset.searchableEnhanced = 'true';

        // 1. Visually hide native select without display:none so HTML5 validation & form submit still work
        this.select.style.position = 'absolute';
        this.select.style.width = '1px';
        this.select.style.height = '1px';
        this.select.style.padding = '0';
        this.select.style.margin = '-1px';
        this.select.style.overflow = 'hidden';
        this.select.style.clip = 'rect(0, 0, 0, 0)';
        this.select.style.whiteSpace = 'nowrap';
        this.select.style.border = '0';
        this.select.tabIndex = -1;

        // 2. Create Wrapper & Button according to original width
        const isFullWidth = this.select.classList.contains('w-full');

        this.wrapper = document.createElement('div');
        this.wrapper.className = isFullWidth 
            ? 'relative block w-full searchable-select-wrapper text-left' 
            : 'relative inline-block searchable-select-wrapper text-left';

        // Insert wrapper into DOM
        this.select.parentNode.insertBefore(this.wrapper, this.select);
        this.wrapper.appendChild(this.select);

        // 3. Create Trigger Button
        this.btn = document.createElement('button');
        this.btn.type = 'button';
        this.btn.className = isFullWidth
            ? 'searchable-select-btn flex items-center justify-between gap-2.5 w-full min-h-[40px] px-3.5 py-2 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 hover:border-emerald-500 hover:bg-white dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer text-left shadow-xs'
            : 'searchable-select-btn inline-flex items-center justify-between gap-2.5 min-w-[170px] min-h-[36px] px-3 py-1.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 hover:border-emerald-500 hover:bg-white dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer text-left shadow-xs';
        
        this.btn.setAttribute('aria-haspopup', 'listbox');
        this.btn.setAttribute('aria-expanded', 'false');

        // Label span
        this.labelSpan = document.createElement('span');
        this.labelSpan.className = 'truncate block font-semibold text-slate-800 dark:text-slate-200';

        // Chevron icon
        this.chevron = document.createElement('i');
        this.chevron.className = 'fa-solid fa-chevron-down text-[10px] text-slate-400 dark:text-slate-500 shrink-0 ml-1 transition-transform duration-200';

        this.btn.appendChild(this.labelSpan);
        this.btn.appendChild(this.chevron);
        this.wrapper.appendChild(this.btn);

        // 4. Create Dropdown Panel
        this.panel = document.createElement('div');
        this.panel.className = 'searchable-select-panel hidden absolute left-0 top-full mt-1.5 w-full min-w-[220px] max-w-[95vw] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl z-[99999] overflow-hidden';
        this.panel.setAttribute('role', 'listbox');

        // Search Header
        const searchHeader = document.createElement('div');
        searchHeader.className = 'p-2 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-100 dark:border-slate-800';

        const searchRel = document.createElement('div');
        searchRel.className = 'relative flex items-center';

        const searchIcon = document.createElement('i');
        searchIcon.className = 'fa-solid fa-magnifying-glass absolute left-2.5 text-slate-400 text-xs pointer-events-none';

        this.searchInput = document.createElement('input');
        this.searchInput.type = 'text';
        this.searchInput.autocomplete = 'off';
        this.searchInput.placeholder = this.options.placeholder;
        this.searchInput.className = 'w-full pl-7 pr-7 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500';

        this.clearBtn = document.createElement('button');
        this.clearBtn.type = 'button';
        this.clearBtn.className = 'hidden absolute right-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs p-1 cursor-pointer';
        this.clearBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';

        searchRel.appendChild(searchIcon);
        searchRel.appendChild(this.searchInput);
        searchRel.appendChild(this.clearBtn);
        searchHeader.appendChild(searchRel);
        this.panel.appendChild(searchHeader);

        // Options List Container
        this.optionsList = document.createElement('div');
        this.optionsList.className = `options-list ${this.options.maxHeight} overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 py-1`;
        this.panel.appendChild(this.optionsList);

        // Empty state message
        this.emptyState = document.createElement('div');
        this.emptyState.className = 'hidden px-4 py-3 text-center text-xs text-slate-400 dark:text-slate-500 italic';
        this.emptyState.innerHTML = `<i class="fa-solid fa-magnifying-glass text-[11px] mr-1.5 opacity-60"></i> ${this.options.emptyText}`;
        this.panel.appendChild(this.emptyState);

        this.wrapper.appendChild(this.panel);

        // 5. Populate Options
        this.renderOptions();
        this.updateButtonLabel();

        // 6. Bind Event Listeners
        this.bindEvents();

        // 7. Setup Interception for programmatic value changes
        this.interceptValueChanges();

        // 8. Observe dynamic option additions (e.g. AJAX / fetch)
        this.observeDOM();
    }

    renderOptions() {
        this.optionsList.innerHTML = '';
        const options = Array.from(this.select.options);

        options.forEach((opt, idx) => {
            const row = document.createElement('div');
            const isSelected = opt.selected;
            const text = opt.textContent.trim() || opt.value || '(Kosong)';

            row.className = `option-row px-3 py-2 flex items-center justify-between text-xs cursor-pointer transition-colors ${
                isSelected 
                    ? 'bg-emerald-50/70 dark:bg-emerald-950/40 font-bold text-emerald-700 dark:text-emerald-400' 
                    : 'text-slate-700 dark:text-slate-300 hover:bg-emerald-50/50 dark:hover:bg-slate-800'
            } ${opt.disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}`;
            
            row.dataset.index = idx;
            row.dataset.value = opt.value;
            row.dataset.search = (text + ' ' + opt.value).toLowerCase();
            row.setAttribute('role', 'option');
            row.setAttribute('aria-selected', isSelected ? 'true' : 'false');

            row.innerHTML = `
                <span class="truncate pr-2">${text}</span>
                ${isSelected ? '<i class="fa-solid fa-check text-emerald-600 dark:text-emerald-400 text-xs shrink-0 checkmark-icon"></i>' : ''}
            `;

            row.addEventListener('click', (e) => {
                e.stopPropagation();
                if (opt.disabled) return;
                this.selectOptionByIndex(idx);
            });

            this.optionsList.appendChild(row);
        });

        this.updateButtonLabel();
    }

    updateButtonLabel() {
        const selectedOpt = this.select.options[this.select.selectedIndex];
        if (selectedOpt) {
            this.labelSpan.textContent = selectedOpt.textContent.trim() || selectedOpt.value || 'Pilih...';
        } else {
            this.labelSpan.textContent = this.select.getAttribute('placeholder') || 'Pilih...';
        }
    }

    selectOptionByIndex(idx) {
        if (idx < 0 || idx >= this.select.options.length) return;
        
        const option = this.select.options[idx];
        if (option.disabled) return;

        this.select.selectedIndex = idx;
        this.updateButtonLabel();

        // Update UI highlights
        const rows = this.optionsList.querySelectorAll('.option-row');
        rows.forEach((row, rIdx) => {
            const isMatch = rIdx === idx;
            row.setAttribute('aria-selected', isMatch ? 'true' : 'false');
            if (isMatch) {
                row.className = 'option-row px-3 py-2 flex items-center justify-between text-xs cursor-pointer transition-colors bg-emerald-50/70 dark:bg-emerald-950/40 font-bold text-emerald-700 dark:text-emerald-400';
                if (!row.querySelector('.checkmark-icon')) {
                    const check = document.createElement('i');
                    check.className = 'fa-solid fa-check text-emerald-600 dark:text-emerald-400 text-xs shrink-0 checkmark-icon';
                    row.appendChild(check);
                }
            } else {
                row.className = 'option-row px-3 py-2 flex items-center justify-between text-xs cursor-pointer transition-colors text-slate-700 dark:text-slate-300 hover:bg-emerald-50/50 dark:hover:bg-slate-800';
                const check = row.querySelector('.checkmark-icon');
                if (check) check.remove();
            }
        });

        // Trigger change events for native listeners (including onchange="this.form.submit()")
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
        this.select.dispatchEvent(new Event('input', { bubbles: true }));

        this.close();
        this.btn.focus();
    }

    selectOptionByValue(value) {
        const idx = Array.from(this.select.options).findIndex(opt => String(opt.value) === String(value));
        if (idx !== -1) {
            this.selectOptionByIndex(idx);
        }
    }

    syncFromSelect() {
        const selectedIdx = this.select.selectedIndex;
        this.updateButtonLabel();

        const rows = this.optionsList.querySelectorAll('.option-row');
        rows.forEach((row, rIdx) => {
            const isMatch = rIdx === selectedIdx;
            row.setAttribute('aria-selected', isMatch ? 'true' : 'false');
            if (isMatch) {
                row.className = 'option-row px-3 py-2 flex items-center justify-between text-xs cursor-pointer transition-colors bg-emerald-50/70 dark:bg-emerald-950/40 font-bold text-emerald-700 dark:text-emerald-400';
                if (!row.querySelector('.checkmark-icon')) {
                    const check = document.createElement('i');
                    check.className = 'fa-solid fa-check text-emerald-600 dark:text-emerald-400 text-xs shrink-0 checkmark-icon';
                    row.appendChild(check);
                }
            } else {
                row.className = 'option-row px-3 py-2 flex items-center justify-between text-xs cursor-pointer transition-colors text-slate-700 dark:text-slate-300 hover:bg-emerald-50/50 dark:hover:bg-slate-800';
                const check = row.querySelector('.checkmark-icon');
                if (check) check.remove();
            }
        });
    }

    open() {
        if (this.isOpen) return;

        // Close other open searchable dropdowns
        document.querySelectorAll('.searchable-select-panel:not(.hidden)').forEach(p => {
            p.classList.add('hidden');
            const otherBtn = p.parentElement?.querySelector('.searchable-select-btn');
            const otherChev = p.parentElement?.querySelector('.fa-chevron-down');
            if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
            if (otherChev) otherChev.style.transform = 'rotate(0deg)';
        });

        this.panel.classList.remove('hidden');
        this.btn.setAttribute('aria-expanded', 'true');
        this.chevron.style.transform = 'rotate(180deg)';
        this.isOpen = true;

        // Smart Flip Detection (if near bottom of screen or container)
        if (this.options.autoFlip) {
            const rect = this.wrapper.getBoundingClientRect();
            const panelHeight = 280;
            const spaceBelow = window.innerHeight - rect.bottom;
            if (spaceBelow < panelHeight && rect.top > panelHeight) {
                this.panel.classList.remove('top-full', 'mt-1.5');
                this.panel.classList.add('bottom-full', 'mb-1.5');
            } else {
                this.panel.classList.remove('bottom-full', 'mb-1.5');
                this.panel.classList.add('top-full', 'mt-1.5');
            }
        }

        // Reset search & focus
        this.searchInput.value = '';
        this.filterOptions('');
        setTimeout(() => this.searchInput.focus(), 50);

        // Scroll selected option into view
        const selectedRow = this.optionsList.querySelector('[aria-selected="true"]');
        if (selectedRow) {
            selectedRow.scrollIntoView({ block: 'nearest' });
        }
    }

    close() {
        if (!this.isOpen) return;
        this.panel.classList.add('hidden');
        this.btn.setAttribute('aria-expanded', 'false');
        this.chevron.style.transform = 'rotate(0deg)';
        this.isOpen = false;
        this.focusedOptionIndex = -1;
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    filterOptions(query) {
        const q = (query || '').toLowerCase().trim();
        const rows = this.optionsList.querySelectorAll('.option-row');
        let matchCount = 0;

        if (this.clearBtn) {
            if (q.length > 0) {
                this.clearBtn.classList.remove('hidden');
            } else {
                this.clearBtn.classList.add('hidden');
            }
        }

        rows.forEach(row => {
            const text = row.dataset.search || '';
            if (!q || text.includes(q)) {
                row.style.display = 'flex';
                matchCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (matchCount === 0) {
            this.emptyState.classList.remove('hidden');
        } else {
            this.emptyState.classList.add('hidden');
        }
    }

    bindEvents() {
        // Toggle on button click
        this.btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggle();
        });

        // Search input
        this.searchInput.addEventListener('input', (e) => {
            this.filterOptions(e.target.value);
        });

        // Clear search button
        this.clearBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.searchInput.value = '';
            this.filterOptions('');
            this.searchInput.focus();
        });

        // Keyboard Navigation
        this.searchInput.addEventListener('keydown', (e) => {
            const visibleRows = Array.from(this.optionsList.querySelectorAll('.option-row')).filter(r => r.style.display !== 'none');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (visibleRows.length > 0) {
                    this.focusedOptionIndex = 0;
                    this.highlightRow(visibleRows[0]);
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (visibleRows.length === 1) {
                    const idx = parseInt(visibleRows[0].dataset.index, 10);
                    this.selectOptionByIndex(idx);
                } else if (this.focusedOptionIndex >= 0 && visibleRows[this.focusedOptionIndex]) {
                    const idx = parseInt(visibleRows[this.focusedOptionIndex].dataset.index, 10);
                    this.selectOptionByIndex(idx);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
                this.btn.focus();
            }
        });

        // Keyboard navigation while focused on options list
        this.optionsList.addEventListener('keydown', (e) => {
            const visibleRows = Array.from(this.optionsList.querySelectorAll('.option-row')).filter(r => r.style.display !== 'none');
            if (visibleRows.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.focusedOptionIndex = Math.min(this.focusedOptionIndex + 1, visibleRows.length - 1);
                this.highlightRow(visibleRows[this.focusedOptionIndex]);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.focusedOptionIndex = Math.max(this.focusedOptionIndex - 1, 0);
                this.highlightRow(visibleRows[this.focusedOptionIndex]);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.focusedOptionIndex >= 0 && visibleRows[this.focusedOptionIndex]) {
                    const idx = parseInt(visibleRows[this.focusedOptionIndex].dataset.index, 10);
                    this.selectOptionByIndex(idx);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
                this.btn.focus();
            }
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target)) {
                this.close();
            }
        });

        // Native select change event listener
        this.select.addEventListener('change', () => {
            this.syncFromSelect();
        });
    }

    highlightRow(row) {
        if (!row) return;
        this.optionsList.querySelectorAll('.option-row').forEach(r => r.classList.remove('ring-1', 'ring-emerald-500'));
        row.classList.add('ring-1', 'ring-emerald-500');
        row.scrollIntoView({ block: 'nearest' });
    }

    interceptValueChanges() {
        // Intercept programmatic select.value assignment
        const select = this.select;
        const self = this;
        const originalDescriptor = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');

        if (originalDescriptor) {
            Object.defineProperty(select, 'value', {
                get() {
                    return originalDescriptor.get.call(this);
                },
                set(val) {
                    originalDescriptor.set.call(this, val);
                    self.syncFromSelect();
                },
                configurable: true
            });
        }
    }

    observeDOM() {
        // Observe changes to <select> children (e.g. if options are dynamically populated via JS)
        const observer = new MutationObserver(() => {
            this.renderOptions();
        });

        observer.observe(this.select, {
            childList: true,
            subtree: true
        });
    }
}

/**
 * Global Initialization Helper
 */
export function initSearchableSelects(scope = document) {
    const selector = 'select:not([data-no-searchable]):not([multiple])';
    const selects = scope.querySelectorAll(selector);

    selects.forEach(select => {
        // Skip template clones or hidden system elements if marked
        if (select.dataset.searchableEnhanced === 'true') return;
        new SearchableSelect(select);
    });
}

// Global window registration
if (typeof window !== 'undefined') {
    window.SearchableSelect = SearchableSelect;
    window.initSearchableSelects = initSearchableSelects;
    window.syncSearchableSelect = function (selectElement) {
        if (typeof selectElement === 'string') {
            selectElement = document.querySelector(selectElement);
        }
        if (selectElement && selectElement.dispatchEvent) {
            selectElement.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };
}
