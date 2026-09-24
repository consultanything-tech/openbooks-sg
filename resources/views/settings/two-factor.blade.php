@extends('layouts.app')

@section('title', 'Two-Factor Authentication')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <x-sticky-form-bar :cancel-url="route('settings.index')" save-label="Verify & Activate">
        <x-slot:title>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Two-Factor Authentication</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Add an extra layer of security to your account</p>
            </div>
        </x-slot:title>
    </x-sticky-form-bar>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-800 dark:text-emerald-400 text-xs font-medium flex items-center gap-2 animate-slide-down">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="px-4 py-3 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-800 dark:text-red-400 text-xs font-medium flex items-center gap-2 animate-slide-down">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 dark:text-red-400"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Recovery Codes Display --}}
    @if(session('recovery_codes'))
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-amber-200 dark:border-amber-500/30 p-6 space-y-4">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500"></i>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Recovery Codes</h2>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Store these recovery codes in a safe place. Each code can be used once to access your account if you lose your authenticator device.
            </p>
            <div id="recoveryCodesBox" class="p-4 rounded-xl bg-amber-50 dark:bg-amber-500/5 border border-amber-200 dark:border-amber-500/20 grid grid-cols-2 gap-2">
                @foreach(session('recovery_codes') as $code)
                    <code class="text-xs font-mono text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700">{{ $code }}</code>
                @endforeach
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="copyRecoveryCodes()" class="btn btn-primary">
                    <i data-lucide="copy" aria-hidden="true"></i>
                    Copy Codes
                </button>
                <button type="button" onclick="downloadRecoveryCodes()" class="btn btn-secondary">
                    <i data-lucide="download" aria-hidden="true"></i>
                    Download
                </button>
            </div>
        </div>
    @endif

    {{-- 2FA Status Card --}}
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">

        @if($user->two_factor_enabled)
            {{-- STATE: 2FA Enabled --}}
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">
                        <i data-lucide="shield" class="w-4 h-4 text-emerald-500 mr-1.5"></i>Authentication Status
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Two-factor authentication is currently active on your account</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 text-[11px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Enabled
                </span>
            </div>

            <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/5 border border-emerald-200 dark:border-emerald-500/20">
                <div class="flex items-start gap-3">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 mt-0.5"></i>
                    <div>
                        <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-300">Your account is protected</p>
                        <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">You will be prompted for a verification code from your authenticator app each time you sign in.</p>
                    </div>
                </div>
            </div>

            {{-- Disable 2FA Section --}}
            <div class="border-t border-slate-200 dark:border-slate-700 pt-5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-1">
                    <i data-lucide="unlock" class="w-4 h-4 text-red-400 mr-1.5"></i>Disable Two-Factor Authentication
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-4">Disabling 2FA will remove the additional security layer from your account. You will need to confirm your password.</p>
                <form action="{{ route('2fa.disable') }}" method="POST" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Current Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="disable-2fa-password" required placeholder="Enter your password"
                                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 pr-10 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <button type="button" onclick="var i=document.getElementById('disable-2fa-password'); var s=i.type==='password'; i.type=s?'text':'password'; this.querySelector('.eye-on').classList.toggle('hidden',s); this.querySelector('.eye-off').classList.toggle('hidden',!s); this.setAttribute('aria-pressed',String(s)); this.setAttribute('aria-label', s?'Hide password':'Show password');" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Show password" aria-pressed="false">
                                <i data-lucide="eye" class="eye-on w-4 h-4" aria-hidden="true"></i>
                                <i data-lucide="eye-off" class="eye-off w-4 h-4 hidden" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" onclick="return confirm('Are you sure you want to disable two-factor authentication?')"
                        class="btn btn-danger whitespace-nowrap">
                        <i data-lucide="shield-off" aria-hidden="true"></i>
                        Disable 2FA
                    </button>
                </form>
            </div>

        @else
            {{-- STATE: 2FA Disabled --}}
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">
                        <i data-lucide="shield" class="w-4 h-4 text-slate-400 mr-1.5"></i>Authentication Status
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Two-factor authentication is not set up on your account</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 text-[11px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    Disabled
                </span>
            </div>

            {{-- Explanation --}}
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 space-y-3">
                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">Why enable two-factor authentication?</p>
                <ul class="space-y-2 text-[11px] text-slate-600 dark:text-slate-400">
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-indigo-500 mt-0.5"></i>
                        Protects your account even if your password is compromised
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-indigo-500 mt-0.5"></i>
                        Requires a time-based code from your authenticator app (Google Authenticator, Authy, 1Password, etc.)
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="check" class="w-3 h-3 text-indigo-500 mt-0.5"></i>
                        Recovery codes are provided as a backup in case you lose access to your device
                    </li>
                </ul>
            </div>

            {{-- Enable Button --}}
            <div id="enableSection">
                <button type="button" id="enableTwoFactorBtn" onclick="enableTwoFactor()"
                    class="btn btn-primary w-full">
                    <i data-lucide="shield" aria-hidden="true"></i>
                    Enable Two-Factor Authentication
                </button>
            </div>

            {{-- Setup Panel (hidden by default, shown after AJAX) --}}
            <div id="setupPanel" class="hidden space-y-5 border-t border-slate-200 dark:border-slate-700 pt-5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white">
                    <i data-lucide="qr-code" class="w-4 h-4 text-indigo-500 mr-1.5"></i>Scan QR Code
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Open your authenticator app and scan the QR code below, or enter the secret key manually.</p>

                <div class="flex flex-col sm:flex-row items-center gap-5">
                    {{-- QR Code --}}
                    <div class="p-3 bg-white rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div id="qrCodeContainer" class="w-48 h-48 flex items-center justify-center"></div>
                    </div>

                    {{-- Manual Key --}}
                    <div class="flex-1 space-y-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Secret Key</label>
                            <div class="flex items-center gap-2">
                                <code id="secretKeyDisplay" class="flex-1 text-xs font-mono bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-800 dark:text-slate-200 break-all select-all"></code>
                                <button type="button" onclick="copySecretKey()" class="btn-icon" title="Copy secret key" aria-label="Copy secret key">
                                    <i data-lucide="copy" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Verification Form --}}
                <form action="{{ route('2fa.verify') }}" method="POST" class="space-y-4 border-t border-slate-200 dark:border-slate-700 pt-5">
                    @csrf
                    <input type="hidden" name="secret" id="secretInput" value="">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Verification Code</label>
                        <input type="text" name="code" id="verificationCode" required placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition text-center text-lg font-mono tracking-[0.3em]">
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Enter the 6-digit code shown in your authenticator app</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" id="primarySubmit"
                            class="btn btn-primary">
                            <i data-lucide="check" aria-hidden="true"></i>
                            Verify & Activate
                        </button>
                        <button type="button" onclick="cancelSetup()"
                            class="btn btn-ghost">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
