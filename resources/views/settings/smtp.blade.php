@extends('layouts.app')

@section('title', 'SMTP & Email Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <x-sticky-form-bar :cancel-url="route('settings.index')">
        <x-slot:title>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">SMTP & Email Settings</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Configure outgoing mail server and automated payment reminder schedules</p>
            </div>
        </x-slot:title>
    </x-sticky-form-bar>

    {{-- SMTP Configuration Card --}}
    <form action="{{ route('settings.smtp.update') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">
                        <i data-lucide="mail" class="w-4 h-4 text-indigo-500 mr-1.5"></i>SMTP Configuration
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Outgoing mail server credentials used for invoices, reminders, and notifications</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- SMTP Host --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">SMTP Host *</label>
                    <input type="text" name="smtp_host" value="{{ old('smtp_host', $company->smtp_host ?? '') }}" required placeholder="smtp.example.com"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>

                {{-- SMTP Port --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">SMTP Port *</label>
                    <input type="number" name="smtp_port" value="{{ old('smtp_port', $company->smtp_port ?? 587) }}" required placeholder="587"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Common ports: 25, 465 (SSL), 587 (TLS)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- SMTP Username --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">SMTP Username *</label>
                    <input type="text" name="smtp_username" value="{{ old('smtp_username', $company->smtp_username ?? '') }}" required placeholder="user@example.com"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>

                {{-- SMTP Password --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">SMTP Password *</label>
                    <div class="relative">
                        <input type="password" name="smtp_password" id="smtpPassword" value="{{ old('smtp_password', $company->smtp_password ?? '') }}" required placeholder="••••••••"
                            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 pr-10 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <button type="button" onclick="var i=document.getElementById('smtpPassword'); var s=i.type==='password'; i.type=s?'text':'password'; this.querySelector('.eye-on').classList.toggle('hidden',s); this.querySelector('.eye-off').classList.toggle('hidden',!s); this.setAttribute('aria-pressed',String(s)); this.setAttribute('aria-label', s?'Hide password':'Show password');" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition" aria-label="Show password" aria-pressed="false">
                            <i data-lucide="eye" class="eye-on w-4 h-4" aria-hidden="true"></i>
                            <i data-lucide="eye-off" class="eye-off w-4 h-4 hidden" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Encryption --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Encryption</label>
                    <select name="smtp_encryption"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white appearance-none cursor-pointer focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="tls" {{ (old('smtp_encryption', $company->smtp_encryption ?? 'tls')) === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ (old('smtp_encryption', $company->smtp_encryption ?? '')) === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="none" {{ (old('smtp_encryption', $company->smtp_encryption ?? '')) === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </div>

                {{-- From Email --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">From Email *</label>
                    <input type="email" name="smtp_from_email" value="{{ old('smtp_from_email', $company->smtp_from_email ?? '') }}" required placeholder="noreply@example.com"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>

                {{-- From Name --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">From Name *</label>
                    <input type="text" name="smtp_from_name" value="{{ old('smtp_from_name', $company->smtp_from_name ?? '') }}" required placeholder="OpenBooks SG"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </div>
        </div>

        {{-- Payment Reminders Card --}}
        <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-1">
                        <i data-lucide="bell" class="w-4 h-4 text-amber-500 mr-1.5"></i>Payment Reminders
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Automated email reminders sent to customers for overdue invoices</p>
                </div>
            </div>

            {{-- Auto Reminders Toggle --}}
            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                <div>
                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">Enable Automatic Reminders</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">When enabled, reminder emails will be sent automatically based on the schedule below</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer ml-4">
                    <input type="hidden" name="auto_reminders_enabled" value="0">
                    <input type="checkbox" name="auto_reminders_enabled" value="1" id="autoRemindersToggle"
                        {{ old('auto_reminders_enabled', $company->auto_reminders_enabled ?? false) ? 'checked' : '' }}
                        class="sr-only peer" onchange="toggleReminderFields(this.checked)">
                    <div class="w-9 h-5 bg-slate-300 dark:bg-slate-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            {{-- Reminder Days --}}
            <div id="reminderFields" class="grid grid-cols-1 md:grid-cols-3 gap-4 {{ old('auto_reminders_enabled', $company->auto_reminders_enabled ?? false) ? '' : 'opacity-50 pointer-events-none' }}">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">First Reminder (Days)</label>
                    <input type="number" name="reminder_days_1" value="{{ old('reminder_days_1', $company->reminder_days_1 ?? 3) }}" min="1" max="90"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Days after due date for 1st reminder</p>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Second Reminder (Days)</label>
                    <input type="number" name="reminder_days_2" value="{{ old('reminder_days_2', $company->reminder_days_2 ?? 7) }}" min="1" max="90"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Days after due date for 2nd reminder</p>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Third Reminder (Days)</label>
                    <input type="number" name="reminder_days_3" value="{{ old('reminder_days_3', $company->reminder_days_3 ?? 14) }}" min="1" max="90"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Days after due date for 3rd reminder</p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('settings.index') }}" class="btn btn-ghost">
                Cancel
            </a>
            <div class="flex items-center gap-3">
                <button type="button" onclick="sendTestEmail()" id="testEmailBtn"
                    class="btn btn-secondary">
                    <i data-lucide="send" aria-hidden="true"></i>
                    Test Connection
                </button>
                <button type="submit" id="primarySubmit"
                    class="btn btn-primary">
                    <i data-lucide="save" aria-hidden="true"></i>
                    Save
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Toast Container --}}
<div id="smtpToast" class="fixed bottom-6 right-6 z-50 hidden">
    <div id="smtpToastContent" class="flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg text-xs font-medium border"></div>
</div>
@endsection

@section('scripts')
<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i data-lucide="eye-off" class="w-4 h-4"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i>';
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function toggleReminderFields(enabled) {
    const fields = document.getElementById('reminderFields');
    if (enabled) {
        fields.classList.remove('opacity-50', 'pointer-events-none');
    } else {
        fields.classList.add('opacity-50', 'pointer-events-none');
    }
}

function showToast(message, type) {
    const toast = document.getElementById('smtpToast');
    const content = document.getElementById('smtpToastContent');

    if (type === 'success') {
        content.className = 'flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg text-xs font-medium border bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-800 dark:text-emerald-400';
        content.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i><span>' + message + '</span>';
    } else {
        content.className = 'flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg text-xs font-medium border bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-800 dark:text-red-400';
        content.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 text-red-600 dark:text-red-400"></i><span>' + message + '</span>';
    }

    toast.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
    setTimeout(function() { toast.classList.add('hidden'); }, 5000);
}

function sendTestEmail() {
    const btn = document.getElementById('testEmailBtn');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Sending...';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    fetch('{{ route("settings.smtp.test") }}', {
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
        if (data.success) {
            showToast(data.message || 'Test email sent successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to send test email.', 'error');
        }
    })
    .catch(function() {
        showToast('Network error. Please try again.', 'error');
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
}
</script>
@endsection
