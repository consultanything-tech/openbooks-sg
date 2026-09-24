<script>
    // ── Theme management ──
    function updateThemeIcons() {
        const isDark = document.documentElement.classList.contains('dark');
        const sunIcon = document.getElementById('themeIconSun');
        const moonIcon = document.getElementById('themeIconMoon');
        const label = document.getElementById('themeLabel');
        if (sunIcon) sunIcon.style.display = isDark ? '' : 'none';
        if (moonIcon) moonIcon.style.display = isDark ? 'none' : '';
        if (label) label.textContent = isDark ? 'Light' : 'Dark';
    }

    function toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
            localStorage.setItem('theme', 'dark');
        }
        updateThemeIcons();
    }

    // ── Sidebar ──
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        sidebar.classList.toggle('sidebar-open');
        backdrop.classList.toggle('active');
        const isOpen = sidebar.classList.contains('sidebar-open');
        if (menuBtn) menuBtn.setAttribute('aria-expanded', isOpen);
        if (isOpen) {
            const firstLink = sidebar.querySelector('nav a');
            if (firstLink) firstLink.focus();
        }
    }
    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        sidebar.classList.remove('sidebar-open');
        backdrop.classList.remove('active');
        if (menuBtn) { menuBtn.setAttribute('aria-expanded', 'false'); menuBtn.focus(); }
    }

    // ── Sidebar: collapsible nav sections (state persisted per user) ──
    function initSidebarGroups() {
        const groups = document.querySelectorAll('.nav-group');
        if (!groups.length) return;
        let saved = null;
        try { saved = JSON.parse(localStorage.getItem('ob-sidebar-groups') || 'null'); } catch (e) { saved = null; }

        const apply = (group, open) => {
            group.classList.toggle('collapsed', !open);
            const toggle = group.querySelector('.nav-section-toggle');
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        groups.forEach(group => {
            const key = group.dataset.group;
            const hasActive = !!group.querySelector('.nav-link.active');
            // Section holding the current page always stays open; otherwise
            // fall back to the saved preference, defaulting to collapsed so
            // the menu stays short.
            const open = hasActive ? true : (saved ? saved[key] !== false : false);
            apply(group, open);

            const toggle = group.querySelector('.nav-section-toggle');
            if (toggle) {
                toggle.addEventListener('click', () => {
                    const nowCollapsed = !group.classList.contains('collapsed');
                    apply(group, !nowCollapsed);
                    const state = {};
                    document.querySelectorAll('.nav-group').forEach(g => {
                        state[g.dataset.group] = !g.classList.contains('collapsed');
                    });
                    localStorage.setItem('ob-sidebar-groups', JSON.stringify(state));
                });
            }
        });
    }
    // Sidebar markup sits above this script, so apply immediately (no flash)
    initSidebarGroups();

    // ── WCAG: Screen reader announcements ──
    function announceToSR(message) {
        const el = document.getElementById('sr-announcements');
        if (el) { el.textContent = ''; setTimeout(() => { el.textContent = message; }, 100); }
    }

    // ── WCAG: Focus trap for modals ──
    // Re-queries focusable elements on every Tab so dynamically-injected modal
    // content is handled, and restores focus to the trigger on close.
    var obLastFocus = null;
    document.addEventListener('focusin', function (e) {
        if (e.target && e.target.closest && !e.target.closest('dialog,[role="dialog"]')) {
            obLastFocus = e.target;
        }
    });
    function trapFocus(element) {
        if (!element || element.dataset.obTrap === '1') return;
        element.dataset.obTrap = '1';
        const sel = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
        element.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            const focusable = Array.prototype.filter.call(element.querySelectorAll(sel), function (el) {
                return el.offsetParent !== null || el === document.activeElement;
            });
            if (focusable.length === 0) return;
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
        function restore() { if (obLastFocus && typeof obLastFocus.focus === 'function') { try { obLastFocus.focus(); } catch (err) {} } }
        element.addEventListener('close', restore);                 // native <dialog>
        element.addEventListener('ob:closed', restore);            // custom signal for role=dialog
    }
    window.obTrapFocus = trapFocus;
    // Auto-trap dialogs injected later (e.g. fetch-loaded modals).
    if (typeof MutationObserver !== 'undefined') {
        var obTrapObserver = new MutationObserver(function (muts) {
            muts.forEach(function (m) {
                m.addedNodes && m.addedNodes.forEach(function (n) {
                    if (n.nodeType !== 1) return;
                    if (n.matches && n.matches('dialog,[role="dialog"]')) trapFocus(n);
                    if (n.querySelectorAll) n.querySelectorAll('dialog,[role="dialog"]').forEach(trapFocus);
                });
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            obTrapObserver.observe(document.body, { childList: true, subtree: true });
        });
    }

    // ── Modal loading overlay (for fetch-populated modals) ──
    function obModalLoading(modalEl, show) {
        if (!modalEl) return;
        if (show) {
            if (modalEl.querySelector('.ob-modal-loading')) return;
            modalEl.style.position = 'relative';
            var ov = document.createElement('div');
            ov.className = 'ob-modal-loading';
            ov.setAttribute('aria-busy', 'true');
            ov.setAttribute('aria-label', 'Loading');
            ov.innerHTML = '<div class="ob-spinner" role="status"></div>';
            modalEl.appendChild(ov);
            modalEl.querySelectorAll('input, select, textarea, button[type="submit"]').forEach(function (f) { f.disabled = true; });
        } else {
            var existing = modalEl.querySelector('.ob-modal-loading');
            if (existing) existing.remove();
            modalEl.querySelectorAll('input, select, textarea, button[type="submit"]').forEach(function (f) { f.disabled = false; });
        }
    }
    window.obModalLoading = obModalLoading;

    // ── Re-initialize Lucide icons after dynamic content ──
    function refreshIcons() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // ── Keyboard: Escape closes sidebar and dropdowns ──
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const sidebar = document.getElementById('sidebar');
            if (sidebar && sidebar.classList.contains('sidebar-open')) closeSidebar();
            const nd = document.getElementById('notifDropdown');
            if (nd) nd.classList.add('hidden');
            const ld = document.getElementById('localeDropdown');
            if (ld) ld.classList.add('hidden');
        }
    });

    // ── Notification system ──
    function toggleNotifications() {
        const dd = document.getElementById('notifDropdown');
        const wrapper = document.getElementById('notificationWrapper');
        const btn = wrapper ? wrapper.querySelector('button') : null;
        dd.classList.toggle('hidden');
        const isOpen = !dd.classList.contains('hidden');
        if (btn) btn.setAttribute('aria-expanded', isOpen);
        if (isOpen) fetchNotifications();
    }

    function fetchNotifications() {
        fetch('{{ route("notifications.index") }}', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('notifList');
            const badge = document.getElementById('notifBadge');
            if (data.unread_count > 0) {
                badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                badge.classList.remove('hidden');
                badge.classList.add('flex');
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('flex');
            }
            if (!data.notifications || data.notifications.length === 0) {
                list.innerHTML = '<div class="px-4 py-6 text-center text-sm text-slate-500">No notifications</div>';
                return;
            }
            list.innerHTML = data.notifications.map(n => `
                <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition cursor-pointer ${n.is_read ? '' : 'bg-indigo-50/50 dark:bg-indigo-500/5'}" onclick="readNotification(${n.id}, '${n.url || ''}')">
                    <div class="flex items-start gap-2.5">
                        <i data-lucide="info" class="w-4 h-4 text-indigo-500 mt-0.5"></i>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">${n.title}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">${n.message || ''}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">${timeAgo(n.created_at)}</p>
                        </div>
                        ${n.is_read ? '' : '<span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0 mt-1"></span>'}
                    </div>
                </div>
            `).join('');
            refreshIcons();
        }).catch(() => {});
    }

    function readNotification(id, url) {
        fetch(`/notifications/${id}/read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
        .then(() => { fetchNotifications(); if (url) window.location.href = url; });
    }
    function markAllRead() {
        fetch('{{ route("notifications.read_all") }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
        .then(() => fetchNotifications());
    }
    function timeAgo(dateStr) {
        const d = new Date(dateStr), now = new Date(), diff = Math.floor((now - d) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
        return Math.floor(diff/86400) + 'd ago';
    }

    // ── Close dropdowns on outside click ──
    document.addEventListener('click', function(e) {
        const nw = document.getElementById('notificationWrapper');
        if (nw && !nw.contains(e.target)) {
            const dd = document.getElementById('notifDropdown');
            if (dd) dd.classList.add('hidden');
        }
        const lw = document.getElementById('localeWrapper');
        if (lw && !lw.contains(e.target)) {
            const dd = document.getElementById('localeDropdown');
            if (dd) dd.classList.add('hidden');
        }
    });

    // ── Date fields: click anywhere in the field opens the native calendar ──
    document.addEventListener('click', function(e) {
        var el = e.target && e.target.closest ? e.target.closest('input[type="date"]') : null;
        if (!el || el.disabled || el.readOnly) return;
        var rect = el.getBoundingClientRect();
        if (e.clientX > rect.right - 32) return; // calendar icon zone opens natively
        if (typeof el.showPicker === 'function') {
            try { el.showPicker(); } catch (err) { /* unsupported browser: icon still works */ }
        }
    });

    // ── Live refresh: when the AI chat performs a write action, refresh any
    //    open page (this tab and other tabs) that displays the affected data.
    //    Scroll is preserved, and create/edit forms with unsaved input are
    //    never reloaded so a draft is not wiped. ──
    (function () {
        var AFFECTED = {
            create_invoice: ['/invoices', '/dashboard', '/quotes', '/customers'],
            create_quote:   ['/quotes', '/dashboard', '/customers'],
            create_bill:    ['/bills', '/dashboard', '/vendors'],
            add_customer:   ['/customers', '/dashboard'],
            add_vendor:     ['/vendors', '/dashboard'],
            add_item:       ['/items', '/products', '/dashboard'],
            add_tax:        ['/settings', '/dashboard']
        };

        function currentPath() {
            return window.location.pathname.replace(/\/+$/, '') || '/';
        }

        // Boundary-aware match: '/invoices' matches '/invoices' and
        // '/invoices/12' but not '/invoices-foo'.
        function pathAffected(here, paths) {
            return paths.some(function (p) {
                return here === p || here.indexOf(p + '/') === 0;
            });
        }

        // True on create/edit screens, or whenever a visible form holds
        // unsaved user input — both cases must not be auto-reloaded.
        function hasUnsavedWork() {
            var here = currentPath();
            if (/(^|\/)(create|edit)(\/|$)/.test(here)) return true;
            var fields = document.querySelectorAll('form input, form textarea, form select');
            for (var i = 0; i < fields.length; i++) {
                var el = fields[i];
                if (el.type === 'hidden' || el.disabled || el.offsetParent === null) continue;
                if (el.type === 'checkbox' || el.type === 'radio') {
                    if (el.checked !== el.defaultChecked) return true;
                } else if (el.value !== el.defaultValue) {
                    return true;
                }
            }
            return false;
        }

        function refreshIfRelevant(action, isOrigin) {
            var paths = AFFECTED[action] || ['/dashboard'];
            if (!pathAffected(currentPath(), paths)) return;
            if (hasUnsavedWork()) return; // protect drafts / typed filters
            // Only the tab where the action happened preserves its open chat.
            if (isOrigin && typeof window.saveAiSnapshot === 'function') {
                window.saveAiSnapshot();
            }
            var y = window.scrollY;
            window.addEventListener('pageshow', function () {
                window.scrollTo(0, y);
            }, { once: true });
            window.location.reload();
        }

        // Cross-tab sync: a write action in one tab refreshes matching tabs.
        var channel = null;
        if ('BroadcastChannel' in window) {
            channel = new BroadcastChannel('openbooks-sync');
            channel.onmessage = function (ev) {
                var action = ev && ev.data ? ev.data.action : null;
                if (action) refreshIfRelevant(action, false);
            };
        }

        document.addEventListener('ob:data-changed', function (e) {
            var action = e && e.detail ? e.detail.action : null;
            if (!action) return;
            if (channel) { try { channel.postMessage({ action: action }); } catch (err) {} }
            refreshIfRelevant(action, true);
        });
    })();

    // ── Confirmation modal (replaces native confirm()) ──
    function obConfirm(message, opts) {
        opts = opts || {};
        return new Promise(function (resolve) {
            var dlg = document.getElementById('obConfirmDialog');
            if (!dlg) { resolve(window.confirm(message)); return; }
            dlg.querySelector('#obConfirmMessage').textContent = message || 'Are you sure?';
            dlg.querySelector('#obConfirmTitle').textContent = opts.title || 'Are you sure?';
            var ok = dlg.querySelector('#obConfirmOk');
            ok.textContent = opts.confirmLabel || 'Confirm';
            ok.className = 'btn ' + (opts.danger === false ? 'btn-primary' : 'btn-danger');
            function done(val) {
                ok.removeEventListener('click', onOk);
                cancel.removeEventListener('click', onCancel);
                dlg.removeEventListener('close', onClose);
                if (dlg.open) dlg.close();
                resolve(val);
            }
            function onOk() { done(true); }
            function onCancel() { done(false); }
            function onClose() { done(false); }
            ok.addEventListener('click', onOk);
            var cancel = dlg.querySelector('#obConfirmCancel');
            cancel.addEventListener('click', onCancel);
            dlg.addEventListener('close', onClose);
            if (dlg.open) dlg.close();
            dlg.showModal();
            cancel.focus();
        });
    }
    window.obConfirm = obConfirm;

    // ── Copy to clipboard ──
    function copyToClipboard(text, btn) {
        var done = function () {
            if (btn) {
                btn.classList.add('btn-icon-success');
                setTimeout(function () { btn.classList.remove('btn-icon-success'); }, 1200);
            }
            announceToSR('Copied to clipboard');
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () {});
        } else {
            var ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); done(); } catch (e) {}
            document.body.removeChild(ta);
        }
    }
    document.addEventListener('click', function (e) {
        var el = e.target && e.target.closest ? e.target.closest('[data-copy]') : null;
        if (!el) return;
        e.preventDefault();
        copyToClipboard(el.getAttribute('data-copy'), el);
    });

    // ── Toast notifications ──
    var OB_TOAST_ICONS = { success: 'check-circle', error: 'alert-circle', warning: 'alert-triangle', info: 'info' };
    function obToast(message, opts) {
        opts = opts || {};
        var type = OB_TOAST_ICONS[opts.type] ? opts.type : 'info';
        var host = document.getElementById('obToasts');
        if (!host) {
            host = document.createElement('div');
            host.id = 'obToasts';
            document.body.appendChild(host);
        }

        var el = document.createElement('div');
        el.className = 'ob-toast ob-toast-' + type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');

        var icon = document.createElement('i');
        icon.setAttribute('data-lucide', OB_TOAST_ICONS[type]);
        icon.setAttribute('aria-hidden', 'true');
        icon.className = 'ob-toast-icon';

        var body = document.createElement('div');
        body.className = 'ob-toast-body';
        var msg = document.createElement('div');
        msg.className = 'ob-toast-msg';
        msg.textContent = message;
        body.appendChild(msg);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'ob-toast-close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.innerHTML = '<i data-lucide="x" aria-hidden="true" style="width:0.875rem;height:0.875rem"></i>';

        el.appendChild(icon);
        el.appendChild(body);
        el.appendChild(close);

        // Errors stay until dismissed so important failures are never missed.
        var duration = typeof opts.duration === 'number' ? opts.duration : (type === 'error' ? 0 : 4500);
        var timer = null;
        var closed = false;

        function dismiss() {
            if (closed) return;
            closed = true;
            if (timer) { clearTimeout(timer); timer = null; }
            el.classList.add('leaving');
            setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 200);
        }
        close.addEventListener('click', dismiss);

        if (opts.actions && opts.actions.length) {
            var bar = document.createElement('div');
            bar.className = 'ob-toast-actions';
            opts.actions.forEach(function (a) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'ob-toast-action';
                b.textContent = a.label;
                b.addEventListener('click', function () {
                    if (typeof a.onClick === 'function') a.onClick();
                    if (a.keepOpen !== true) dismiss();
                });
                bar.appendChild(b);
            });
            body.appendChild(bar);
            duration = Math.max(duration, 8000);
        }

        var prog = null;
        if (duration > 0) {
            prog = document.createElement('div');
            prog.className = 'ob-toast-progress';
            el.appendChild(prog);
            requestAnimationFrame(function () {
                prog.style.transitionDuration = duration + 'ms';
                prog.style.width = '0%';
            });
            timer = setTimeout(dismiss, duration);
            // Hovering pauses the countdown so the message can be read.
            el.addEventListener('mouseenter', function () {
                if (closed) return;
                if (timer) { clearTimeout(timer); timer = null; }
                if (prog) { prog.style.transitionDuration = '0ms'; prog.style.width = '0%'; }
            });
            el.addEventListener('mouseleave', function () {
                if (closed || timer) return;
                if (prog) { prog.style.transitionDuration = '2000ms'; prog.style.width = '0%'; }
                timer = setTimeout(dismiss, 2000);
            });
        }

        host.appendChild(el);
        while (host.children.length > 4) host.removeChild(host.firstChild);
        refreshIcons();
        announceToSR(message);
        return { el: el, dismiss: dismiss };
    }
    window.obToast = obToast;

    // ── Sortable table columns (client-side, current page) ──
    function obSortValue(cell) {
        if (!cell) return '';
        var v = cell.getAttribute('data-sort-value');
        if (v !== null) return v;
        return (cell.textContent || '').trim();
    }
    function obMaybeDate(s) {
        s = String(s).trim();
        // Only treat unambiguous date shapes as dates so codes like "INV-0001" stay text.
        var ok = /^\d{1,4}[\/-]\d{1,2}[\/-]\d{1,4}$/.test(s)
            || /^\d{1,2}\s+[A-Za-z]{3,9}\.?(\s+[A-Za-z]{3}\s+)?\d{4}$/.test(s)
            || /^\d{4}-\d{2}-\d{2}/.test(s);
        if (!ok) return null;
        var t = Date.parse(s);
        return isNaN(t) ? null : t;
    }
    function obSortCompare(a, b, dir) {
        var ad = obMaybeDate(a), bd = obMaybeDate(b);
        var out;
        if (ad !== null && bd !== null) {
            out = ad - bd;
        } else {
            var an = parseFloat(String(a).replace(/[^0-9.\-]/g, ''));
            var bn = parseFloat(String(b).replace(/[^0-9.\-]/g, ''));
            var aNum = String(a).trim() !== '' && !isNaN(an);
            var bNum = String(b).trim() !== '' && !isNaN(bn);
            if (aNum && bNum) {
                out = an - bn;
            } else {
                out = String(a).localeCompare(String(b), undefined, { numeric: true, sensitivity: 'base' });
            }
        }
        return dir === 'asc' ? out : -out;
    }
    function initSortableTables(root) {
        var tables = (root || document).querySelectorAll('table#dataTable:not([data-sortable="off"]), table[data-sortable]:not([data-sortable="off"])');
        tables.forEach(function (table) {
            if (table.dataset.obSortReady === '1') return;
            var tbody = table.tBodies[0];
            var headRow = table.querySelector('thead tr');
            if (!tbody || !headRow) return;
            table.dataset.obSortReady = '1';
            table.setAttribute('data-sortable', table.getAttribute('data-sortable') || 'on');

            var headers = Array.prototype.slice.call(headRow.children);
            headers.forEach(function (th, idx) {
                if (th.hasAttribute('data-no-sort') || th.hasAttribute('colspan') || th.hasAttribute('rowspan')) return;
                // Never hijack columns holding controls (select-all checkbox) or the row-action column.
                if (th.querySelector('input, select, button, a')) return;
                var label = th.textContent.trim();
                if (!label || /^actions?$/i.test(label)) return;

                th.classList.add('ob-sortable');
                th.setAttribute('tabindex', '0');
                th.setAttribute('role', 'columnheader');

                th.textContent = '';
                var inner = document.createElement('span');
                inner.className = 'ob-sort-inner';
                var text = document.createElement('span');
                text.textContent = label;
                var caret = document.createElement('span');
                caret.className = 'ob-sort-caret';
                caret.innerHTML = '<i data-lucide="chevron-down" aria-hidden="true" style="width:0.75rem;height:0.75rem"></i>';
                inner.appendChild(text);
                inner.appendChild(caret);
                th.appendChild(inner);
                th.setAttribute('title', 'Sort by ' + label);
                th.dataset.obSortLabel = label;

                function sortBy(forceDir) {
                    var current = th.getAttribute('aria-sort');
                    var dir = (forceDir === 'asc' || forceDir === 'desc')
                        ? forceDir
                        : (current === 'ascending' ? 'desc' : 'asc');
                    headers.forEach(function (h) { h.removeAttribute('aria-sort'); });
                    th.setAttribute('aria-sort', dir === 'asc' ? 'ascending' : 'descending');

                    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
                    // Keep "no records" placeholder rows in place.
                    if (rows.length < 2) return;
                    rows.sort(function (r1, r2) {
                        return obSortCompare(obSortValue(r1.children[idx]), obSortValue(r2.children[idx]), dir);
                    });
                    var frag = document.createDocumentFragment();
                    rows.forEach(function (r) { frag.appendChild(r); });
                    tbody.appendChild(frag);
                    announceToSR('Sorted by ' + label + ', ' + (dir === 'asc' ? 'ascending' : 'descending'));
                    // Persist sort state in the URL so it survives reload/navigation.
                    try {
                        var u = new URL(window.location.href);
                        u.searchParams.set('ob_sort', label);
                        u.searchParams.set('ob_dir', dir);
                        history.replaceState(null, '', u);
                    } catch (e) {}
                }
                th._obSortBy = sortBy;

                th.addEventListener('click', function () { sortBy(); });
                th.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sortBy(); }
                });
            });

            // Restore a previously-applied sort from the URL.
            try {
                var sp = new URL(window.location.href).searchParams;
                var savedLabel = sp.get('ob_sort');
                var savedDir = sp.get('ob_dir') === 'desc' ? 'desc' : 'asc';
                if (savedLabel) {
                    headers.forEach(function (h) {
                        if (h._obSortBy && h.dataset.obSortLabel === savedLabel) { h._obSortBy(savedDir); }
                    });
                }
            } catch (e) {}

            // Mobile sort control — the card layout hides <thead> at <=768px, so
            // expose the same client-side sorting through a select + direction toggle.
            var sortableHeaders = headers.filter(function (h) { return h._obSortBy; });
            if (sortableHeaders.length > 1 && !table.dataset.obMobileSort) {
                table.dataset.obMobileSort = '1';
                var bar = document.createElement('div');
                bar.className = 'ob-mobile-sort';
                var lbl = document.createElement('label');
                lbl.textContent = 'Sort';
                var sel = document.createElement('select');
                sel.setAttribute('aria-label', 'Sort by column');
                var ph = document.createElement('option');
                ph.value = ''; ph.textContent = 'Sort by…';
                sel.appendChild(ph);
                sortableHeaders.forEach(function (h, i) {
                    var o = document.createElement('option');
                    o.value = String(i);
                    o.textContent = h.dataset.obSortLabel;
                    sel.appendChild(o);
                });
                var dir = 'asc';
                var dirBtn = document.createElement('button');
                dirBtn.type = 'button';
                dirBtn.innerHTML = '<i data-lucide="arrow-up-down" aria-hidden="true" style="width:.85rem;height:.85rem"></i><span>Asc</span>';
                function applyMobileSort() {
                    if (sel.value === '') return;
                    var h = sortableHeaders[parseInt(sel.value, 10)];
                    if (h && h._obSortBy) { h._obSortBy(dir); }
                }
                dirBtn.addEventListener('click', function () {
                    dir = dir === 'asc' ? 'desc' : 'asc';
                    dirBtn.querySelector('span').textContent = dir === 'asc' ? 'Asc' : 'Desc';
                    applyMobileSort();
                });
                sel.addEventListener('change', applyMobileSort);
                bar.appendChild(lbl); bar.appendChild(sel); bar.appendChild(dirBtn);
                var anchor = table.closest('.overflow-x-auto') || table;
                if (anchor.parentNode) { anchor.parentNode.insertBefore(bar, anchor); }
            }
            refreshIcons();
        });
    }
    window.initSortableTables = initSortableTables;

    // ── Global table search with "no matches" state + URL persistence ──
    // Replaces the per-view filterTable() copies. A row is treated as a data row
    // unless it is a single full-width cell (the server empty / no-match placeholder).
    function obIsDataRow(tr) {
        return !(tr.children.length === 1 && tr.children[0].colSpan > 1);
    }
    function filterTable() {
        var input = document.getElementById('searchInput');
        var table = document.getElementById('dataTable');
        if (!input || !table || !table.tBodies[0]) return;
        var tbody = table.tBodies[0];
        var raw = input.value;
        var q = raw.trim().toLowerCase();
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(obIsDataRow);
        var visible = 0;
        rows.forEach(function (tr) {
            var hit = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
            tr.style.display = hit ? '' : 'none';
            if (hit) visible++;
        });

        var headRow = table.querySelector('thead tr');
        var cols = headRow ? headRow.children.length : 1;
        var nm = tbody.querySelector('tr.ob-no-match');
        if (rows.length && visible === 0) {
            if (!nm) {
                nm = document.createElement('tr');
                nm.className = 'ob-no-match';
                var td = document.createElement('td');
                td.colSpan = cols;
                td.className = 'px-6 py-10 text-center';
                td.innerHTML = '<div class="flex flex-col items-center gap-2 text-slate-500 dark:text-slate-400">'
                    + '<i data-lucide="search-x" class="w-6 h-6" aria-hidden="true"></i>'
                    + '<p class="text-xs">No records match &ldquo;<span class="ob-nm-q font-semibold"></span>&rdquo;.</p></div>';
                nm.appendChild(td);
                tbody.appendChild(nm);
            }
            nm.querySelector('.ob-nm-q').textContent = raw;
            nm.style.display = '';
            refreshIcons();
        } else if (nm) {
            nm.style.display = 'none';
        }

        try {
            var u = new URL(window.location.href);
            if (raw.trim()) { u.searchParams.set('q', raw.trim()); } else { u.searchParams.delete('q'); }
            history.replaceState(null, '', u);
        } catch (e) {}
    }
    window.filterTable = filterTable;

    function restoreTableSearch() {
        var input = document.getElementById('searchInput');
        if (!input) return;
        try {
            var q = new URL(window.location.href).searchParams.get('q');
            if (q) { input.value = q; filterTable(); }
        } catch (e) {}
    }
    window.restoreTableSearch = restoreTableSearch;

    // ── Bulk row selection + actions (delete / export) ──
    // Views opt in by rendering a `.ob-bulk-bar` (with data-bulk-delete /
    // data-bulk-export URLs) plus a `#selectAll` checkbox and per-row
    // `.ob-row-check` inputs inside the table.
    function obInitBulk() {
        document.querySelectorAll('.ob-bulk-bar').forEach(function (bar) {
            if (bar.dataset.obBulkReady === '1') return;
            bar.dataset.obBulkReady = '1';
            var card = bar.parentElement;
            var table = card ? card.querySelector('table') : null;
            if (!table) return;
            var selectAll = table.querySelector('#selectAll, thead input[type="checkbox"]');
            function checks() { return Array.prototype.slice.call(table.querySelectorAll('.ob-row-check')); }
            function ids() { return checks().filter(function (x) { return x.checked; }).map(function (x) { return x.value; }); }
            function update() {
                var c = checks();
                var selected = c.filter(function (x) { return x.checked; });
                bar.classList.toggle('active', selected.length > 0);
                var cnt = bar.querySelector('.ob-bulk-count');
                if (cnt) cnt.textContent = selected.length + ' selected';
                if (selectAll) {
                    selectAll.checked = c.length > 0 && selected.length === c.length;
                    selectAll.indeterminate = selected.length > 0 && selected.length < c.length;
                }
            }
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checks().forEach(function (x) { x.checked = selectAll.checked; });
                    update();
                });
            }
            table.addEventListener('change', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains('ob-row-check')) update();
            });
            function postForm(url, list) {
                if (!url) return;
                var f = document.createElement('form');
                f.method = 'POST'; f.action = url; f.style.display = 'none';
                var meta = document.querySelector('meta[name="csrf-token"]');
                var t = document.createElement('input');
                t.type = 'hidden'; t.name = '_token'; t.value = meta ? meta.getAttribute('content') : '';
                f.appendChild(t);
                list.forEach(function (id) {
                    var i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'ids[]'; i.value = id; f.appendChild(i);
                });
                document.body.appendChild(f); f.submit();
            }
            bar.addEventListener('click', function (e) {
                var btn = e.target.closest ? e.target.closest('[data-bulk]') : null;
                if (!btn) return;
                var list = ids();
                if (!list.length) return;
                var kind = btn.getAttribute('data-bulk');
                if (kind === 'export') { postForm(bar.dataset.bulkExport, list); return; }
                if (kind === 'delete') {
                    var n = list.length;
                    var msg = 'Delete ' + n + ' selected record' + (n > 1 ? 's' : '') + '? You will be able to undo this.';
                    var p = (typeof obConfirm === 'function')
                        ? obConfirm(msg, { title: 'Delete selected', confirmLabel: 'Yes, delete' })
                        : Promise.resolve(window.confirm(msg));
                    p.then(function (ok) { if (ok) postForm(bar.dataset.bulkDelete, list); });
                }
            });
            update();
        });
    }
    window.obInitBulk = obInitBulk;

    // ── Initialize on DOM ready ──
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Sortable table headers
        initSortableTables(document);

        // Re-apply a search term carried in the URL (?q=...)
        restoreTableSearch();

        // Bulk row selection + actions
        obInitBulk();

        // Confirmation modal: upgrade legacy onsubmit/onclick confirm(...) to the styled dialog
        document.querySelectorAll('form[onsubmit*="confirm("], [onclick*="confirm("]').forEach(function (el) {
            var attr = el.getAttribute('onsubmit') || el.getAttribute('onclick') || '';
            var m = attr.match(/confirm\(\s*(["'])((?:\\.|(?!\1).)*)\1\s*\)/);
            var msg = m ? m[2].replace(/\\'/g, "'").replace(/\\"/g, '"') : 'Are you sure?';
            var isForm = el.tagName === 'FORM';
            if (isForm) { el.removeAttribute('onsubmit'); } else { el.removeAttribute('onclick'); }
            var ask = function (submitter) {
                return obConfirm(msg, { title: 'Please confirm', confirmLabel: 'Yes, continue' }).then(function (okVal) {
                    if (!okVal) return;
                    el.dataset.obConfirmed = '1';
                    if (isForm) { el.requestSubmit(submitter || undefined); }
                    else if (el.form) { el.form.requestSubmit(el); }
                    else { el.click(); }
                });
            };
            if (isForm) {
                el.addEventListener('submit', function (e) {
                    if (el.dataset.obConfirmed === '1') { el.dataset.obConfirmed = ''; return; }
                    e.preventDefault();
                    ask(e.submitter);
                });
            } else {
                el.addEventListener('click', function (e) {
                    if (el.dataset.obConfirmed === '1') { el.dataset.obConfirmed = ''; return; }
                    e.preventDefault();
                    ask(null);
                });
            }
        });

        // Loading states: block double submits and show a spinner on the button(s)
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (e.defaultPrevented) return;
            if (form.getAttribute('data-loading') === 'off') return;
            var btns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            if (!btns.length) return;
            btns.forEach(function (b) { b.disabled = true; b.classList.add('is-loading'); });
            form.setAttribute('aria-busy', 'true');
            setTimeout(function () {
                btns.forEach(function (b) { b.disabled = false; b.classList.remove('is-loading'); });
                form.removeAttribute('aria-busy');
            }, 8000);
        });

        // Back to top
        (function () {
            var btt = document.getElementById('backToTop');
            var main = document.getElementById('main-content');
            if (!btt || !main) return;
            main.addEventListener('scroll', function () {
                btt.classList.toggle('visible', main.scrollTop > 600);
            }, { passive: true });
            btt.addEventListener('click', function () { main.scrollTo({ top: 0, behavior: 'smooth' }); });
        })();


        // Theme icons
        updateThemeIcons();

        // Focus trap on dialogs
        document.querySelectorAll('[role="dialog"], dialog').forEach(function(modal) {
            trapFocus(modal);
        });

        // Notification badge count
        fetch('{{ route("notifications.index") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.unread_count > 0) {
                const badge = document.getElementById('notifBadge');
                if (badge) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.classList.remove('hidden');
                    badge.classList.add('flex');
                }
            }
        }).catch(() => {});

        // PWA Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    });
</script>

@include('partials.combobox')

@yield('scripts')
