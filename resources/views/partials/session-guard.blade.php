{{-- Idle session guard: warns before the PHP session expires so work is never lost --}}
@php
    $obSessionLifetime = max(1, (int) config('session.lifetime', 120));
    $obSessionWarnMinutes = $obSessionLifetime > 3 ? 2 : 1;
@endphp

<dialog id="obSessionDialog" aria-labelledby="obSessionTitle"
    class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl p-0 w-[calc(100%-2rem)] max-w-sm">
    <div class="p-5">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-5 h-5 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
            </div>
            <div class="min-w-0">
                <h2 id="obSessionTitle" class="text-sm font-bold text-slate-900 dark:text-white">Your session is about to expire</h2>
                <p id="obSessionMessage" class="text-xs text-slate-500 dark:text-slate-400 mt-1"></p>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <a href="{{ route('logout') }}" id="obSessionSignOut"
               class="btn btn-ghost"
               onclick="event.preventDefault(); try{localStorage.removeItem('ob-return-to');}catch(e){} document.getElementById('obSessionLogoutForm').submit();">Sign out</a>
            <button type="button" id="obSessionStay" class="btn btn-primary">Stay signed in</button>
        </div>
    </div>
</dialog>
<form id="obSessionLogoutForm" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

<script>
(function () {
    var dlg = document.getElementById('obSessionDialog');
    var msg = document.getElementById('obSessionMessage');
    var stay = document.getElementById('obSessionStay');
    if (!dlg || !msg || !stay) return;

    var LIFETIME_MS = {{ $obSessionLifetime }} * 60000;
    var WARN_MS = {{ $obSessionWarnMinutes }} * 60000;
    var KEY = 'ob-last-activity';
    var CHECK_EVERY = 10000;
    var countdownTimer = null;
    var expired = false;

    function lastActivity() {
        var raw = null;
        try { raw = localStorage.getItem(KEY); } catch (e) { raw = null; }
        var ts = parseInt(raw, 10);
        return isNaN(ts) ? Date.now() : ts;
    }
    function touch() {
        try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
    }

    var throttled = false;
    function onTouch() {
        if (throttled) return;
        throttled = true;
        setTimeout(function () { throttled = false; }, 5000);
        touch();
    }
    ['keydown', 'mousedown', 'touchstart', 'scroll'].forEach(function (ev) {
        document.addEventListener(ev, onTouch, { passive: true });
    });
    touch();

    // Remember the current authenticated page so that, if the session lapses and
    // the user signs back in, they land here instead of the dashboard.
    try {
        var p = window.location.pathname + window.location.search;
        if (p && p.indexOf('/login') !== 0 && p.indexOf('/logout') !== 0 && p.indexOf('/2fa') !== 0) {
            localStorage.setItem('ob-return-to', p);
        }
    } catch (e) {}

    function stopCountdown() {
        if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
    }

    function keepAlive() {
        var token = document.querySelector('meta[name="csrf-token"]');
        return fetch('{{ route('session.heartbeat') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).catch(function () { return null; });
    }

    function showExpired() {
        expired = true;
        stopCountdown();
        msg.textContent = 'Your session has expired. Sign in again to continue where you left off.';
        dlg.querySelector('#obSessionTitle').textContent = 'Session expired';
        stay.textContent = 'Sign in again';
        stay.onclick = function () { window.location.href = '{{ route('login') }}'; };
        if (!dlg.open) dlg.showModal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function showWarning(remainingMs) {
        if (expired) return;
        function paint(ms) {
            var secs = Math.max(0, Math.ceil(ms / 1000));
            var m = Math.floor(secs / 60), s = secs % 60;
            msg.textContent = 'For your security you will be signed out in ' + m + ':' + (s < 10 ? '0' : '') + s + '. Any unsaved work may be lost.';
        }
        paint(remainingMs);
        if (!dlg.open) {
            dlg.showModal();
            stay.focus();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
        stopCountdown();
        var endsAt = Date.now() + remainingMs;
        countdownTimer = setInterval(function () {
            var left = endsAt - Date.now();
            if (left <= 0) { showExpired(); return; }
            paint(left);
        }, 1000);
    }

    stay.addEventListener('click', function () {
        if (expired) return; // handler replaced above in the expired state
        stopCountdown();
        touch();
        if (dlg.open) dlg.close();
        keepAlive().then(function () {
            if (typeof obToast === 'function') obToast('Session extended.', { type: 'success', duration: 2500 });
        });
    });
    // Escape / backdrop must not silently dismiss the warning without extending.
    dlg.addEventListener('cancel', function (e) { e.preventDefault(); });

    setInterval(function () {
        if (document.hidden) return;
        var idle = Date.now() - lastActivity();
        if (idle >= LIFETIME_MS) { showExpired(); return; }
        if (idle >= LIFETIME_MS - WARN_MS) { showWarning(LIFETIME_MS - idle); }
        else if (!expired && dlg.open && countdownTimer) { stopCountdown(); dlg.close(); }
    }, CHECK_EVERY);
})();
</script>
