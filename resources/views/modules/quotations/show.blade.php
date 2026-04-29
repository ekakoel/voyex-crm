@extends('layouts.master')

@section('page_title', ui_phrase('show page title'))
@section('page_subtitle', ui_phrase('show page subtitle'))
@section('page_actions')
    @if (($canValidateQuotation ?? false) === true)
        <a href="{{ route('quotations.validate.show', $quotation) }}" class="btn-outline">{{ ui_phrase('Validate Quotation') }}</a>
    @endif
    @can('update', $quotation)
        @if (($quotation->status ?? '') !== 'final')
            <a href="{{ route('quotations.edit', $quotation) }}" class="btn-secondary">{{ ui_phrase('Edit') }}</a>
        @endif
    @endcan
    @if (in_array(($quotation->status ?? ''), ['approved', 'final'], true))
        <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener" class="btn-outline">{{ ui_phrase('Preview PDF') }}</a>
    @endif
    <a href="{{ route('quotations.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@push('scripts')
    <script>
        (function() {
            const openModal = (id) => {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = (id) => {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            };

            document.querySelectorAll('[data-open-reject-modal]').forEach((btn) => {
                btn.addEventListener('click', () => openModal(btn.getAttribute('data-open-reject-modal')));
            });

            document.querySelectorAll('[data-close-reject-modal]').forEach((btn) => {
                btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close-reject-modal')));
            });

            document.querySelectorAll('[id$="-reject-modal"]').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal(modal.id);
                });
            });

            @if ($errors->has('approval_note'))
                openModal('show-reject-modal');
            @endif
        })();
    </script>
@endpush

