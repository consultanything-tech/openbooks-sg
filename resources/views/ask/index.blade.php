@extends('layouts.app')

@section('title', 'Ask OpenBooks')

@section('content')
<div class="space-y-6" id="askPage" data-auto="{{ request('q') }}" data-keys='@js($keys)'>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <i data-lucide="sparkles" class="w-4 h-4 text-indigo-500"></i> Ask OpenBooks
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ask in plain language — every figure is computed live from your own books</p>
        </div>
    </div>

    <!-- Ask box -->
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
        <form id="askForm" class="flex gap-2" autocomplete="off">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input id="askInput" type="text" maxlength="300"
                    placeholder="e.g. Who owes me money? How much GST this quarter? Am I over budget?"
                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl pl-10 pr-3.5 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500">
            </div>
            <button type="submit" id="askBtn"
                class="btn btn-primary">
                <i data-lucide="send" aria-hidden="true"></i> Ask
            </button>
        </form>
        @if($recent->isNotEmpty())
        <div class="mt-3 flex items-center gap-2 flex-wrap">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Recent</span>
            @foreach($recent as $r)
                <button type="button" data-key="{{ $r->intent_key }}" data-label="{{ $r->question }}"
                    class="ask-chip text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/60 rounded-full px-2.5 py-1 hover:text-indigo-600">{{ $r->question }}</button>
            @endforeach
        </div>
        @endif
        <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500">
            @if($aiConfigured)
                Natural-language mode active — ask in your own words.
            @else
                Keyword-matching mode — add a free NVIDIA API key in Settings → AI Assistant for full natural-language understanding.
            @endif
        </p>
    </div>

    <!-- Answer -->
    <div id="askAnswer" class="hidden bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <span id="ansCategory" class="inline-block text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 rounded-full px-2.5 py-1"></span>
                <p id="ansQuestion" class="text-xs text-slate-500 dark:text-slate-400 mt-2"></p>
            </div>
            <span id="ansCompare" class="hidden shrink-0 inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1"></span>
        </div>
        <h2 id="ansHeadline" class="text-2xl font-bold text-slate-900 dark:text-white"></h2>
        <div id="ansChartWrap" class="hidden relative h-64">
            <canvas id="askChart"></canvas>
        </div>
        <div id="ansTableWrap" class="hidden overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60"><tr id="ansHead"></tr></thead>
                <tbody id="ansBody" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
            </table>
        </div>
        <ul id="ansCaveats" class="hidden space-y-1"></ul>
        <p id="ansText" class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed"></p>
        <div id="ansLinks" class="hidden flex gap-2 flex-wrap"></div>
        <div id="ansActions" class="hidden flex gap-2 flex-wrap pt-1">
            <button type="button" id="btnDrill"
                class="hidden inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-700 hover:border-indigo-400 hover:text-indigo-600 rounded-full px-3 py-1.5 transition">
                <i data-lucide="microscope" class="w-3.5 h-3.5"></i> Why? Show me the documents
            </button>
            <span id="ansFollowups" class="contents"></span>
        </div>
        <details class="text-xs text-slate-500 dark:text-slate-400">
            <summary class="cursor-pointer font-semibold">How this was calculated</summary>
            <p id="ansExplain" class="mt-1.5"></p>
        </details>
    </div>

    <!-- Loading / error / out-of-scope -->
    <div id="askLoading" class="hidden py-12 text-center">
        <i data-lucide="loader-2" class="w-5 h-5 text-indigo-500 mb-2 animate-spin inline-block"></i>
        <p class="text-xs text-slate-500 dark:text-slate-400">Crunching your numbers...</p>
    </div>
    <div id="askScope" class="hidden bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
        <p id="askScopeMsg" class="text-sm text-slate-700 dark:text-slate-300"></p>
        <div class="mt-3 flex flex-wrap gap-2" id="askScopeSuggestions"></div>
    </div>

    <!-- Predefined questions -->
    <div class="space-y-5">
        @foreach($groups as $category => $questions)
            <div>
                <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">{{ $category }}</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($questions as $q)
                        <button type="button" data-key="{{ $q['key'] }}" data-label="{{ $q['label'] }}"
                            class="ask-chip text-xs text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-full px-3.5 py-2 transition text-left">
                            {{ $q['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    var askChart = null;
    var lastIntent = null;
    var $ = function(id) { return document.getElementById(id); };
    function esc(v) {
        return String(v).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function show(id, on) { $(id).classList.toggle('hidden', !on); }

    function submit(question, key) {
        show('askAnswer', false); show('askScope', false); show('askLoading', true);
        fetch('{{ route("ask.run") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ question: question || null, key: key || null, context: lastIntent })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            show('askLoading', false);
            if (res.status === 'ok') { render(res.payload); }
            else if (res.status === 'out_of_scope') { renderScope(res); }
            else { renderScope({ message: res.message || 'Something went wrong. Please try again.', suggestions: [] }); }
        })
        .catch(function() {
            show('askLoading', false);
            renderScope({ message: 'Network error — could not reach OpenBooks. Please try again.', suggestions: [] });
        });
    }

    function render(p) {
        $('ansCategory').textContent = p.category || '';
        $('ansQuestion').textContent = '“' + (p.question || p.label) + '”';
        $('ansHeadline').textContent = p.headline || '';
        $('ansText').textContent = p.answer || '';
        $('ansExplain').textContent = p.explain || '';

        var cmp = $('ansCompare');
        if (p.compare && p.compare.delta_pct !== null && p.compare.delta_pct !== undefined) {
            var up = p.compare.delta_pct >= 0;
            cmp.textContent = (up ? '▲ ' : '▼ ') + Math.abs(p.compare.delta_pct) + '% ' + (p.compare.text || '');
            cmp.className = 'shrink-0 inline-flex items-center gap-1 text-xs font-semibold rounded-full px-2.5 py-1 '
                + (up ? 'text-emerald-700 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-500/10'
                      : 'text-rose-700 bg-rose-50 dark:text-rose-400 dark:bg-rose-500/10');
            show('ansCompare', true);
        } else { show('ansCompare', false); }

        // Chart
        if (askChart) { askChart.destroy(); askChart = null; }
        if (p.chart && p.chart.labels && p.chart.labels.length) {
            show('ansChartWrap', true);
            var isDark = document.documentElement.classList.contains('dark');
            var tick = isDark ? '#94a3b8' : '#64748b';
            askChart = new Chart($('askChart').getContext('2d'), {
                type: p.chart.type === 'line' ? 'line' : 'bar',
                data: {
                    labels: p.chart.labels,
                    datasets: [{
                        label: 'Value',
                        data: p.chart.values,
                        backgroundColor: p.chart.type === 'line' ? 'rgba(37, 99, 235, 0.15)' : 'rgba(37, 99, 235, 0.7)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        fill: p.chart.type === 'line',
                        tension: 0.35,
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: tick, font: { size: 10 }, maxRotation: 45 }, grid: { display: false } },
                        y: { ticks: { color: tick, font: { size: 10 } }, grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' } }
                    }
                }
            });
        } else { show('ansChartWrap', false); }

        // Breakdown table
        if (p.breakdown && p.breakdown.rows && p.breakdown.rows.length) {
            $('ansHead').innerHTML = p.breakdown.columns.map(function(c) {
                return '<th class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 px-4 py-2.5">' + esc(c) + '</th>';
            }).join('');
            $('ansBody').innerHTML = p.breakdown.rows.map(function(row) {
                return '<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">' + row.map(function(cell) {
                    return '<td class="px-4 py-2 text-xs text-slate-700 dark:text-slate-300">' + (cell === null || cell === undefined ? '--' : esc(cell)) + '</td>';
                }).join('') + '</tr>';
            }).join('');
            show('ansTableWrap', true);
        } else { show('ansTableWrap', false); }

        // Caveats
        if (p.caveats && p.caveats.length) {
            $('ansCaveats').innerHTML = p.caveats.map(function(c) {
                return '<li class="flex items-start gap-1.5 text-xs text-amber-700 dark:text-amber-400">'
                    + '<i data-lucide="alert-triangle" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i>' + esc(c) + '</li>';
            }).join('');
            show('ansCaveats', true);
        } else { show('ansCaveats', false); }

        // Links
        if (p.links && p.links.length) {
            $('ansLinks').innerHTML = p.links.map(function(l) {
                return '<a href="' + esc(l.url) + '" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">'
                    + '<i data-lucide="external-link" class="w-3.5 h-3.5"></i>' + esc(l.label) + '</a>';
            }).join('');
            show('ansLinks', true);
        } else { show('ansLinks', false); }

        // Drill-down + follow-up actions
        var actions = false;
        var bd = $('btnDrill');
        if (p.drill_available && !p.drill) {
            bd.classList.remove('hidden'); bd.classList.add('inline-flex');
            actions = true;
        } else {
            bd.classList.add('hidden'); bd.classList.remove('inline-flex');
        }
        var fus = (p.followups || []).map(function(f) {
            return '<button type="button" data-key="' + esc(f.key) + '" data-label="' + esc(f.label) + '" class="ask-chip text-xs text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-700 hover:border-indigo-400 hover:text-indigo-600 rounded-full px-3 py-1.5 transition">Related: ' + esc(f.label) + '</button>';
        });
        $('ansFollowups').innerHTML = fus.join('');
        if (fus.length) actions = true;
        show('ansActions', actions);
        lastIntent = p.intent || null;

        show('askAnswer', true);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        $('askAnswer').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function renderScope(res) {
        $('askScopeMsg').textContent = res.message || '';
        var box = $('askScopeSuggestions');
        box.innerHTML = (res.suggestions || []).map(function(s) {
            return '<button type="button" data-key="' + esc(s.key) + '" data-label="' + esc(s.label) + '" class="ask-chip text-xs text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30 rounded-full px-3 py-1.5 hover:bg-indigo-50 dark:hover:bg-indigo-500/10">' + esc(s.label) + '</button>';
        }).join('');
        show('askScope', true);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    document.addEventListener('click', function(e) {
        var chip = e.target.closest('.ask-chip');
        if (chip) { submit(chip.dataset.label, chip.dataset.key); }
    });

    $('askForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var q = $('askInput').value.trim();
        if (!q) return;
        submit(q, null);
    });

    $('btnDrill').addEventListener('click', function() {
        if (!lastIntent) return;
        show('askAnswer', false); show('askScope', false); show('askLoading', true);
        fetch('{{ route("ask.drill") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ key: lastIntent })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            show('askLoading', false);
            if (res.status === 'ok') render(res.payload);
            else renderScope({ message: res.message || 'Could not load details.', suggestions: [] });
        })
        .catch(function() {
            show('askLoading', false);
            renderScope({ message: 'Network error — could not load details.', suggestions: [] });
        });
    });

    // Auto-run ?q= deep links from the AI widget
    var page = $('askPage');
    var auto = page ? (page.dataset.auto || '') : '';
    if (auto) {
        var keys = [];
        try { keys = JSON.parse(page.dataset.keys || '[]'); } catch (e) {}
        if (keys.indexOf(auto) !== -1) submit('', auto);
        else submit(auto, null);
    }
})();
</script>
@endsection
