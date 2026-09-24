{{-- Global command palette (Cmd/Ctrl + K) --}}
<dialog id="obCommandPalette" aria-labelledby="obCmdHeading">
    <h2 id="obCmdHeading" class="sr-only">Command palette</h2>
    <div class="flex items-center gap-2.5 px-3.5 border-b" style="border-color: var(--border-color)">
        <i data-lucide="search" class="w-4 h-4 shrink-0" style="color: var(--text-muted)" aria-hidden="true"></i>
        <input id="obCmdInput" class="ob-cmd-input" type="text" placeholder="Search pages and actions…"
               autocomplete="off" spellcheck="false" role="combobox" aria-expanded="true"
               aria-controls="obCmdList" aria-autocomplete="list">
        <kbd class="ob-cmd-esc">Esc</kbd>
    </div>
    <div id="obCmdList" class="ob-cmd-list" role="listbox" aria-label="Command results"></div>
    <div class="ob-cmd-foot">
        <span><kbd>&uarr;</kbd> <kbd>&darr;</kbd> to navigate</span>
        <span><kbd>&crarr;</kbd> to open</span>
        <span class="ml-auto">OpenBooks SG</span>
    </div>
</dialog>

<script>
(function () {
    var dlg = document.getElementById('obCommandPalette');
    var input = document.getElementById('obCmdInput');
    var list = document.getElementById('obCmdList');
    if (!dlg || !input || !list) return;

    var QUICK_ACTIONS = [
        @canEdit
        @if(\Illuminate\Support\Facades\Route::has('invoices.create'))
        { label: 'New Invoice', icon: 'file-plus', group: 'Quick actions', href: '{{ route('invoices.create') }}' },
        @endif
        @if(\Illuminate\Support\Facades\Route::has('quotes.create'))
        { label: 'New Quotation', icon: 'clipboard-list', group: 'Quick actions', href: '{{ route('quotes.create') }}' },
        @endif
        @if(\Illuminate\Support\Facades\Route::has('bills.create'))
        { label: 'New Bill', icon: 'receipt', group: 'Quick actions', href: '{{ route('bills.create') }}' },
        @endif
        @if(\Illuminate\Support\Facades\Route::has('credit_notes.create'))
        { label: 'New Credit Note', icon: 'file-minus', group: 'Quick actions', href: '{{ route('credit_notes.create') }}' },
        @endif
        @if(\Illuminate\Support\Facades\Route::has('recurring.create'))
        { label: 'New Recurring Template', icon: 'refresh-cw', group: 'Quick actions', href: '{{ route('recurring.create') }}' },
        @endif
        @if(\Illuminate\Support\Facades\Route::has('expense_claims.create'))
        { label: 'New Expense Claim', icon: 'wallet', group: 'Quick actions', href: '{{ route('expense_claims.create') }}' },
        @endif
        @if(\Illuminate\Support\Facades\Route::has('time_tracking.create'))
        { label: 'New Time Entry', icon: 'timer', group: 'Quick actions', href: '{{ route('time_tracking.create') }}' },
        @endif
        @if(\Illuminate\Support\Facades\Route::has('budgets.create'))
        { label: 'New Budget', icon: 'piggy-bank', group: 'Quick actions', href: '{{ route('budgets.create') }}' },
        @endif
        @endcanEdit
        { label: 'Toggle dark mode', icon: 'moon', group: 'Quick actions', run: function () { toggleTheme(); } },
        { label: 'Keyboard shortcuts', icon: 'keyboard', group: 'Quick actions', run: function () {
            var o = document.getElementById('shortcutOverlay');
            if (o) o.classList.remove('hidden');
        } }
    ];

    var items = [];
    var rendered = [];
    var activeIndex = 0;

    function iconNameOf(link) {
        var svg = link.querySelector('svg');
        if (svg) {
            var m = (svg.getAttribute('class') || '').match(/lucide-([a-z0-9-]+)/);
            if (m) return m[1];
        }
        var i = link.querySelector('[data-lucide]');
        return i ? i.getAttribute('data-lucide') : 'arrow-right';
    }

    // Pages are derived from the sidebar so the palette can never drift from the nav.
    function collectNavItems() {
        var out = [];
        document.querySelectorAll('#sidebar nav a.nav-link').forEach(function (a) {
            var href = a.getAttribute('href');
            var label = (a.textContent || '').trim().replace(/\s+/g, ' ');
            if (!href || !label) return;
            var groupEl = a.closest('.nav-group');
            var group = 'Navigate';
            if (groupEl) {
                var toggle = groupEl.querySelector('.nav-section-toggle span');
                if (toggle && toggle.textContent.trim()) group = toggle.textContent.trim();
            }
            out.push({ label: label, icon: iconNameOf(a), group: group, href: href });
        });
        return out;
    }

    function buildItems() {
        items = QUICK_ACTIONS.concat(collectNavItems());
    }

    // Subsequence fuzzy match; returns a score (lower is better) or null.
    function score(query, text) {
        query = query.toLowerCase();
        text = text.toLowerCase();
        if (!query) return { score: 0, hits: [] };
        var exact = text.indexOf(query);
        if (exact === 0) return { score: 0, hits: [[0, query.length]] };
        if (exact > 0) return { score: exact, hits: [[exact, query.length]] };

        var ti = 0, hits = [], spread = 0, first = -1;
        for (var qi = 0; qi < query.length; qi++) {
            var found = text.indexOf(query[qi], ti);
            if (found === -1) return null;
            if (first === -1) first = found;
            hits.push([found, 1]);
            spread = found - first;
            ti = found + 1;
        }
        return { score: 100 + spread, hits: hits };
    }

    function highlight(text, hits) {
        if (!hits || !hits.length) return document.createTextNode(text);
        var frag = document.createDocumentFragment();
        var cursor = 0;
        hits.forEach(function (h) {
            if (h[0] > cursor) frag.appendChild(document.createTextNode(text.slice(cursor, h[0])));
            var mark = document.createElement('mark');
            mark.textContent = text.slice(h[0], h[0] + h[1]);
            frag.appendChild(mark);
            cursor = h[0] + h[1];
        });
        if (cursor < text.length) frag.appendChild(document.createTextNode(text.slice(cursor)));
        return frag;
    }

    function render(query) {
        var matches = [];
        items.forEach(function (item, i) {
            var s = score(query || '', item.label);
            if (s) matches.push({ item: item, idx: i, score: s.score, hits: s.hits });
        });
        // Relevance first; ties keep the natural nav order so an empty query
        // reads top-to-bottom like the sidebar.
        matches.sort(function (a, b) { return a.score - b.score || a.idx - b.idx; });
        matches = matches.slice(0, 30);

        // Bucket by group so headers never repeat, groups ordered by best match.
        var buckets = [], byGroup = {};
        matches.forEach(function (m) {
            var g = m.item.group || 'Navigate';
            if (!byGroup[g]) { byGroup[g] = []; buckets.push(g); }
            byGroup[g].push(m);
        });

        list.textContent = '';
        rendered = [];
        if (!matches.length) {
            var empty = document.createElement('div');
            empty.className = 'ob-cmd-empty';
            empty.textContent = 'No matching pages or actions.';
            list.appendChild(empty);
            return;
        }

        buckets.forEach(function (g) {
            var head = document.createElement('div');
            head.className = 'ob-cmd-group';
            head.textContent = g;
            list.appendChild(head);
            byGroup[g].forEach(function (m) { list.appendChild(rowFor(m)); });
        });

        activeIndex = 0;
        setActive(0);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function rowFor(m) {
        var row = document.createElement('button');
        row.type = 'button';
        row.className = 'ob-cmd-item';
        row.setAttribute('role', 'option');
        row.setAttribute('aria-selected', 'false');

        var icon = document.createElement('i');
        icon.setAttribute('data-lucide', m.item.icon || 'arrow-right');
        icon.setAttribute('aria-hidden', 'true');
        icon.className = 'ob-cmd-item-icon';

        var label = document.createElement('span');
        label.className = 'ob-cmd-item-label';
        label.appendChild(highlight(m.item.label, m.hits));

        row.appendChild(icon);
        row.appendChild(label);
        if (m.item.href) {
            var hint = document.createElement('span');
            hint.className = 'ob-cmd-item-hint';
            hint.textContent = 'Go';
            row.appendChild(hint);
        }
        row.addEventListener('click', function () { activate(m.item); });
        row.addEventListener('mousemove', function () { setActive(rendered.indexOf(row)); });
        row.__item = m.item;
        rendered.push(row);
        return row;
    }

    function setActive(idx) {
        if (!rendered.length) return;
        activeIndex = (idx + rendered.length) % rendered.length;
        rendered.forEach(function (r, i) {
            r.classList.toggle('active', i === activeIndex);
            r.setAttribute('aria-selected', i === activeIndex ? 'true' : 'false');
        });
        var el = rendered[activeIndex];
        if (el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
    }

    function activate(item) {
        close();
        if (item.run) { item.run(); return; }
        if (item.href) window.location.href = item.href;
    }

    function open() {
        buildItems();
        render('');
        if (!dlg.open) dlg.showModal();
        input.value = '';
        input.focus();
    }
    function close() {
        if (dlg.open) dlg.close();
    }

    input.addEventListener('input', function () { render(input.value.trim()); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); setActive(activeIndex + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(activeIndex - 1); }
        else if (e.key === 'Enter') {
            e.preventDefault();
            var el = rendered[activeIndex];
            if (el && el.__item) activate(el.__item);
        }
    });
    dlg.addEventListener('cancel', function (e) { e.preventDefault(); close(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) close(); });

    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            if (dlg.open) { close(); } else { open(); }
        }
    });

    window.obCommandPalette = { open: open, close: close };

    var trigger = document.getElementById('cmdPaletteTrigger');
    if (trigger) trigger.addEventListener('click', open);
    var isMac = /Mac|iPhone|iPad|iPod/.test(navigator.platform || navigator.userAgent || '');
    var kbdHint = document.getElementById('cmdPaletteKbd');
    if (kbdHint) kbdHint.textContent = isMac ? '\u2318K' : 'Ctrl K';
})();
</script>
