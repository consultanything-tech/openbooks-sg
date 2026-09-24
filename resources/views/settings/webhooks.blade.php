@extends('layouts.app')

@section('title', 'Webhooks')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Webhooks</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Receive real-time HTTP callbacks when events occur in OpenBooks</p>
        </div>
        <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            Back to Settings
        </a>
    </div>

    {{-- Add Webhook Form --}}
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white mb-4">
            <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-500 mr-1.5"></i>Add New Webhook
        </h2>
        <form action="{{ route('webhooks.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Event *</label>
                    <select name="event" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                        <option value="">Select event...</option>
                        @foreach($events as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Endpoint URL *</label>
                    <input type="url" name="url" required placeholder="https://your-app.com/webhooks/openbooks"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Signing Secret <span class="normal-case font-normal text-slate-400">(optional)</span></label>
                    <input type="text" name="secret" placeholder="whsec_your_secret_key"
                        class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Used to generate HMAC-SHA256 signature in the X-OpenBooks-Signature header</p>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn btn-primary w-full">
                        <i data-lucide="plus" aria-hidden="true"></i>
                        Add Webhook
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Webhooks Table --}}
    <div class="bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                <i data-lucide="zap" class="w-4 h-4 text-indigo-500 mr-1.5"></i>Configured Webhooks
                <span class="ml-2 text-[10px] font-medium text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">{{ $webhooks->count() }}</span>
            </h2>
        </div>

        @if($webhooks->isEmpty())
            <div class="px-6 py-12 text-center">
                <i data-lucide="plug-zap" class="w-6 h-6 text-slate-300 dark:text-slate-600 mb-3"></i>
                <p class="text-sm text-slate-500 dark:text-slate-400">No webhooks configured yet</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Add a webhook above to start receiving event notifications</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                            <th class="px-6 py-3 text-left font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[10px]">Event</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[10px]">Endpoint URL</th>
                            <th class="px-6 py-3 text-center font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[10px]">Status</th>
                            <th class="px-6 py-3 text-left font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[10px]">Last Triggered</th>
                            <th class="px-6 py-3 text-right font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-[10px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($webhooks as $webhook)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center gap-1.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 px-2.5 py-1 rounded-lg font-medium text-[11px]">
                                        <i data-lucide="zap" class="w-3 h-3"></i>
                                        {{ $events[$webhook->event] ?? $webhook->event }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-slate-700 dark:text-slate-300 font-mono text-[11px] truncate max-w-[250px]" title="{{ $webhook->url }}">
                                    {{ Str::limit($webhook->url, 45) }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <form action="{{ route('webhooks.update', $webhook->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold transition {{ $webhook->is_active ? 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $webhook->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-3 text-slate-500 dark:text-slate-400">
                                    @if($webhook->last_triggered_at)
                                        {{ $webhook->last_triggered_at->diffForHumans() }}
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 italic">Never</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <form action="{{ route('webhooks.destroy', $webhook->id) }}" method="POST" onsubmit="return confirm('Delete this webhook?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete webhook" aria-label="Delete webhook">
                                            <i data-lucide="trash-2" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Documentation Card --}}
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">
            <i data-lucide="book" class="w-4 h-4 text-indigo-500 mr-1.5"></i>Payload Format
        </h3>
        <pre class="text-[11px] text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-900 rounded-xl p-4 overflow-x-auto border border-slate-200 dark:border-slate-700 font-mono"><code>{
  "event": "invoice.created",
  "data": { ... },
  "timestamp": "2026-09-21T12:00:00+08:00"
}</code></pre>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-3">
            <i data-lucide="shield" class="w-4 h-4 text-emerald-500 mr-1"></i>
            If a signing secret is set, an <code class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded text-[10px] font-mono">X-OpenBooks-Signature</code> header containing an HMAC-SHA256 hash of the request body will be included.
        </p>
    </div>
</div>
@endsection