@section('content')
    @php
        $subTotal = (float) ($kpiSummary['sub_total'] ?? 0);
        $itemDiscountAmount = (float) ($kpiSummary['item_discount_total'] ?? 0);
        $globalDiscountAmount = (float) ($kpiSummary['global_discount_amount'] ?? 0);
        $finalAmount = (float) ($kpiSummary['final_amount'] ?? 0);
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
            <div class="module-grid-main">
                <div class="app-card p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Number') }}</p>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->quotation_number }}</h2>
                        </div>
                        <x-status-badge :status="$quotation->trashed() ? 'inactive' : $quotation->status" size="xs" />
                    </div>

                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Order Number') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->order_number ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Validity Date') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Destination') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->itinerary?->destination ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Booking') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->booking?->booking_number ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Itinerary') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">#{{ $quotation->itinerary?->id ?? '-' }} - {{ $quotation->itinerary?->title ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="app-card p-6">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Sub Total') }}</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100"><x-money :amount="$subTotal" currency="IDR" /></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Item Discount') }}</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100"><x-money :amount="$itemDiscountAmount" currency="IDR" /></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Global Discount') }}</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100"><x-money :amount="$globalDiscountAmount" currency="IDR" /></p>
                        </div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-700 dark:bg-emerald-900/20">
                            <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ ui_phrase('Final Amount') }}</p>
                            <p class="mt-1 text-lg font-semibold text-emerald-800 dark:text-emerald-200"><x-money :amount="$finalAmount" currency="IDR" /></p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Items') }}</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('items count', ['count' => $quotation->items->count()]) }}</span>
                    </div>

                    <div class="hidden md:block overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Description') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Qty') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Unit Price') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Discount Type') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Discount') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Total') }}</th>
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
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ ($item->discount_type ?? 'fixed') === 'percent' ? ui_phrase('Percent') : ui_phrase('Fixed') }}</td>
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
                                        <td colspan="6" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No items available.') }}</td>
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
                                    <div>{{ ui_phrase('Qty') }}</div><div class="text-right">{{ $item->qty }}</div>
                                    <div>{{ ui_phrase('Unit Price') }}</div><div class="text-right"><x-money :amount="$item->unit_price ?? 0" currency="IDR" /></div>
                                    <div>{{ ui_phrase('Discount') }}</div>
                                    <div class="text-right">
                                        @if (($item->discount_type ?? 'fixed') === 'percent')
                                            {{ number_format($item->discount ?? 0, 2, ',', '.') }}%
                                        @else
                                            <x-money :amount="$item->discount ?? 0" currency="IDR" />
                                        @endif
                                    </div>
                                    <div class="font-semibold">{{ ui_phrase('Total') }}</div><div class="text-right font-semibold"><x-money :amount="$item->total ?? 0" currency="IDR" /></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ ui_phrase('No items available.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="module-grid-side">
                @if ($quotation->itinerary?->inquiry)
                    <div class="app-card p-6">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('inquiry and itinerary') }}</h3>
                        <dl class="space-y-2 text-xs text-gray-700 dark:text-gray-200">
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Inquiry No') }}</dt><dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->inquiry_number ?? '-' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Customer:') }}</dt><dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->customer?->name ?? '-' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Inquiry Status') }}</dt><dd class="font-medium text-right">{{ $quotation->itinerary?->inquiry?->status ?? '-' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Itinerary') }}</dt><dd class="font-medium text-right">#{{ $quotation->itinerary?->id ?? '-' }} - {{ $quotation->itinerary?->title ?? '-' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Created by') }}</dt><dd class="font-medium text-right"><x-masked-user-name :user="$quotation->itinerary?->creator" /></dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Created At') }}</dt><dd class="font-medium text-right"><x-local-time :value="$quotation->itinerary?->created_at" /></dd></div>
                        </dl>
                    </div>
                @endif

                @php
                    $canAccessItineraryModule = auth()->user()?->can('module.itineraries.access');
                @endphp
                @if ($quotation->itinerary && $canAccessItineraryModule)
                    <div class="app-card p-6 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Quick Actions') }}</h3>
                        <a
                            href="{{ route('itineraries.show', $quotation->itinerary) }}"
                            class="btn-secondary w-full justify-center"
                        >
                            {{ ui_phrase('View Itinerary Detail') }}
                        </a>
                        <a
                            href="{{ route('itineraries.pdf', ['itinerary' => $quotation->itinerary->id, 'mode' => 'stream']) }}"
                            target="_blank"
                            rel="noopener"
                            class="btn-outline w-full justify-center"
                        >
                            {{ ui_phrase('View Itinerary PDF') }}
                        </a>
                    </div>
                @endif

                <div class="app-card p-6 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Activity Timeline') }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ ui_phrase('detailed audit log') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>

                @include('partials._quotation-comments', ['quotation' => $quotation])

                <div class="app-card p-6 space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Validation Progress') }}</h3>
                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ (string) ($validationProgress['status'] ?? 'pending') === 'valid' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : ((string) ($validationProgress['status'] ?? 'pending') === 'partial' ? 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300') }}">
                            {{ (string) ($validationProgress['status'] ?? 'pending') }}
                        </span>
                    </div>

                    <div class="space-y-2 text-xs text-gray-700 dark:text-gray-200">
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Required Validation') }}</span>
                            <span class="font-medium">{{ (int) ($validationProgress['total_required'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Validated Items') }}</span>
                            <span class="font-medium">{{ (int) ($validationProgress['total_validated'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Validation Progress') }}</span>
                            <span class="font-medium">{{ (int) ($validationProgress['validation_percent'] ?? 0) }}%</span>
                        </div>
                    </div>

                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div
                            class="h-full rounded-full bg-emerald-500 transition-all"
                            style="width: {{ max(0, min(100, (int) ($validationProgress['validation_percent'] ?? 0))) }}%;"
                        ></div>
                    </div>

                    @php
                        $validators = collect($validationProgress['validators'] ?? []);
                    @endphp
                    @if ($validators->isNotEmpty())
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('Validated By') }} ({{ (int) ($validationProgress['validators_count'] ?? $validators->count()) }})
                            </div>
                            <ul class="space-y-1.5 text-xs text-gray-700 dark:text-gray-200">
                                @foreach ($validators as $validator)
                                    <li class="flex items-center justify-between gap-2">
                                        <span class="font-medium">{{ $validator['name'] ?? '-' }}</span>
                                        <span class="whitespace-nowrap text-right text-gray-500 dark:text-gray-400">
                                            {{ (int) ($validator['validated_items'] ?? 0) }} item
                                            @if (!empty($validator['last_validated_at']))
                                                - <x-local-time :value="$validator['last_validated_at']" />
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (($canValidateQuotation ?? false) === true)
                        <a href="{{ route('quotations.validate.show', $quotation) }}" class="btn-outline w-full justify-center">
                            {{ ui_phrase('Validate Quotation') }}
                        </a>
                    @endif
                </div>

                <div class="app-card p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Approval') }}</h3>

                    <dl class="space-y-2 text-xs text-gray-700 dark:text-gray-200">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}</dt>
                            <dd class="font-medium text-right">{{ $quotation->status ?? '-' }}</dd>
                        </div>
                        @if (in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Approved by') }}</dt>
                                <dd class="font-medium text-right">{{ $quotation->approvedBy?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Approved at') }}</dt>
                                <dd class="font-medium text-right"><x-local-time :value="$quotation->approved_at" /></dd>
                            </div>
                        @endif
                    </dl>

                    <div class="grid grid-cols-1 gap-2">
                        <div class="flex items-center justify-between rounded-md border px-3 py-2 text-xs {{ ($approvalProgress['is_ready'] ?? false) ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300' }}">
                            <span class="inline-flex items-center gap-2">
                                <span class="inline-flex rounded-full border border-current px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">{{ ui_phrase('Rule') }}</span>
                                <span>{{ ui_phrase('minimum two non creator') }}</span>
                            </span>
                            <span>{{ (int) ($approvalProgress['non_creator_approval_count'] ?? 0) }}/{{ (int) ($approvalProgress['required_non_creator_approvals'] ?? 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-md border px-3 py-2 text-xs {{ ((int) ($approvalProgress['remaining_non_creator_approvals'] ?? 0) === 0) ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300' }}">
                            <span>{{ ui_phrase('Remaining approvals') }}</span>
                            <span>{{ (int) ($approvalProgress['remaining_non_creator_approvals'] ?? 0) }}</span>
                        </div>
                    </div>

                    @if (!empty($approvalProgress['missing_labels']))
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            {{ ui_phrase('waiting for', ['names' => implode(', ', $approvalProgress['missing_labels'])]) }}
                        </p>
                    @endif

                    @if ($quotation->approvals->isNotEmpty())
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Approval Log') }}</div>
                            <ul class="mt-2 space-y-1 text-xs text-gray-700 dark:text-gray-200">
                                @foreach ($quotation->approvals as $approval)
                                    <li>
                                        {{ ucfirst((string) $approval->approval_role) }} - <x-masked-user-name :user="$approval->user" />
                                        @if ($approval->approved_at)
                                            (<x-local-time :value="$approval->approved_at" />)
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($quotation->approval_note))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                            <div class="font-semibold">{{ ui_phrase('Validation Note') }}</div>
                            <p class="mt-1">{{ $quotation->approval_note }}</p>
                        </div>
                    @endif

                    @if (auth()->check() && (auth()->user()?->can('quotations.approve') || $quotation->isCreator(auth()->user()) || auth()->user()?->can('quotations.reject') || auth()->user()?->can('quotations.set_pending')))
                        <div class="space-y-3">
                            @php
                                $authUser = auth()->user();
                                $isCreator = $quotation->isCreator($authUser);
                                $alreadyApprovedByUser = $authUser
                                    ? $quotation->approvals->contains(fn ($a) => (int) ($a->user_id ?? 0) === (int) $authUser->id)
                                    : false;
                                $requiredApprovals = (int) ($approvalProgress['required_non_creator_approvals'] ?? 2);
                                $nonCreatorApprovalCount = (int) ($approvalProgress['non_creator_approval_count'] ?? 0);
                                $canApproveByRole = false;
                                if (!$isCreator && !$alreadyApprovedByUser && $authUser) {
                                    $canApproveByRole = $authUser->can('quotations.approve')
                                        && $nonCreatorApprovalCount < $requiredApprovals;
                                }
                                $isValidationComplete = (bool) ($validationProgress['is_complete'] ?? false);
                                $requiresValidation = (bool) ($validationProgress['requires_validation'] ?? false);
                                $canApproveWithValidation = $canApproveByRole && (! $requiresValidation || $isValidationComplete);
                            @endphp
                            <div class="flex flex-wrap items-center gap-2">
                                @if (($quotation->status ?? '') === 'approved')
                                    @if ($quotation->isCreator(auth()->user()))
                                        <form method="POST" action="{{ route('quotations.set-final', $quotation) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary-sm">{{ ui_phrase('Set Final') }}</button>
                                        </form>
                                    @endif
                                    @if (auth()->user()?->can('quotations.set_pending'))
                                        <form method="POST" action="{{ route('quotations.set-pending', $quotation) }}">
                                            @csrf
                                            <button type="submit" class="btn-warning-sm">{{ ui_phrase('Set Pending') }}</button>
                                        </form>
                                    @endif
                                @elseif (($quotation->status ?? '') === 'final')
                                    @if (auth()->user()?->can('quotations.set_pending'))
                                        <form method="POST" action="{{ route('quotations.set-pending', $quotation) }}">
                                            @csrf
                                            <button type="submit" class="btn-warning-sm">{{ ui_phrase('Set Pending') }}</button>
                                        </form>
                                    @endif
                                @else
                                    @if ($canApproveWithValidation)
                                        <form method="POST" action="{{ route('quotations.approve', $quotation) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary-sm">{{ ui_phrase('Approve') }}</button>
                                        </form>
                                    @endif
                                    @can('quotations.reject')
                                        <button type="button" class="btn-danger-sm" data-open-reject-modal="show-reject-modal">{{ ui_phrase('Reject') }}</button>
                                    @endcan
                                @endif
                            </div>
                            @if (! $canApproveByRole && ! in_array((string) ($quotation->status ?? ''), ['approved', 'final'], true))
                                <p class="text-xs text-amber-700 dark:text-amber-300">
                                    @if ($alreadyApprovedByUser)
                                        {{ ui_phrase('approval already done') }}
                                    @else
                                        {{ ui_phrase('approval not available') }}
                                    @endif
                                </p>
                            @endif
                            @if ($canApproveByRole && ! $canApproveWithValidation && ! in_array((string) ($quotation->status ?? ''), ['approved', 'final'], true))
                                <p class="text-xs text-rose-600 dark:text-rose-300">
                                    {{ ui_phrase('approval requires validation') }}
                                </p>
                            @endif
                        </div>
                    @endif

                    @if (auth()->user()?->can('quotations.reject') && ($quotation->status ?? '') !== 'approved' && ($quotation->status ?? '') !== 'final')
                        <div id="show-reject-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
                            <div class="w-full max-w-lg rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Reject Quotation') }}</h3>
                                    <button type="button" class="btn-ghost px-2 py-1 text-xs" data-close-reject-modal="show-reject-modal">{{ ui_phrase('Close') }}</button>
                                </div>
                                <form method="POST" action="{{ route('quotations.reject', $quotation) }}" class="mt-3 space-y-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Reason Note') }}</label>
                                        <textarea
                                            name="approval_note"
                                            rows="4"
                                            class="mt-1 w-full app-input"
                                            placeholder="{{ ui_phrase('reject placeholder') }}"
                                            required
                                        >{{ old('approval_note') }}</textarea>
                                        @error('approval_note')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="btn-secondary-sm" data-close-reject-modal="show-reject-modal">{{ ui_phrase('Cancel') }}</button>
                                        <button type="submit" class="btn-danger-sm">{{ ui_phrase('Confirm Reject') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>

            </aside>
        </div>
    </div>
@endsection

