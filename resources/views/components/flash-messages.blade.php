@php
    $flashSuccess = session()->pull('success');
    $flashError = session()->pull('error');
@endphp

@if (filled($flashSuccess))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
        {{ $flashSuccess }}
    </div>
@endif

@if (filled($flashError))
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
        {{ $flashError }}
    </div>
@endif
