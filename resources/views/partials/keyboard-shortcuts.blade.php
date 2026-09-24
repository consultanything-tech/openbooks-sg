<!-- Keyboard Shortcuts -->
<div id="shortcutOverlay" class="fixed inset-0 z-[70] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="keyboard" class="w-4 h-4 text-indigo-500"></i> Keyboard Shortcuts
            </h3>
            <button onclick="document.getElementById('shortcutOverlay').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider col-span-2 pt-2 pb-1">Navigation</div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Go to Dashboard</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">G D</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Go to Invoices</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">G I</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Go to Bills</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">G B</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Go to Customers</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">G C</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Go to Quotes</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">G Q</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Go to Reports</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">G R</kbd></div>

            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider col-span-2 pt-3 pb-1">Actions</div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">New Invoice</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">N I</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">New Bill</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">N B</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">New Quote</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">N Q</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Toggle Dark Mode</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">T T</kbd></div>

            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider col-span-2 pt-3 pb-1">Global</div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Command Palette / Search</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">&#8984;/Ctrl K</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Show Shortcuts</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">?</kbd></div>
            <div class="flex items-center justify-between"><span class="text-slate-600 dark:text-slate-300">Close / Escape</span><kbd class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-mono border border-slate-200 dark:border-slate-700">Esc</kbd></div>
        </div>
    </div>
</div>

<script>
// Keyboard shortcuts system
(function() {
    let pendingKey = null;
    let pendingTimeout = null;

    function isTyping() {
        const el = document.activeElement;
        return el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.isContentEditable);
    }

    document.addEventListener('keydown', function(e) {
        if (isTyping()) return;

        const key = e.key.toLowerCase();

        // Escape closes modals/overlays
        if (key === 'escape') {
            document.getElementById('shortcutOverlay')?.classList.add('hidden');
            // Close any visible modal
            document.querySelectorAll('[id$="Modal"],[id$="modal"]').forEach(m => m.classList.add('hidden'));
            return;
        }

        // ? shows shortcuts
        if (e.key === '?' || (e.shiftKey && key === '/')) {
            e.preventDefault();
            document.getElementById('shortcutOverlay')?.classList.toggle('hidden');
            return;
        }

        // Two-key combos: G+X for navigation, N+X for creation, T+T for theme
        if (pendingKey) {
            clearTimeout(pendingTimeout);
            const combo = pendingKey + key;
            pendingKey = null;

            const routes = {
                'gd': '{{ route("dashboard") }}',
                'gi': '{{ route("invoices.index") }}',
                'gb': '{{ route("bills.index") }}',
                'gc': '{{ route("customers.index") }}',
                'gq': '{{ route("quotes.index") }}',
                'gr': '{{ route("reports.profit_loss") }}',
                'ni': '{{ route("invoices.create") }}',
                'nb': '{{ route("bills.create") }}',
                'nq': '{{ route("quotes.create") }}',
            };

            if (combo === 'tt') {
                e.preventDefault();
                toggleTheme();
                return;
            }

            if (routes[combo]) {
                e.preventDefault();
                window.location.href = routes[combo];
                return;
            }
        }

        if (key === 'g' || key === 'n' || key === 't') {
            pendingKey = key;
            pendingTimeout = setTimeout(() => { pendingKey = null; }, 800);
            return;
        }
    });
})();
</script>
