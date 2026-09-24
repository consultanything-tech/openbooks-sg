<!-- Mobile sidebar backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

<!-- Sidebar Navigation -->
<aside id="sidebar" role="complementary" aria-label="Sidebar" class="w-60 flex flex-col justify-between shrink-0 select-none z-30">
    <div class="flex flex-col min-h-0 overflow-y-auto">
        <!-- App Brand -->
        <div class="h-14 flex items-center gap-3 px-5 border-b border-slate-200 dark:border-slate-800">
            @if(!empty($company->logo_path))
                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="h-8 w-auto">
            @else
                <img src="{{ asset('logo/mark.svg') }}" alt="OpenBooks SG" class="h-8 w-8 rounded-lg">
            @endif
            <div>
                <h1 class="text-sm font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-1.5">
                    OpenBooks <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400">SG</span>
                </h1>
                <p class="text-[10px] tracking-wider uppercase font-medium text-slate-400 dark:text-slate-500">Open Source Accounting</p>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav aria-label="Main navigation" class="p-3 flex flex-col gap-0.5">
            <a href="{{ route('dashboard') }}" {{ request()->routeIs('dashboard') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard" class="w-[18px] h-[18px]"></i>
                <span>Dashboard</span>
            </a>

            {{-- Sales & Revenue --}}
            <div class="nav-group" data-group="sales">
                <button type="button" class="nav-section-toggle" aria-expanded="true" aria-controls="nav-group-sales">
                    <span>Sales &amp; Revenue</span>
                    <i data-lucide="chevron-down" class="nav-section-chevron w-3.5 h-3.5"></i>
                </button>
                <div class="nav-group-items" id="nav-group-sales">
                    <div class="flex flex-col gap-0.5">
                        <a href="{{ route('invoices.index') }}" {{ request()->routeIs('invoices.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                            <i data-lucide="file-text" class="w-[18px] h-[18px]"></i>
                            <span>Invoices</span>
                        </a>
                        <a href="{{ route('customers.index') }}" {{ request()->routeIs('customers.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                            <i data-lucide="users" class="w-[18px] h-[18px]"></i>
                            <span>Customers</span>
                        </a>
                        <a href="{{ route('quotes.index') }}" {{ request()->routeIs('quotes.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('quotes.*') ? 'active' : '' }}">
                            <i data-lucide="clipboard-list" class="w-[18px] h-[18px]"></i>
                            <span>Quotations</span>
                        </a>
                        <a href="{{ route('credit_notes.index') }}" {{ request()->routeIs('credit_notes.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('credit_notes.*') ? 'active' : '' }}">
                            <i data-lucide="file-minus" class="w-[18px] h-[18px]"></i>
                            <span>Credit Notes</span>
                        </a>
                        <a href="{{ route('recurring.index') }}" {{ request()->routeIs('recurring.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('recurring.*') ? 'active' : '' }}">
                            <i data-lucide="refresh-cw" class="w-[18px] h-[18px]"></i>
                            <span>Recurring</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Purchases & Expenses --}}
            <div class="nav-group" data-group="purchases">
                <button type="button" class="nav-section-toggle" aria-expanded="true" aria-controls="nav-group-purchases">
                    <span>Purchases &amp; Expenses</span>
                    <i data-lucide="chevron-down" class="nav-section-chevron w-3.5 h-3.5"></i>
                </button>
                <div class="nav-group-items" id="nav-group-purchases">
                    <div class="flex flex-col gap-0.5">
                        <a href="{{ route('bills.index') }}" {{ request()->routeIs('bills.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('bills.*') ? 'active' : '' }}">
                            <i data-lucide="receipt" class="w-[18px] h-[18px]"></i>
                            <span>Bills &amp; Expenses</span>
                        </a>
                        <a href="{{ route('vendors.index') }}" {{ request()->routeIs('vendors.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('vendors.*') ? 'active' : '' }}">
                            <i data-lucide="truck" class="w-[18px] h-[18px]"></i>
                            <span>Vendors</span>
                        </a>
                        <a href="{{ route('expense_claims.index') }}" {{ request()->routeIs('expense_claims.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('expense_claims.*') ? 'active' : '' }}">
                            <i data-lucide="banknote" class="w-[18px] h-[18px]"></i>
                            <span>Expense Claims</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Finance & Catalog --}}
            <div class="nav-group" data-group="finance">
                <button type="button" class="nav-section-toggle" aria-expanded="true" aria-controls="nav-group-finance">
                    <span>Finance &amp; Catalog</span>
                    <i data-lucide="chevron-down" class="nav-section-chevron w-3.5 h-3.5"></i>
                </button>
                <div class="nav-group-items" id="nav-group-finance">
                    <div class="flex flex-col gap-0.5">
                        <a href="{{ route('banking.index') }}" {{ request()->routeIs('banking.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('banking.*') ? 'active' : '' }}">
                            <i data-lucide="landmark" class="w-[18px] h-[18px]"></i>
                            <span>Banking &amp; Cash</span>
                        </a>
                        <a href="{{ route('items.index') }}" {{ request()->routeIs('items.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}">
                            <i data-lucide="package" class="w-[18px] h-[18px]"></i>
                            <span>Products &amp; Services</span>
                        </a>
                        <a href="{{ route('inventory.index') }}" {{ request()->routeIs('inventory.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                            <i data-lucide="warehouse" class="w-[18px] h-[18px]"></i>
                            <span>Inventory</span>
                        </a>
                        <a href="{{ route('accounts.index') }}" {{ request()->routeIs('accounts.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                            <i data-lucide="book-open" class="w-[18px] h-[18px]"></i>
                            <span>Chart of Accounts</span>
                        </a>
                        <a href="{{ route('time_tracking.index') }}" {{ request()->routeIs('time_tracking.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('time_tracking.*') ? 'active' : '' }}">
                            <i data-lucide="timer" class="w-[18px] h-[18px]"></i>
                            <span>Time Tracking</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Intelligence --}}
            <div class="nav-group" data-group="intelligence">
                <button type="button" class="nav-section-toggle" aria-expanded="true" aria-controls="nav-group-intelligence">
                    <span>Intelligence</span>
                    <i data-lucide="chevron-down" class="nav-section-chevron w-3.5 h-3.5"></i>
                </button>
                <div class="nav-group-items" id="nav-group-intelligence">
                    <div class="flex flex-col gap-0.5">
                        <a href="{{ route('reports.profit_loss') }}" {{ request()->routeIs('reports.*') && !request()->routeIs('reports.custom*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('reports.*') && !request()->routeIs('reports.custom*') ? 'active' : '' }}">
                            <i data-lucide="trending-up" class="w-[18px] h-[18px]"></i>
                            <span>Financial Reports</span>
                        </a>
                        <a href="{{ route('ask.index') }}" {{ request()->routeIs('ask.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('ask.*') ? 'active' : '' }}">
                            <i data-lucide="sparkles" class="w-[18px] h-[18px]"></i>
                            <span>Ask OpenBooks</span>
                        </a>
                        <a href="{{ route('reports.custom') }}" {{ request()->routeIs('reports.custom*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('reports.custom*') ? 'active' : '' }}">
                            <i data-lucide="wand-2" class="w-[18px] h-[18px]"></i>
                            <span>Report Builder</span>
                        </a>
                        <a href="{{ route('budgets.index') }}" {{ request()->routeIs('budgets.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
                            <i data-lucide="target" class="w-[18px] h-[18px]"></i>
                            <span>Budgets</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Administration (admins only) --}}
            @if(Auth::user() && strtoupper(Auth::user()->role) === 'ADMIN')
            <div class="nav-group" data-group="admin">
                <button type="button" class="nav-section-toggle" aria-expanded="true" aria-controls="nav-group-admin">
                    <span>Administration</span>
                    <i data-lucide="chevron-down" class="nav-section-chevron w-3.5 h-3.5"></i>
                </button>
                <div class="nav-group-items" id="nav-group-admin">
                    <div class="flex flex-col gap-0.5">
                        <a href="{{ route('settings.index') }}" {{ request()->routeIs('settings.*') && !request()->routeIs('settings.users') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('settings.*') && !request()->routeIs('settings.users') ? 'active' : '' }}">
                            <i data-lucide="settings" class="w-[18px] h-[18px]"></i>
                            <span>Settings</span>
                        </a>
                        <a href="{{ route('settings.users') }}" {{ request()->routeIs('settings.users*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('settings.users*') ? 'active' : '' }}">
                            <i data-lucide="shield" class="w-[18px] h-[18px]"></i>
                            <span>User Management</span>
                        </a>
                        <a href="{{ route('activity_log.index') }}" {{ request()->routeIs('activity_log.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('activity_log.*') ? 'active' : '' }}">
                            <i data-lucide="history" class="w-[18px] h-[18px]"></i>
                            <span>Activity Log</span>
                        </a>
                        <a href="{{ route('updates.index') }}" {{ request()->routeIs('updates.*') ? 'aria-current=page' : '' }} class="nav-link {{ request()->routeIs('updates.*') ? 'active' : '' }}">
                            <i data-lucide="cloud-download" class="w-[18px] h-[18px]"></i>
                            <span class="flex-1">System Updates</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </nav>
    </div>

    <!-- User Profile & System Status -->
    <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                    {{ strtoupper(substr(Auth::user()->name ?? 'Admin', 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate leading-tight text-slate-900 dark:text-white">{{ Auth::user()->name ?? 'Administrator' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ ucfirst(strtolower(Auth::user()->role ?? 'Admin')) }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" onsubmit="try{localStorage.removeItem('ob-return-to');}catch(e){}">
                @csrf
                <button type="submit" title="Logout" aria-label="Logout" class="text-slate-400 hover:text-red-500 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <i data-lucide="log-out" class="w-4 h-4" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