function enableTwoFactor() {
    const btn = document.getElementById('enableTwoFactorBtn');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Generating secret...';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    fetch('{{ route("2fa.enable") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.secret) {
            // Render QR code locally using qrcode-generator (already loaded via CDN)
            var qrContainer = document.getElementById('qrCodeContainer');
            qrContainer.innerHTML = '';
            if (typeof qrcode === 'function') {
                var qr = qrcode(0, 'M');
                qr.addData(data.otpauth_url);
                qr.make();
                qrContainer.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 2 });
            } else {
                // Fallback: show otpauth URL as a link
                qrContainer.innerHTML = '<a href="' + data.otpauth_url + '" class="text-xs text-indigo-500 underline break-all p-2">' + data.otpauth_url + '</a>';
            }
            document.getElementById('secretKeyDisplay').textContent = data.secret;
            document.getElementById('secretInput').value = data.secret;
            document.getElementById('enableSection').classList.add('hidden');
            document.getElementById('setupPanel').classList.remove('hidden');
            document.getElementById('verificationCode').focus();
        } else {
            alert(data.message || 'Failed to generate 2FA secret. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    })
    .catch(function() {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    });
}

function cancelSetup() {
    document.getElementById('setupPanel').classList.add('hidden');
    document.getElementById('enableSection').classList.remove('hidden');
    const btn = document.getElementById('enableTwoFactorBtn');
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="shield" class="w-3.5 h-3.5"></i> Enable Two-Factor Authentication';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function copySecretKey() {
    var text = document.getElementById('secretKeyDisplay').textContent;
    navigator.clipboard.writeText(text).then(function() {
        showToast('Secret key copied to clipboard', 'success');
    });
}

function copyRecoveryCodes() {
    var codes = [];
    document.querySelectorAll('#recoveryCodesBox code').forEach(function(el) {
        codes.push(el.textContent.trim());
    });
    navigator.clipboard.writeText(codes.join('\n')).then(function() {
        showToast('Recovery codes copied to clipboard', 'success');
    });
}

function downloadRecoveryCodes() {
    var codes = [];
    document.querySelectorAll('#recoveryCodesBox code').forEach(function(el) {
        codes.push(el.textContent.trim());
    });
    var content = 'OpenBooks SG - Two-Factor Authentication Recovery Codes\n';
    content += 'Generated: ' + new Date().toISOString() + '\n';
    content += '========================================\n\n';
    codes.forEach(function(code, i) {
        content += (i + 1) + '. ' + code + '\n';
    });
    content += '\nKeep these codes in a safe place. Each code can only be used once.\n';

    var blob = new Blob([content], { type: 'text/plain' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'openbooks-recovery-codes.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function showToast(message, type) {
    var existing = document.getElementById('twoFaToast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'twoFaToast';
    toast.className = 'fixed bottom-6 right-6 z-50 animate-slide-down';

    var bgColor = type === 'success'
        ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-800 dark:text-emerald-400'
        : 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-800 dark:text-red-400';

    var icon = type === 'success' ? 'check-circle' : 'alert-circle';

    toast.innerHTML = '<div class="flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg text-xs font-medium border ' + bgColor + '">'
        + '<i data-lucide="' + icon + '" class="w-4 h-4"></i><span>' + message + '</span></div>';

    document.body.appendChild(toast);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    setTimeout(function() { toast.remove(); }, 4000);
}
</script>
@endsection
