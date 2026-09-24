{{--
    Searchable-select (combobox) enhancement.
    Progressively upgrades any <select data-combobox> or <select class="catalog-select">
    into a type-to-search dropdown while keeping the original <select> in the DOM
    (hidden) so form submission, server validation and existing onchange handlers
    (onCatalogItemSelect, filterInvoices, form auto-submit…) keep working unchanged.
--}}
<script>
(function () {
    'use strict';

    var SELECTOR = 'select[data-combobox], select.catalog-select';

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function enhance(select) {
        if (!select || select.dataset.comboboxReady) return;
        select.dataset.comboboxReady = '1';

        var wasRequired = select.required;
        select.required = false;
        select.style.display = 'none';
        select.setAttribute('aria-hidden', 'true');
        select.tabIndex = -1;

        var wrapper = document.createElement('div');
        wrapper.className = 'combobox relative';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        var placeholder = '';
        if (select.options.length && select.options[0].value === '') {
            placeholder = select.options[0].textContent.trim();
        }

        var input = document.createElement('input');
        input.type = 'text';
        input.className = select.className.replace(/\bcursor-pointer\b/, '') + ' pr-9';
        input.autocomplete = 'off';
        input.spellcheck = false;
        input.placeholder = placeholder || 'Search…';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-autocomplete', 'list');
        if (select.disabled) input.disabled = true;
        if (wasRequired) input.required = true;
        wrapper.appendChild(input);

        var chev = document.createElement('span');
        chev.className = 'combobox-chevron';
        chev.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';
        wrapper.appendChild(chev);

        var panel = document.createElement('div');
        // Fixed positioning + JS geometry so the panel escapes any
        // overflow-x-auto ancestor (line-item tables) and is never clipped;
        // it flips above the input when there is no room below.
        panel.className = 'combobox-panel fixed z-50 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg overflow-y-auto hidden';
        wrapper.appendChild(panel);

        var activeIndex = -1;

        function selectedOption() {
            var o = select.options[select.selectedIndex];
            return (o && o.value !== '') ? o : null;
        }

        function syncInput() {
            var o = selectedOption();
            input.value = o ? o.textContent.trim() : '';
            if (wasRequired) input.setCustomValidity(o ? '' : 'Please select an option');
        }
        syncInput();

        function isOpen() { return !panel.classList.contains('hidden'); }

        // Size/place the fixed panel under (or above) the input, clamped to
        // the viewport so every option is reachable without clipping.
        function positionPanel() {
            var r = input.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var gap = 4, cap = 240;
            var spaceBelow = vh - r.bottom - gap - 8;
            var spaceAbove = r.top - gap - 8;
            var openUp = spaceBelow < 140 && spaceAbove > spaceBelow;
            var maxH = Math.max(100, Math.min(cap, openUp ? spaceAbove : spaceBelow));
            panel.style.width = r.width + 'px';
            panel.style.maxHeight = maxH + 'px';
            panel.style.left = r.left + 'px';
            if (openUp) {
                panel.style.top = 'auto';
                panel.style.bottom = (vh - r.top + gap) + 'px';
            } else {
                panel.style.bottom = 'auto';
                panel.style.top = (r.bottom + gap) + 'px';
            }
        }

        function openPanel() {
            if (isOpen()) return;
            panel.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
            render('');
            positionPanel();
        }
        function closePanel() {
            panel.classList.add('hidden');
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        }

        function render(filter) {
            var q = (filter || '').trim().toLowerCase();
            panel.innerHTML = '';
            var matches = Array.prototype.filter.call(select.options, function (o) {
                if (o.value === '') return false;
                return !q || o.textContent.toLowerCase().indexOf(q) !== -1;
            });
            if (!matches.length) {
                panel.innerHTML = '<div class="px-3 py-2.5 text-xs text-slate-400 dark:text-slate-500">No matches found</div>';
                activeIndex = -1;
                return;
            }
            matches.forEach(function (o) {
                var div = document.createElement('div');
                div.className = 'combobox-option px-3 py-2 text-xs cursor-pointer text-slate-700 dark:text-slate-200';
                div.dataset.value = o.value;
                var label = o.textContent.trim();
                if (q) {
                    var idx = label.toLowerCase().indexOf(q);
                    div.innerHTML = escapeHtml(label.slice(0, idx)) +
                        '<mark>' + escapeHtml(label.slice(idx, idx + q.length)) + '</mark>' +
                        escapeHtml(label.slice(idx + q.length));
                } else {
                    div.textContent = label;
                }
                if (o.value === select.value) div.classList.add('selected');
                div.addEventListener('mousedown', function (e) { e.preventDefault(); });
                div.addEventListener('click', function () { pick(o.value); });
                panel.appendChild(div);
            });
            activeIndex = -1;
        }

        function optionNodes() { return panel.querySelectorAll('.combobox-option'); }

        function setActive(idx) {
            var opts = optionNodes();
            if (!opts.length) return;
            activeIndex = Math.max(0, Math.min(idx, opts.length - 1));
            for (var i = 0; i < opts.length; i++) opts[i].classList.toggle('active', i === activeIndex);
            opts[activeIndex].scrollIntoView({ block: 'nearest' });
        }

        function pick(value) {
            select.value = value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            syncInput();
            closePanel();
        }

        // Keep the visible input in sync when page code changes the select
        select.addEventListener('change', syncInput);

        input.addEventListener('focus', openPanel);
        input.addEventListener('input', function () {
            input.setCustomValidity('');
            if (select.value !== '') { select.value = ''; }
            openPanel();
            render(input.value);
            positionPanel();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!isOpen()) openPanel(); else setActive(activeIndex + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (isOpen()) setActive(activeIndex - 1);
            } else if (e.key === 'Enter') {
                if (isOpen()) {
                    var opts = optionNodes();
                    if (activeIndex >= 0 && opts[activeIndex]) {
                        e.preventDefault();
                        pick(opts[activeIndex].dataset.value);
                    } else if (opts.length === 1) {
                        e.preventDefault();
                        pick(opts[0].dataset.value);
                    }
                }
            } else if (e.key === 'Escape') {
                if (isOpen()) { e.preventDefault(); closePanel(); }
            }
        });
        input.addEventListener('blur', function () {
            setTimeout(function () {
                if (!panel.matches(':hover')) closePanel();
            }, 120);
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) closePanel();
        });

        // Keep the panel glued to the input while the page/any scroll
        // container moves or the viewport resizes.
        window.addEventListener('scroll', function () { if (isOpen()) positionPanel(); }, true);
        window.addEventListener('resize', function () { if (isOpen()) positionPanel(); });

        if (select.form && wasRequired) {
            select.form.addEventListener('submit', function () {
                input.setCustomValidity(selectedOption() ? '' : 'Please select an option');
            }, true);
        }
    }

    function scan(root) {
        (root || document).querySelectorAll(SELECTOR).forEach(enhance);
    }

    function observe() {
        var observer = new MutationObserver(function (muts) {
            muts.forEach(function (m) {
                m.addedNodes.forEach(function (n) {
                    if (n.nodeType !== 1) return;
                    if (n.matches && n.matches(SELECTOR)) enhance(n);
                    if (n.querySelectorAll) n.querySelectorAll(SELECTOR).forEach(enhance);
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { scan(); observe(); });
    } else {
        scan(); observe();
    }

    window.enhanceComboboxes = scan;
})();
</script>
