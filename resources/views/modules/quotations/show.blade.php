@extends('layouts.master')

@section('page_title', 'Quotation Detail')
@section('page_subtitle', 'Review quotation details, pricing breakdown, and approval workflow.')
@section('page_actions')
    @can('update', $quotation)
        @if (! in_array(($quotation->status ?? ''), ['approved', 'final'], true))
            <a href="{{ route('quotations.edit', $quotation) }}" class="btn-secondary">Edit</a>
        @endif
    @endcan
    <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener" class="btn-outline">PDF</a>
    <a href="{{ route('quotations.index') }}" class="btn-ghost">Back</a>
@endsection

@section('content')
    @php
        $subTotal = (float) ($quotation->sub_total ?? 0);
        $discountType = $quotation->discount_type ?? null;
        $discountValue = (float) ($quotation->discount_value ?? 0);
        $discountAmount = 0;

        if ($discountType === 'percent') {
            $discountAmount = $subTotal * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        }
    @endphp

    <div class="space-y-6 module-page module-page--quotations">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="module-grid-8-4">
            <div class="module-grid-main space-y-4">
                <div class="app-card p-6 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Quotation Number</p>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->quotation_number }}</h2>
                        </div>
                        <x-status-badge :status="$quotation->status" size="xs" />
                    </div>

                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Validity Date</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Destination</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->itinerary?->destination ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Booking</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->booking?->booking_number ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Itinerary</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">#{{ $quotation->itinerary?->id ?? '-' }} - {{ $quotation->itinerary?->title ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="app-card p-6">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Sub Total</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100"><x-money :amount="$subTotal" currency="IDR" /></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Discount Type</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                @if ($discountType === 'percent')
                                    Percent ({{ number_format($discountValue, 2, ',', '.') }}%)
                                @elseif ($discountType === 'fixed')
                                    Fixed
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Discount Amount</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100"><x-money :amount="$discountAmount" currency="IDR" /></p>
                        </div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-700 dark:bg-emerald-900/20">
                            <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Final Amount</p>
                            <p class="mt-1 text-lg font-semibold text-emerald-800 dark:text-emerald-200"><x-money :amount="$quotation->final_amount ?? 0" currency="IDR" /></p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-6 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Items</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $quotation->items->count() }} item</span>
                    </div>

                    <div class="hidden md:block overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Description</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Qty</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Unit Price</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Discount Type</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Discount</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($quotation->items as $item)
                                    @php
                                        $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                        $paxType = strtolower((string) ($meta['pax_type'] ?? ''));
                                        $paxBadgeLabel = $paxType === 'adult' ? 'Adult Publish Rate' : ($paxType === 'child' ? 'Child Publish Rate' : '');
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span>{{ $item->description }}</span>
                                                @if ($paxBadgeLabel !== '')
                                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $paxType === 'child' ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                                                        {{ $paxBadgeLabel }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">{{ $item->qty }}</td>
                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200"><x-money :amount="$item->unit_price ?? 0" currency="IDR" /></td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ ($item->discount_type ?? 'fixed') === 'percent' ? 'Percent' : 'Fixed' }}</td>
                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                            @if (($item->discount_type ?? 'fixed') === 'percent')
                                                {{ number_format($item->discount ?? 0, 2, ',', '.') }}%
                                            @else
                                                <x-money :amount="$item->discount ?? 0" currency="IDR" />
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium text-gray-700 dark:text-gray-200"><x-money :amount="$item->total ?? 0" currency="IDR" /></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No items available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="md:hidden space-y-3">
                        @forelse ($quotation->items as $item)
                            @php
                                $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                $paxType = strtolower((string) ($meta['pax_type'] ?? ''));
                                $paxBadgeLabel = $paxType === 'adult' ? 'Adult Publish Rate' : ($paxType === 'child' ? 'Child Publish Rate' : '');
                            @endphp
                            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $item->description }}</p>
                                    @if ($paxBadgeLabel !== '')
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $paxType === 'child' ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">{{ $paxBadgeLabel }}</span>
                                    @endif
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    <div>Qty</div><div class="text-right">{{ $item->qty }}</div>
                                    <div>Unit Price</div><div class="text-right"><x-money :amount="$item->unit_price ?? 0" currency="IDR" /></div>
                                    <div>Discount</div>
                                    <div class="text-right">
                                        @if (($item->discount_type ?? 'fixed') === 'percent')
                                            {{ number_format($item->discount ?? 0, 2, ',', '.') }}%
                                        @else
                                            <x-money :amount="$item->discount ?? 0" currency="IDR" />
                                        @endif
                                    </div>
                                    <div class="font-semibold">Total</div><div class="text-right font-semibold"><x-money :amount="$item->total ?? 0" currency="IDR" /></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">No items available.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="module-grid-side space-y-4">
                @if ($quotation->itinerary?->inquiry)
                    <div class="app-card p-6 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Inquiry & Itinerary</h3>
                        <dl class="space-y-2 text-xs text-gray-700 dark:text-gray-200">
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Inquiry No</dt><dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->inquiry_number ?? '-' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Customer</dt><dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->customer?->name ?? '-' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Inquiry Status</dt><dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->status ?? '-' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Itinerary</dt><dd class="font-medium text-right">#{{ $quotation->itinerary?->id ?? '-' }} - {{ $quotation->itinerary?->title ?? '-' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Created By</dt><dd class="font-medium text-right">{{ $quotation->itinerary?->creator?->name ?? '-' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">Created At</dt><dd class="font-medium text-right">{{ $quotation->itinerary?->created_at?->format('Y-m-d H:i') ?? '-' }}</dd></div>
                        </dl>
                    </div>
                @endif

                @include('partials._quotation-comments', ['quotation' => $quotation])

                <div class="app-card p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Validation</h3>

                    <dl class="space-y-2 text-xs text-gray-700 dark:text-gray-200">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="font-medium text-right">{{ $quotation->status ?? '-' }}</dd>
                        </div>
                        @if (($quotation->status ?? '') === 'approved')
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">Approved by</dt>
                                <dd class="font-medium text-right">{{ $quotation->approvedBy?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">Approved at</dt>
                                <dd class="font-medium text-right">{{ $quotation->approved_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if (! empty($quotation->approval_note))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                            <div class="font-semibold">Validation Note</div>
                            <p class="mt-1">{{ $quotation->approval_note }}</p>
                        </div>
                    @endif

                    @if (auth()->user()?->hasAnyRole(['Director']) && ($quotation->status ?? '') !== 'final')
                        <form method="POST" action="{{ route('quotations.approve', $quotation) }}" class="space-y-3">
                            @csrf
                            <details class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <summary class="cursor-pointer text-xs font-semibold text-gray-700 dark:text-gray-200">
                                    <i class="fa-solid fa-comment-dots mr-2"></i>Add note
                                </summary>
                                <div class="mt-2">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400">Validation Note</label>
                                    <textarea name="approval_note" rows="3" class="mt-1 w-full app-input" placeholder="Catatan terkait status yang akan dilakukan">{{ old('approval_note', $quotation->approval_note ?? '') }}</textarea>
                                    @error('approval_note')
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </details>

                            <div class="flex flex-wrap items-center gap-2">
                                @if (($quotation->status ?? '') !== 'approved')
                                    <button type="submit" formaction="{{ route('quotations.approve', $quotation) }}" class="btn-primary-sm">Approve</button>
                                    <button type="submit" formaction="{{ route('quotations.reject', $quotation) }}" class="btn-danger-sm">Reject</button>
                                @else
                                    <button type="submit" formaction="{{ route('quotations.set-pending', $quotation) }}" class="btn-warning-sm">Set Pending</button>
                                @endif
                            </div>
                        </form>
                    @endif
                </div>

                @include('partials._audit-info', ['record' => $quotation, 'title' => 'Audit Info'])
            </aside>
        </div>
    </div>
@endsection
