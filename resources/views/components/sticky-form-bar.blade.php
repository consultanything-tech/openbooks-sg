@props([
    'cancelUrl',
    'cancelLabel' => 'Cancel',
    'submitId' => 'primarySubmit',
    'saveLabel' => 'Save',
    'showSave' => true,
])

{{-- Sticky action bar: keeps Save/Cancel reachable without scrolling to the
     bottom of long create/edit forms. The Save button proxies a click to the
     form's real primary submit (#{submitId}) so multi-status forms
     (e.g. "Save as Draft" vs "Save") behave identically to the bottom bar. --}}
<div class="sticky top-0 z-20 py-3 border-b border-slate-200 dark:border-slate-800" style="background-color: var(--surface-raised)">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">{{ $title }}</div>
        <div class="flex items-center gap-2 shrink-0">
            {{ $actions ?? '' }}
            <a href="{{ $cancelUrl }}" class="btn btn-ghost">{{ $cancelLabel }}</a>
            @if($showSave)
                <button type="button" class="btn btn-primary" onclick="document.getElementById('{{ $submitId }}').click()">
                    <i data-lucide="save" aria-hidden="true"></i> {{ $saveLabel }}
                </button>
            @endif
        </div>
    </div>
</div>
