@if($canQuotations)
    <div>
        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Pending Quotations</h3>
        <div class="mt-3 space-y-2">
            @forelse($pendingQuotations as $quotation)
                <a href="{{ route('quotations.show', $quotation) }}" class="block rounded-lg bg-slate-50 px-3 py-2 text-xs hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800" data-progressive-item>
                    <p class="font-bold text-slate-700 dark:text-slate-200">{{ $quotation->quotation_number }}</p>
                    <p class="text-slate-500 dark:text-slate-400">
                        Customer: {{ $quotation->inquiry?->customer?->name ?? 'N/A' }}
                    </p>
                </a>
            @empty
                <p class="text-xs text-slate-500 dark:text-slate-400" data-progressive-item>No pending quotations.</p>
            @endforelse
        </div>
    </div>
@endif
