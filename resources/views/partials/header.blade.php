<!-- Top Bar -->
<header class="h-14 px-6 flex items-center justify-between shrink-0" role="banner">
    <div class="flex items-center gap-3">
        <button type="button" onclick="toggleSidebar()" aria-label="Toggle sidebar navigation" title="Toggle sidebar navigation" class="mobile-menu-btn items-center justify-center w-8 h-8 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition" style="display:none;">
            <i data-lucide="menu" class="w-5 h-5" aria-hidden="true"></i>
        </button>
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">@yield('title', 'Overview')</h2>
        @yield('breadcrumbs')
    </div>

    <div class="flex items-center gap-2">
        <!-- Command palette trigger -->
        <button type="button" id="cmdPaletteTrigger" aria-label="Open command palette"
                title="Search pages and actions"
                class="flex items-center gap-2 h-8 pl-2.5 pr-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition text-xs font-medium">
            <i data-lucide="search" class="w-4 h-4" aria-hidden="true"></i>
            <span class="hidden md:inline">Search</span>
            <kbd id="cmdPaletteKbd" class="hidden md:inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono text-[10px] text-slate-500 dark:text-slate-400">&#8984;K</kbd>
        </button>

        <!-- Notification Bell -->
        <div class="relative" id="notificationWrapper">
            <button type="button" onclick="toggleNotifications()" aria-label="Notifications" title="Notifications" aria-expanded="false" aria-haspopup="true" class="relative flex items-center justify-center w-8 h-8 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition">
                <i data-lucide="bell" class="w-[18px] h-[18px]" aria-hidden="true"></i>
                <span id="notifBadge" class="hidden absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full items-center justify-center"></span>
            </button>
            <div id="notifDropdown" role="dialog" aria-label="Notifications panel" class="hidden absolute right-0 top-10 w-80 card shadow-lg z-50 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Notifications</span>
                    <button onclick="markAllRead()" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Mark all read</button>
                </div>
                <div id="notifList" class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800" role="list" aria-label="Notification list">
                    <div class="px-4 py-6 text-center text-sm text-slate-500">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Locale Switcher -->
        <div class="relative" id="localeWrapper">
            <button type="button" onclick="document.getElementById('localeDropdown').classList.toggle('hidden')" aria-label="{{ __('messages.language') }}" title="{{ __('messages.language') }}" aria-expanded="false" aria-haspopup="true" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition text-xs font-medium">
                <i data-lucide="globe" class="w-4 h-4" aria-hidden="true"></i>
                <span>{{ strtoupper(app()->getLocale()) }}</span>
            </button>
            <div id="localeDropdown" role="menu" aria-label="Language selection" class="hidden absolute right-0 top-10 w-36 card shadow-lg z-50 overflow-hidden">
                <a href="{{ route('locale.set', 'en') }}" role="menuitem" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 {{ app()->getLocale() === 'en' ? 'font-semibold text-indigo-600 dark:text-indigo-400' : '' }}">
                    EN &mdash; English
                </a>
                <a href="{{ route('locale.set', 'ms') }}" role="menuitem" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 {{ app()->getLocale() === 'ms' ? 'font-semibold text-indigo-600 dark:text-indigo-400' : '' }}">
                    BM &mdash; Bahasa
                </a>
            </div>
        </div>

        <!-- Theme Toggle -->
        <button type="button" onclick="toggleTheme()" id="themeToggleBtn" aria-label="Toggle light and dark mode" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 transition text-xs font-medium" title="Toggle Light / Dark Mode">
            <span id="themeIconSun" style="display:none"><i data-lucide="sun" class="w-4 h-4 text-amber-500" aria-hidden="true"></i></span>
            <span id="themeIconMoon"><i data-lucide="moon" class="w-4 h-4" aria-hidden="true"></i></span>
            <span id="themeLabel">Dark</span>
        </button>

        @canEdit
        <div class="h-5 w-px bg-slate-200 dark:bg-slate-700 mx-1 hidden sm:block"></div>
        <span class="hidden sm:inline-flex">
            <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus" aria-hidden="true"></i>
                New Invoice
            </a>
        </span>
        <span class="hidden lg:inline-flex">
            <a href="{{ route('quotes.create') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="clipboard-list" aria-hidden="true"></i>
                New Quote
            </a>
        </span>
        <span class="hidden lg:inline-flex">
            <a href="{{ route('bills.create') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="receipt" aria-hidden="true"></i>
                New Bill
            </a>
        </span>
        @endcanEdit
    </div>
</header>
