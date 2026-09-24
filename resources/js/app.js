import { createIcons, icons } from 'lucide';

// Initialize all Lucide icons on the page
function initIcons() {
    createIcons({ icons });
}

// Re-initialize icons after dynamic content changes
function refreshIcons() {
    createIcons({ icons });
}

// Theme management
function initTheme() {
    const stored = localStorage.getItem('theme');
    if (stored === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('light');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
    }
    updateThemeIcons();
}

function toggleTheme() {
    const isDark = document.documentElement.classList.contains('dark');
    if (isDark) {
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

function updateThemeIcons() {
    const isDark = document.documentElement.classList.contains('dark');
    const sunIcon = document.getElementById('themeIconSun');
    const moonIcon = document.getElementById('themeIconMoon');
    const label = document.getElementById('themeLabel');
    if (sunIcon) sunIcon.style.display = isDark ? '' : 'none';
    if (moonIcon) moonIcon.style.display = isDark ? 'none' : '';
    if (label) label.textContent = isDark ? 'Light' : 'Dark';
}

// Sidebar
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

// WCAG: Escape key closes sidebar and dropdowns
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('sidebar-open')) closeSidebar();

        // Close notification dropdown
        const notifDropdown = document.getElementById('notifDropdown');
        if (notifDropdown && !notifDropdown.classList.contains('hidden')) {
            notifDropdown.classList.add('hidden');
        }

        // Close locale dropdown
        const localeDropdown = document.getElementById('localeDropdown');
        if (localeDropdown && !localeDropdown.classList.contains('hidden')) {
            localeDropdown.classList.add('hidden');
        }
    }
});

// Screen reader announcements
function announceToSR(message) {
    const el = document.getElementById('sr-announcements');
    if (el) { el.textContent = ''; setTimeout(() => { el.textContent = message; }, 100); }
}

// Focus trap for modals
function trapFocus(element) {
    const focusableSelectors = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
    const focusableElements = element.querySelectorAll(focusableSelectors);
    if (focusableElements.length === 0) return;
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];
    element.addEventListener('keydown', function(e) {
        if (e.key !== 'Tab') return;
        if (e.shiftKey) {
            if (document.activeElement === firstFocusable) { e.preventDefault(); lastFocusable.focus(); }
        } else {
            if (document.activeElement === lastFocusable) { e.preventDefault(); firstFocusable.focus(); }
        }
    });
}

// Notification system
function toggleNotifications() {
    const dd = document.getElementById('notifDropdown');
    const wrapper = document.getElementById('notificationWrapper');
    const btn = wrapper.querySelector('button');
    dd.classList.toggle('hidden');
    const isOpen = !dd.classList.contains('hidden');
    if (btn) btn.setAttribute('aria-expanded', isOpen);
    if (isOpen) fetchNotifications();
}

function fetchNotifications() {
    const meta = document.querySelector('meta[name="notification-url"]');
    if (!meta) return;
    fetch(meta.content, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
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
    const meta = document.querySelector('meta[name="csrf-token"]');
    fetch(`/notifications/${id}/read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': meta.content, 'Accept': 'application/json' } })
    .then(() => { fetchNotifications(); if (url) window.location.href = url; });
}

function markAllRead() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const urlMeta = document.querySelector('meta[name="notifications-read-all-url"]');
    if (!urlMeta) return;
    fetch(urlMeta.content, { method: 'POST', headers: { 'X-CSRF-TOKEN': meta.content, 'Accept': 'application/json' } })
    .then(() => fetchNotifications());
}

function timeAgo(dateStr) {
    const d = new Date(dateStr), now = new Date(), diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    const notifWrapper = document.getElementById('notificationWrapper');
    if (notifWrapper && !notifWrapper.contains(e.target)) {
        const dd = document.getElementById('notifDropdown');
        if (dd) dd.classList.add('hidden');
    }
    const localeWrapper = document.getElementById('localeWrapper');
    if (localeWrapper && !localeWrapper.contains(e.target)) {
        const dd = document.getElementById('localeDropdown');
        if (dd) dd.classList.add('hidden');
    }
});

// Expose functions globally for inline onclick handlers
window.toggleTheme = toggleTheme;
window.toggleSidebar = toggleSidebar;
window.closeSidebar = closeSidebar;
window.toggleNotifications = toggleNotifications;
window.fetchNotifications = fetchNotifications;
window.readNotification = readNotification;
window.markAllRead = markAllRead;
window.trapFocus = trapFocus;
window.announceToSR = announceToSR;
window.refreshIcons = refreshIcons;

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initIcons();

    // Focus trap on dialogs
    document.querySelectorAll('[role="dialog"], dialog').forEach(function(modal) {
        trapFocus(modal);
    });

    // Fetch notification badge count
    const meta = document.querySelector('meta[name="notification-url"]');
    if (meta) {
        fetch(meta.content, { headers: { 'Accept': 'application/json' } })
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
    }

    // PWA Service Worker Registration
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
});
