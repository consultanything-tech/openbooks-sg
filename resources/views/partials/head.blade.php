<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#2563eb">
@include('partials.favicon')
<meta name="notification-url" content="{{ route('notifications.index') }}">
<meta name="notifications-read-all-url" content="{{ route('notifications.read_all') }}">
<link rel="manifest" href="/manifest.json">
<title>@yield('title', 'Dashboard') - OpenBooks SG</title>

<script>
    // Immediate theme detection to prevent FOUC
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('light');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
    }
</script>

{{-- Inter font --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

{{-- Tailwind CSS v4 browser build --}}
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.1.11"></script>

{{-- Lucide icons --}}
<script src="https://cdn.jsdelivr.net/npm/lucide@1.47.0/dist/umd/lucide.min.js"></script>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>

{{-- Tailwind v4 config --}}
<style type="text/tailwindcss">
    /* Class-based dark mode (matches html.dark toggle, not OS preference) */
    @custom-variant dark (&:where(.dark, .dark *));

    @theme {
        --font-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;

        --color-primary-50: #eff6ff;
        --color-primary-100: #dbeafe;
        --color-primary-200: #bfdbfe;
        --color-primary-300: #93c5fd;
        --color-primary-400: #60a5fa;
        --color-primary-500: #3b82f6;
        --color-primary-600: #2563eb;
        --color-primary-700: #1d4ed8;
        --color-primary-800: #1e40af;
        --color-primary-900: #1e3a8a;

        /* Ocean Blue: remap the indigo utility ramp onto blue so every
           existing indigo-* class across the views renders in the new
           brand hue without touching a single template. */
        --color-indigo-50: #eff6ff;
        --color-indigo-100: #dbeafe;
        --color-indigo-200: #bfdbfe;
        --color-indigo-300: #93c5fd;
        --color-indigo-400: #60a5fa;
        --color-indigo-500: #3b82f6;
        --color-indigo-600: #2563eb;
        --color-indigo-700: #1d4ed8;
        --color-indigo-800: #1e40af;
        --color-indigo-900: #1e3a8a;
        --color-indigo-950: #172554;

        --color-success-50: #ecfdf5;
        --color-success-100: #d1fae5;
        --color-success-500: #10b981;
        --color-success-600: #059669;
        --color-success-700: #047857;

        --color-danger-50: #fff1f2;
        --color-danger-100: #ffe4e6;
        --color-danger-500: #f43f5e;
        --color-danger-600: #e11d48;
        --color-danger-700: #be123c;

        --color-warning-50: #fffbeb;
        --color-warning-100: #fef3c7;
        --color-warning-500: #f59e0b;
        --color-warning-600: #d97706;

        --color-info-50: #f0f9ff;
        --color-info-100: #e0f2fe;
        --color-info-500: #0ea5e9;
        --color-info-600: #0284c7;
    }

</style>

<style>
    /* ── Light mode surface tokens ── */
    :root {
        --surface: #ffffff;
        --surface-raised: #f8fafc;
        --surface-overlay: #f1f5f9;
        --border-color: #dbe4ee;
        --border-subtle: #eef2f7;
        --text-primary: #0f172a;
        --text-secondary: #475569;
        --text-muted: #64748b;
        --text-disabled: #94a3b8;
        --shadow-sm: 0 1px 2px 0 rgba(15, 23, 42, 0.04);
        --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.06), 0 2px 4px -2px rgba(15, 23, 42, 0.04);
        --shadow-lg: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04);
        --sidebar-bg: #ffffff;
        --sidebar-active-bg: #eff6ff;
        --sidebar-active-border: #2563eb;
        --sidebar-active-text: #1d4ed8;
        --header-bg: rgba(255, 255, 255, 0.97);
    }

    /* ── Dark mode surface tokens ── */
    html.dark {
        --surface: #0f172a;
        --surface-raised: #1e293b;
        --surface-overlay: #334155;
        --border-color: rgba(255, 255, 255, 0.08);
        --border-subtle: rgba(255, 255, 255, 0.04);
        --text-primary: #f1f5f9;
        --text-secondary: #cbd5e1;
        --text-muted: #94a3b8;
        --text-disabled: #64748b;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.2);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.2);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -4px rgba(0, 0, 0, 0.3);
        --sidebar-bg: #0f172a;
        --sidebar-active-bg: rgba(59, 130, 246, 0.12);
        --sidebar-active-border: #3b82f6;
        --sidebar-active-text: #93c5fd;
        --header-bg: rgba(15, 23, 42, 0.97);
        color-scheme: dark;
    }

    /* ── Base ── */
    body {
        font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        background-color: var(--surface-raised);
        color: var(--text-primary);
        transition: background-color 0.2s ease, color 0.2s ease;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* ── WCAG 2.1 AA: Visible focus indicator (2.4.7) ── */
    :focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
        border-radius: 4px;
    }
    :focus:not(:focus-visible) {
        outline: none;
    }

    /* ── WCAG 2.1 AA: Reduced motion preference (2.3.3) ── */
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    }

    /* ── Animations ── */
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .animate-slide-down { animation: slideDown 0.3s ease-out; }
    .animate-fade-in { animation: fadeIn 0.2s ease-out; }

    /* ── Card component ── */
    .card, .glass-card {
        background-color: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        box-shadow: var(--shadow-sm);
    }
    .card:hover { box-shadow: var(--shadow-md); }

    /* ── Sidebar ── */
    aside {
        background-color: var(--sidebar-bg);
        border-right: 1px solid var(--border-color);
    }
    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-secondary);
        border-left: 3px solid transparent;
        border-radius: 0 0.375rem 0.375rem 0;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .nav-link:hover {
        background-color: var(--surface-overlay);
        color: var(--text-primary);
    }
    .nav-link.active {
        background-color: var(--sidebar-active-bg);
        border-left-color: var(--sidebar-active-border);
        color: var(--sidebar-active-text);
        font-weight: 600;
    }
    .nav-link.active svg { color: var(--sidebar-active-border); }

    /* ── Collapsible sidebar sections ── */
    .nav-section-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0.875rem 0.75rem 0.375rem;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-muted);
        background: none;
        border: none;
        cursor: pointer;
        transition: color 0.15s ease;
    }
    .nav-section-toggle:hover { color: var(--text-primary); }
    .nav-section-chevron {
        transition: transform 0.2s ease;
        opacity: 0.7;
    }
    .nav-group.collapsed .nav-section-chevron { transform: rotate(-90deg); }
    .nav-group-items {
        display: grid;
        grid-template-rows: 1fr;
        transition: grid-template-rows 0.22s ease;
    }
    .nav-group-items > div {
        overflow: hidden;
        min-height: 0;
        visibility: visible;
        transition: visibility 0s;
    }
    .nav-group.collapsed .nav-group-items { grid-template-rows: 0fr; }
    .nav-group.collapsed .nav-group-items > div {
        visibility: hidden;
        transition: visibility 0s 0.22s;
    }

    /* ── Header ── */
    header {
        background-color: var(--header-bg);
        border-bottom: 1px solid var(--border-color);
        backdrop-filter: blur(8px);
    }

    /* ── Tables ── */
    table thead { background-color: var(--surface-raised); }
    table thead th {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border-color);
        padding: 0.75rem 1rem;
    }
    table tbody td {
        font-size: 0.875rem;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-subtle);
        padding: 0.75rem 1rem;
    }
    table tbody tr:hover { background-color: var(--surface-raised); }

    /* ── Form inputs ── */
    input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
    select, textarea {
        background-color: var(--surface);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 0.875rem;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        width: 100%;
        font-family: inherit;
    }
    /* Fixed-width date pickers for inline filter bars (the base width:100%
       above is unlayered, so Tailwind width utilities cannot override it). */
    .input-date { width: 9.5rem !important; flex: none; }
    input::placeholder, textarea::placeholder { color: var(--text-muted); }
    input:focus, select:focus, textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        outline: none;
    }
    label {
        display: block;
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--text-secondary);
        margin-bottom: 0.375rem;
    }

    /* ── Buttons ── */
    /* ── Button system — single source of truth for every CTA ──
       Tier 1 (icon-only, dense/repetitive: table rows, modal close): .btn-icon [.btn-icon-danger]
       Tier 2 (icon + text, action CTAs): .btn .btn-primary | .btn-secondary | .btn-success | .btn-danger
       Tier 3 (text-only, exit & destructive): .btn .btn-ghost (Cancel) | .btn .btn-danger-text (Delete)
       Icons inside .btn and .btn-icon are auto-sized to 1rem or less — do not add
       explicit width or height utility classes to them.
       Note: keep this unlayered; responsive hiding of .btn elements must be done on a
       wrapper element (Tailwind display utilities cannot override unlayered CSS). */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        line-height: 1rem;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        transition: all 0.15s ease;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
        font-family: inherit;
    }
    .btn svg { width: 1rem; height: 1rem; flex-shrink: 0; }
    .btn:disabled, .btn[disabled] { cursor: not-allowed; opacity: 0.5; }
    .btn-primary { background-color: #2563eb; color: #ffffff; box-shadow: 0 1px 2px 0 rgb(37 99 235 / 0.35); }
    .btn-primary:hover { background-color: #3b82f6; }
    .btn-secondary { background-color: var(--surface); color: var(--text-secondary); border-color: var(--border-color); }
    .btn-secondary:hover { background-color: var(--surface-raised); color: var(--text-primary); }
    .btn-success { background-color: #059669; color: #ffffff; }
    .btn-success:hover { background-color: #10b981; }
    .btn-danger { background-color: #e11d48; color: #ffffff; }
    .btn-danger:hover { background-color: #be123c; }
    .btn-danger-text { color: #e11d48; }
    .btn-danger-text:hover { background-color: #fff1f2; }
    html.dark .btn-danger-text { color: #fda4af; }
    html.dark .btn-danger-text:hover { background-color: rgb(225 29 72 / 0.12); }
    .btn-ghost { color: var(--text-secondary); }
    .btn-ghost:hover { background-color: var(--surface-overlay); color: var(--text-primary); }
    .btn-sm { font-size: 0.6875rem; padding: 0.375rem 0.75rem; }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem;
        border-radius: 0.375rem;
        background-color: transparent;
        color: var(--text-muted);
        border: 1px solid transparent;
        transition: all 0.15s ease;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-icon svg { width: 0.875rem; height: 0.875rem; flex-shrink: 0; }
    .btn-icon:hover { background-color: var(--surface-overlay); color: var(--text-primary); }
    .btn-icon-danger:hover { background-color: #fff1f2; color: #e11d48; }
    html.dark .btn-icon-danger:hover { background-color: rgb(225 29 72 / 0.12); color: #fda4af; }
    .btn-icon-critical { color: #ef4444; }
    .btn-icon-success { color: #10b981; }
    .btn-icon-info { color: #0ea5e9; }
    .btn-icon-warning { color: #f59e0b; }

    /* ── Loading states (form submit) ── */
    @keyframes ob-spin { to { transform: rotate(360deg); } }
    .btn.is-loading, button.is-loading { pointer-events: none; opacity: 0.8; }
    .btn.is-loading::before, button.is-loading::before {
        content: ""; width: 0.875rem; height: 0.875rem; flex-shrink: 0;
        border-radius: 9999px; border: 2px solid currentColor; border-right-color: transparent;
        animation: ob-spin 0.6s linear infinite;
    }

    /* ── Back to top ── */
    #backToTop {
        position: fixed; right: 1.25rem; bottom: 6.5rem; z-index: 40;
        width: 2.5rem; height: 2.5rem; border-radius: 9999px;
        display: inline-flex; align-items: center; justify-content: center;
        background-color: var(--surface); color: var(--text-secondary);
        border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgb(0 0 0 / 0.12);
        opacity: 0; transform: translateY(8px); pointer-events: none;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }
    #backToTop.visible { opacity: 1; transform: translateY(0); pointer-events: auto; }
    #backToTop:hover { color: var(--text-primary); background-color: var(--surface-raised); }

    /* ── Confirmation dialog backdrop + centering ── */
    #obConfirmDialog::backdrop { background: rgba(0, 0, 0, 0.5); }
    #obConfirmDialog[open] { animation: fadeIn 0.15s ease; }
    #obConfirmDialog {
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
        margin: 0;
    }

    /* ── Toasts ── */
    #obToasts {
        position: fixed; top: 1rem; right: 1rem; z-index: 90;
        display: flex; flex-direction: column; gap: 0.5rem;
        width: min(23rem, calc(100vw - 2rem)); pointer-events: none;
    }
    .ob-toast {
        position: relative; overflow: hidden; pointer-events: auto;
        display: flex; align-items: flex-start; gap: 0.625rem;
        padding: 0.75rem 0.875rem; border-radius: 0.875rem;
        background-color: var(--surface); border: 1px solid var(--border-color);
        box-shadow: 0 8px 24px rgb(0 0 0 / 0.14);
        font-size: 0.8125rem; font-weight: 500; color: var(--text-primary);
        animation: ob-toast-in 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .ob-toast.leaving { animation: ob-toast-out 0.18s ease forwards; }
    @keyframes ob-toast-in { from { opacity: 0; transform: translateX(1rem) scale(0.98); } to { opacity: 1; transform: none; } }
    @keyframes ob-toast-out { to { opacity: 0; transform: translateX(1rem) scale(0.98); } }
    .ob-toast-icon { flex-shrink: 0; width: 1.0625rem; height: 1.0625rem; margin-top: 0.0625rem; }
    .ob-toast-success .ob-toast-icon { color: #059669; }
    .ob-toast-error   .ob-toast-icon { color: #e11d48; }
    .ob-toast-warning .ob-toast-icon { color: #d97706; }
    .ob-toast-info    .ob-toast-icon { color: #4f46e5; }
    .dark .ob-toast-success .ob-toast-icon { color: #34d399; }
    .dark .ob-toast-error   .ob-toast-icon { color: #fb7185; }
    .dark .ob-toast-warning .ob-toast-icon { color: #fbbf24; }
    .dark .ob-toast-info    .ob-toast-icon { color: #818cf8; }
    .ob-toast-body { flex: 1; min-width: 0; }
    .ob-toast-msg { line-height: 1.45; }
    .ob-toast-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.375rem; }
    .ob-toast-action {
        font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;
        color: #4f46e5; background: none; border: none; padding: 0; cursor: pointer;
    }
    .dark .ob-toast-action { color: #a5b4fc; }
    .ob-toast-action:hover { text-decoration: underline; }
    .ob-toast-close {
        flex-shrink: 0; display: flex; padding: 0.125rem; cursor: pointer;
        color: var(--text-muted); background: none; border: none; border-radius: 0.375rem;
    }
    .ob-toast-close:hover { color: var(--text-primary); background-color: var(--surface-raised); }
    .ob-toast-progress {
        position: absolute; left: 0; bottom: 0; height: 2px; width: 100%;
        background: currentColor; opacity: 0.22;
        transition-property: width; transition-timing-function: linear;
    }
    .ob-toast-success { color: #059669; }
    .ob-toast-error { color: #e11d48; }
    .ob-toast-warning { color: #d97706; }
    .ob-toast-info { color: #4f46e5; }
    .ob-toast .ob-toast-msg, .ob-toast .ob-toast-close { color: var(--text-primary); }
    .ob-toast .ob-toast-close { color: var(--text-muted); }
    @media (max-width: 640px) { #obToasts { top: 0.75rem; right: 0.75rem; left: 0.75rem; width: auto; } }

    /* ── Sortable table headers ── */
    th.ob-sortable { cursor: pointer; user-select: none; white-space: nowrap; }
    th.ob-sortable:hover { color: var(--text-primary); }
    th.ob-sortable:focus-visible { outline: 2px solid #6366f1; outline-offset: -2px; border-radius: 0.375rem; }
    .ob-sort-inner { display: inline-flex; align-items: center; gap: 0.3125rem; }
    .ob-sort-caret { display: inline-flex; opacity: 0.3; transition: opacity 0.15s ease, transform 0.15s ease; }
    th.ob-sortable:hover .ob-sort-caret { opacity: 0.6; }
    th[aria-sort] .ob-sort-caret { opacity: 1; color: #4f46e5; }
    th[aria-sort="ascending"] .ob-sort-caret { transform: rotate(180deg); }

    /* ── Command palette ── */
    #obCommandPalette::backdrop { background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px); }
    #obCommandPalette {
        position: fixed; top: 12vh; left: 50%; transform: translateX(-50%); margin: 0;
        width: min(36rem, calc(100vw - 2rem)); max-height: min(30rem, 76vh);
        padding: 0; overflow: hidden; border-radius: 1rem;
        background-color: var(--surface); border: 1px solid var(--border-color);
        box-shadow: 0 24px 60px rgb(0 0 0 / 0.28);
    }
    #obCommandPalette[open] { animation: ob-cmd-in 0.16s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes ob-cmd-in { from { opacity: 0; transform: translateX(-50%) translateY(-8px) scale(0.985); } to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); } }
    .ob-cmd-input {
        width: 100%; border: none; outline: none; background: transparent;
        padding: 0.9375rem 1rem; font-size: 0.9375rem; color: var(--text-primary);
    }
    .ob-cmd-input::placeholder { color: var(--text-muted); }
    .ob-cmd-esc {
        flex-shrink: 0; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.625rem;
        padding: 0.125rem 0.375rem; border-radius: 0.3125rem; color: var(--text-muted);
        border: 1px solid var(--border-color); background-color: var(--surface-raised);
    }
    .ob-cmd-list { overflow-y: auto; max-height: calc(min(30rem, 76vh) - 7.5rem); padding: 0.375rem; }
    .ob-cmd-group {
        padding: 0.5rem 0.625rem 0.25rem; font-size: 0.625rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted);
    }
    .ob-cmd-item {
        display: flex; align-items: center; gap: 0.625rem; width: 100%;
        padding: 0.5rem 0.625rem; border-radius: 0.625rem; cursor: pointer;
        font-size: 0.8125rem; font-weight: 500; text-align: left;
        color: var(--text-primary); background: none; border: none;
    }
    .ob-cmd-item .ob-cmd-item-icon { flex-shrink: 0; width: 1rem; height: 1rem; color: var(--text-muted); }
    .ob-cmd-item .ob-cmd-item-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ob-cmd-item .ob-cmd-item-hint { flex-shrink: 0; font-size: 0.625rem; color: var(--text-muted); }
    .ob-cmd-item mark { background: rgba(99, 102, 241, 0.22); color: inherit; border-radius: 3px; padding: 0 1px; font-weight: 700; }
    .ob-cmd-item.active { background-color: var(--sidebar-active-bg); color: var(--sidebar-active-text); }
    .ob-cmd-item.active .ob-cmd-item-icon, .ob-cmd-item.active .ob-cmd-item-hint { color: var(--sidebar-active-text); }
    .ob-cmd-empty { padding: 1.75rem 1rem; text-align: center; font-size: 0.8125rem; color: var(--text-muted); }
    .ob-cmd-foot {
        display: flex; align-items: center; gap: 0.875rem; padding: 0.5rem 0.875rem;
        border-top: 1px solid var(--border-color); font-size: 0.6875rem; color: var(--text-muted);
        background-color: var(--surface-raised);
    }
    .ob-cmd-foot kbd {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.625rem;
        padding: 0.0625rem 0.3125rem; border-radius: 0.25rem;
        border: 1px solid var(--border-color); background-color: var(--surface);
    }

    /* ── Session timeout warning ── */
    #obSessionDialog::backdrop { background: rgba(15, 23, 42, 0.6); }
    #obSessionDialog {
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;
    }
    #obSessionDialog[open] { animation: fadeIn 0.15s ease; }

    /* ── Badges ── */
    .badge {
        display: inline-flex;
        align-items: center;
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.125rem 0.625rem;
        border-radius: 9999px;
    }

    /* ── Modal ── */
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-content,
    [id$="Modal"] > div,
    [id$="modal"] > div,
    [id*="Modal"] > div,
    [id*="modal"] > div {
        background-color: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        box-shadow: var(--shadow-lg);
        color: var(--text-primary);
    }

    /* ── MOBILE-RESPONSIVE TABLES ── */
    @media (max-width: 768px) {
        .responsive-table thead { display: none; }
        .responsive-table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1rem;
            background: var(--surface);
        }
        .responsive-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border: none;
            border-bottom: 1px solid var(--border-subtle);
        }
        .responsive-table tbody td:last-child { border-bottom: none; justify-content: flex-end; }
        .responsive-table tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .responsive-table tbody td .flex.items-center.justify-end { flex-wrap: wrap; }
        .page-actions, .flex.items-center.gap-3 { flex-wrap: wrap; gap: 0.5rem; }

        aside {
            position: fixed !important;
            left: 0; top: 0; bottom: 0;
            z-index: 40;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            width: 16rem;
        }
        aside.sidebar-open { transform: translateX(0); }
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 35; }
        .sidebar-backdrop.active { display: block; }
        .mobile-menu-btn { display: flex !important; }
    }
    @media (min-width: 769px) {
        .mobile-menu-btn { display: none !important; }
        .sidebar-backdrop { display: none !important; }
    }

    /* ── Sticky table headers (desktop) ── */
    @media (min-width: 769px) {
        .overflow-x-auto:has(> table#dataTable) { max-height: 70vh; overflow-y: auto; }
        table#dataTable thead th {
            position: sticky; top: 0; z-index: 5;
            background-color: #f8fafc;
        }
        .dark table#dataTable thead th { background-color: #1e293b; }
    }

    /* ── Mobile sort control (card layout hides <thead>) ── */
    .ob-mobile-sort { display: none; }
    @media (max-width: 768px) {
        .ob-mobile-sort {
            display: flex; align-items: center; gap: .5rem;
            padding: .5rem .75rem; border-bottom: 1px solid var(--border-color);
            font-size: .75rem; color: var(--text-muted);
        }
        .ob-mobile-sort label { white-space: nowrap; font-weight: 600; }
        .ob-mobile-sort select {
            flex: 1; min-width: 0; background: var(--surface); color: var(--text-primary);
            border: 1px solid var(--border-color); border-radius: .6rem; padding: .4rem .6rem; font-size: .75rem;
        }
        .ob-mobile-sort button {
            display: inline-flex; align-items: center; gap: .25rem; white-space: nowrap;
            border: 1px solid var(--border-color); border-radius: .6rem; padding: .4rem .6rem;
            background: var(--surface); color: var(--text-primary); cursor: pointer;
        }
    }

    /* ── Bulk actions toolbar ── */
    .ob-bulk-bar {
        display: none; align-items: center; gap: .75rem; flex-wrap: wrap;
        padding: .6rem .9rem; border-bottom: 1px solid var(--border-color);
        background: var(--surface-raised, #eef2ff); font-size: .75rem;
    }
    .ob-bulk-bar.active { display: flex; }
    .ob-bulk-bar .ob-bulk-count { font-weight: 700; color: var(--text-primary); }
    .ob-bulk-actions { margin-left: auto; display: flex; gap: .5rem; flex-wrap: wrap; }
    table#dataTable th.ob-check-col, table#dataTable td.ob-check-col { width: 2.25rem; padding-right: 0; }

    /* ── Skeleton loading ── */
    .ob-skeleton {
        position: relative; overflow: hidden;
        background: var(--border-subtle, #e2e8f0); border-radius: .375rem;
    }
    .ob-skeleton::after {
        content: ""; position: absolute; inset: 0; transform: translateX(-100%);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.55), transparent);
        animation: ob-shimmer 1.3s infinite;
    }
    .dark .ob-skeleton::after { background: linear-gradient(90deg, transparent, rgba(255,255,255,.08), transparent); }
    @keyframes ob-shimmer { 100% { transform: translateX(100%); } }
    @media (prefers-reduced-motion: reduce) { .ob-skeleton::after { animation: none; } }

    /* ── Modal loading overlay ── */
    .ob-modal-loading {
        position: absolute; inset: 0; z-index: 10;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,.82); border-radius: inherit;
    }
    .dark .ob-modal-loading { background: rgba(15,23,42,.82); }
    .ob-modal-loading .ob-spinner {
        width: 1.75rem; height: 1.75rem; border-radius: 50%;
        border: 3px solid var(--border-color); border-top-color: #6366f1;
        animation: ob-spin .6s linear infinite;
    }
    @keyframes ob-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .ob-modal-loading .ob-spinner { animation-duration: 1.8s; } }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--text-disabled); border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

    /* ── Combobox (searchable select) ── */
    .combobox-chevron {
        position: absolute; right: 0.7rem; top: 50%; transform: translateY(-50%);
        pointer-events: none; color: var(--text-muted); display: flex;
    }
    .combobox-panel { scrollbar-width: thin; }
    .combobox-panel::-webkit-scrollbar { width: 5px; }
    .combobox-option { transition: background-color 0.1s ease, color 0.1s ease; }
    .combobox-option.active,
    .combobox-option:hover {
        background: var(--sidebar-active-bg);
        color: var(--sidebar-active-text);
    }
    .combobox-option.selected { font-weight: 600; }
    .combobox-option mark {
        background: var(--sidebar-active-bg);
        color: var(--sidebar-active-text);
        border-radius: 3px; padding: 0 1px; font-weight: 600;
    }
    .combobox-option.active mark,
    .combobox-option:hover mark { background: transparent; }

    /* ── Print ── */
    @media print {
        aside, header, .no-print, #obToasts, #backToTop { display: none !important; }
        main { margin-left: 0 !important; }
        body { background: white !important; color: black !important; }
    }
</style>
