@extends('layouts.master')

@section('page_title', ui_phrase('show page title'))
@section('page_subtitle', ui_phrase('show page subtitle'))
@section('page_actions')
    @include('modules.quotations.partials.action-buttons', [
        'quotation' => $quotation,
        'availableActions' => $availableActions ?? [],
    ])
    <a href="{{ route('quotations.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    @php
        $subTotal = (float) ($kpiSummary['sub_total'] ?? 0);
        $globalDiscountAmount = (float) ($kpiSummary['global_discount_amount'] ?? 0);
        $finalAmount = (float) ($kpiSummary['final_amount'] ?? 0);
        $workflowNotice = $workflowOverview['notice'] ?? null;
        $displayQuotationStatus = (string) ($quotation->status ?? 'draft');
        if (! ($bookingsModuleEnabled ?? false) && in_array($displayQuotationStatus, ['converted_to_booking', 'booking_created', 'booking_in_progress', 'booking_issue'], true)) {
            $displayQuotationStatus = 'approved';
        }
        $workflowVisibilityCaption = match (true) {
            ($bookingsModuleEnabled ?? false) && ($invoicesModuleEnabled ?? false) => ui_phrase('Track quotation, validation, approval, booking, invoice, payment, and operation status in one view.'),
            ($bookingsModuleEnabled ?? false) => ui_phrase('Track quotation, validation, approval, booking, and operation status in one view.'),
            ($invoicesModuleEnabled ?? false) => ui_phrase('Track quotation, validation, approval, invoice, and payment status in one view.'),
            default => ui_phrase('Track quotation, validation, and approval status in one view.'),
        };
    @endphp

    <div class="space-y-6 module-page module-page--quotations">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                @if (!empty($workflowNotice))
                    <x-ui.lock-alert
                        :title="$workflowNotice['title'] ?? null"
                        :message="$workflowNotice['message'] ?? null"
                        :type="$workflowNotice['type'] ?? 'warning'"
                    />
                @endif
                <div class="app-card p-6 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Workflow Visibility') }}</h3>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ $workflowVisibilityCaption }}
                            </p>
                        </div>
                        <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                            <div>{{ ui_phrase('Current Stage') }}</div>
                            <div class="mt-1 font-semibold text-gray-800 dark:text-gray-100">{{ $workflowOverview['current_stage'] ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach (($workflowOverview['status_cards'] ?? []) as $statusCard)
                            @php
                                $tone = (string) ($statusCard['tone'] ?? 'muted');
                                $toneClass = match ($tone) {
                                    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
                                    'warning' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                                    'danger' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300',
                                    'info' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
                                    default => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-300',
                                };
                            @endphp
                            <div class="rounded-lg border px-3 py-2 {{ $toneClass }}">
                                <p class="text-[11px] font-semibold uppercase tracking-wide opacity-80">{{ $statusCard['label'] ?? '-' }}</p>
                                <p class="mt-1 text-sm font-semibold">{{ \Illuminate\Support\Str::headline((string) ($statusCard['value'] ?? '-')) }}</p>
                            </div>
                        @endforeach
                    </div>

                    <dl class="grid grid-cols-1 gap-3 text-xs text-gray-700 dark:text-gray-200 sm:grid-cols-2 xl:grid-cols-5">
                        @foreach (($workflowOverview['meta'] ?? []) as $metaRow)
                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                <dt class="text-gray-500 dark:text-gray-400">{{ $metaRow['label'] ?? '-' }}</dt>
                                <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ $metaRow['value'] ?? '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @php
                        $currentRevisionRow = collect($revisionHistory ?? [])
                            ->sortByDesc(fn ($row) => (int) ($row->revision_number ?? 1))
                            ->firstWhere('id', $quotation->id);
                    @endphp
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="mb-2 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Current Version') }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $currentRevisionRow?->getAttribute('revision_label') ?? ui_phrase('Original') }}
                                </p>
                            </div>
                            <x-ui.status-badge :status="$displayQuotationStatus" size="xs" />
                        </div>
                        <dl class="grid grid-cols-1 gap-2 text-xs text-gray-700 dark:text-gray-200 sm:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Revision Status') }}</dt>
                                <dd class="font-medium">{{ $currentRevisionRow?->getAttribute('revision_status_label') ?? ui_phrase('Original Quotation') }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Last Revision Date') }}</dt>
                                <dd class="font-medium"><x-local-time :value="$currentRevisionRow?->getAttribute('revision_finished_at') ?? $quotation->updated_at" /></dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Last Revised By') }}</dt>
                                <dd class="font-medium">{{ $currentRevisionRow?->getAttribute('revision_finished_by_name') ?? ui_phrase('Not revised yet') }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Revisions') }}</dt>
                                <dd class="font-medium">{{ max(0, collect($revisionHistory ?? [])->count() - 1) }}</dd>
                            </div>
                            <div class="sm:col-span-2 xl:col-span-4">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Current Validation Progress') }}</dt>
                                <dd class="font-medium">{{ $currentRevisionRow?->getAttribute('revision_progress_text') ?? ((int) ($validationProgress['validation_percent'] ?? 0) . '%') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Validation Progress') }}</p>
                            <x-ui.status-badge :status="(string) ($validationProgress['status'] ?? 'pending')" size="xs" />
                        </div>
                        <div class="space-y-1.5 text-xs text-gray-700 dark:text-gray-200">
                            <div class="flex justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Required Validation') }}</span>
                                <span class="font-medium">{{ (int) ($validationProgress['total_required'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Total Validated Items') }}</span>
                                <span class="font-medium">{{ (int) ($validationProgress['total_validated'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Progress') }}</span>
                                <span class="font-medium">{{ (int) ($validationProgress['validation_percent'] ?? 0) }}%</span>
                            </div>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div
                                class="h-full rounded-full bg-emerald-500 transition-all"
                                style="width: {{ max(0, min(100, (int) ($validationProgress['validation_percent'] ?? 0))) }}%;"
                            ></div>
                        </div>
                    </div>

                    @php
                        $workflowValidators = collect($validationProgress['validators'] ?? []);
                    @endphp
                    @if ($workflowValidators->isNotEmpty())
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ ui_phrase('Validated By') }} ({{ (int) ($validationProgress['validators_count'] ?? $workflowValidators->count()) }})
                            </div>
                            <ul class="space-y-1.5 text-xs text-gray-700 dark:text-gray-200">
                                @foreach ($workflowValidators as $validator)
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

                    @if (! empty($workflowOverview['risks'] ?? []))
                        <div class="space-y-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Risk / Warning Indicators') }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach (($workflowOverview['risks'] ?? []) as $risk)
                                    @php
                                        $riskTone = (string) ($risk['tone'] ?? 'warning');
                                        $riskClass = $riskTone === 'danger'
                                            ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300'
                                            : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300';
                                    @endphp
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $riskClass }}">{{ $risk['label'] ?? '-' }}</span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                            {{ ui_phrase('No active workflow risk detected.') }}
                        </div>
                    @endif
                </div>
                <div class="app-card p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="mb-3">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Number') }}</p>
                            <h2 class="text-lg pb-3 font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->quotation_number }}</h2>
                        </div>
                        <x-ui.status-badge :status="$quotation->trashed() ? 'inactive' : $displayQuotationStatus" size="xs" />
                    </div>

                    @php
                        $quotationInquiry = $quotation->inquiry;
                        $quotationCustomer = $quotationInquiry?->customer;
                        $quotationCustomerCode = trim((string) ($quotationCustomer?->code ?? ''));
                        $quotationCustomerName = trim((string) ($quotationCustomer?->name ?? $quotationCustomer?->company_name ?? ''));
                        $quotationCustomerLabel = $quotationCustomerName !== ''
                            ? (($quotationCustomerCode !== '' ? '(' . $quotationCustomerCode . ') ' : '') . $quotationCustomerName)
                            : '-';
                        $quotationInquiryNotes = \App\Support\SafeRichText::sanitize((string) ($quotationInquiry?->notes ?? ''));
                    @endphp
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Order Number') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->order_number ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Inquiry') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                @if ($quotation->inquiry_id && Route::has('inquiries.show') && auth()->user()->can('module.inquiries.access'))
                                    <a href="{{ route('inquiries.show', $quotation->inquiry_id) }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200">
                                        {{ $quotation->inquiry?->inquiry_number ?? '-' }}
                                    </a>
                                @else
                                    {{ $quotation->inquiry?->inquiry_number ?? '-' }}
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Customer Name') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotationCustomerLabel }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Deadline Date') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100"><x-ui.date-display :date="$quotationInquiry?->deadline" format="Y-m-d" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Source Itinerary') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                @if ($quotation->itinerary_id && Route::has('itineraries.show') && auth()->user()->can('module.itineraries.access'))
                                    <a href="{{ route('itineraries.show', $quotation->itinerary_id) }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200">
                                        #{{ $quotation->itinerary_id }} - {{ $quotation->itinerary?->title ?? ui_phrase('Itinerary') }}
                                    </a>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Service Date') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100"><x-ui.date-display :date="$quotation->service_date" format="Y-m-d" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Validity Date') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100"><x-ui.date-display :date="$quotation->validity_date" format="Y-m-d" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Destination') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->itinerary?->destination ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Pax (Adult / Child)') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ (int) ($quotation->pax_adult ?? 0) }} / {{ (int) ($quotation->pax_child ?? 0) }}</dd>
                        </div>
                        @if($bookingsModuleEnabled ?? false)
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Booking') }}</dt>
                                <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->booking?->booking_number ?? '-' }}</dd>
                            </div>
                        @endif
                        <div class="sm:col-span-2 lg:col-span-3">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Notes') }}</dt>
                            <dd class="prose prose-sm mt-1 max-w-none text-sm font-medium text-gray-800 dark:prose-invert dark:text-gray-100">
                                {!! $quotationInquiryNotes !== '' ? $quotationInquiryNotes : '-' !!}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="app-card p-6">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Sub Total') }}</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100"><x-ui.money :amount="$subTotal" :currency="$currentCurrency ?? 'IDR'" /></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Discount') }}</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100"><x-ui.money :amount="$globalDiscountAmount" :currency="$currentCurrency ?? 'IDR'" /></p>
                        </div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-700 dark:bg-emerald-900/20">
                            <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ ui_phrase('Final Amount') }}</p>
                            <p class="mt-1 text-lg font-semibold text-emerald-800 dark:text-emerald-200"><x-ui.money :amount="$finalAmount" :currency="$currentCurrency ?? 'IDR'" /></p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Services') }}</h3>
                    </div>
                    @php
                        $groupedItemsByDay = $groupedItemsByDay ?? collect();
                    @endphp

                    <div class="hidden md:block overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="table-header">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Description') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Status') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Contract Rate') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Markup') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Unit Price') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Qty') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">{{ ui_phrase('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($groupedItemsByDay as $dayKey => $dayItems)
                                    <tr>
                                        <td colspan="7" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700 bg-indigo-50/60 dark:bg-indigo-900/20 dark:text-indigo-300">
                                            {{ $dayKey === 'without_day' ? ui_phrase('Additional Services') : ui_phrase('Day') . ' ' . $dayKey }}
                                        </td>
                                    </tr>
                                    @foreach ($dayItems as $item)
                                        @php
                                            $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                            $paxType = strtolower((string) ($meta['pax_type'] ?? ''));
                                            $paxBadgeLabel = $paxType === 'adult' ? ui_phrase('Adult Publish Rate') : ($paxType === 'child' ? ui_phrase('Child Publish Rate') : '');
                                            $cleanDescription = trim((string) ($item->description ?? ''));
                                            $cleanDescription = preg_replace('/^\s*Day\s+\d+\s*-\s*/i', '', $cleanDescription) ?? $cleanDescription;
                                            $cleanDescription = preg_replace('/^\s*Without\s+Day\s*-\s*/i', '', $cleanDescription) ?? $cleanDescription;
                                            if ($cleanDescription === '') {
                                                $cleanDescription = '-';
                                            }
                                            $typePart = '';
                                            $detailPart = $cleanDescription;
                                            if (preg_match('/^\s*([^:]+):\s*(.+)$/', $cleanDescription, $typeMatches)) {
                                                $typePart = trim((string) ($typeMatches[1] ?? ''));
                                                $detailPart = trim((string) ($typeMatches[2] ?? ''));
                                            } elseif (str_contains($cleanDescription, ' - ')) {
                                                [$typePart, $detailPart] = explode(' - ', $cleanDescription, 2);
                                                $typePart = trim((string) $typePart);
                                                $detailPart = trim((string) $detailPart);
                                            }
                                            $typeColorClass = 'text-primary dark:text-teal-300';
                                        @endphp
                                        <tr>
                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if ($detailPart !== '')
                                                        @if (trim((string) $typePart) !== '')
                                                            <span class="font-semibold {{ $typeColorClass }}">{{ trim((string) $typePart) }}</span>
                                                            <span class="text-gray-500 dark:text-gray-400">:</span>
                                                        @endif
                                                        <span class="font-normal text-gray-900 dark:text-gray-100">{{ trim((string) $detailPart) }}</span>
                                                    @else
                                                        <span class="font-normal text-gray-900 dark:text-gray-100">{{ $cleanDescription }}</span>
                                                    @endif
                                                    @if ($paxBadgeLabel !== '')
                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $paxType === 'child' ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                                                            {{ $paxBadgeLabel }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-left text-gray-700 dark:text-gray-200">
                                                <x-ui.status-badge :status="(string) ($item->status ?? 'active')" size="xs" />
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                                <x-ui.money :amount="$item->contract_rate ?? 0" :currency="$currentCurrency ?? 'IDR'" />
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                                @if (($item->markup_type ?? 'fixed') === 'percent')
                                                    {{ number_format($item->markup ?? 0, 2, ',', '.') }}%
                                                @else
                                                    <x-ui.money :amount="$item->markup ?? 0" :currency="$currentCurrency ?? 'IDR'" />
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200"><x-ui.money :amount="$item->unit_price ?? 0" :currency="$currentCurrency ?? 'IDR'" /></td>
                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">{{ $item->qty }}</td>
                                            <td class="px-3 py-2 text-right font-medium text-gray-700 dark:text-gray-200"><x-ui.money :amount="$item->total ?? 0" :currency="$currentCurrency ?? 'IDR'" /></td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No items available.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="border-t border-gray-200 dark:border-gray-700">
                                <tr>
                                    <td colspan="6" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('Sub Total') }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        <x-ui.money :amount="$subTotal" :currency="$currentCurrency ?? 'IDR'" />
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('Discount') }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        <x-ui.money :amount="$globalDiscountAmount" :currency="$currentCurrency ?? 'IDR'" />
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                        {{ ui_phrase('Final Amount') }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-bold text-emerald-700 dark:text-emerald-300">
                                        <x-ui.money :amount="$finalAmount" :currency="$currentCurrency ?? 'IDR'" />
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="md:hidden space-y-3">
                        @forelse ($groupedItemsByDay as $dayKey => $dayItems)
                            <div class="rounded-lg border border-indigo-200 bg-indigo-50/60 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300">
                                {{ $dayKey === 'without_day' ? ui_phrase('Additional Services') : ui_phrase('Day') . ' ' . $dayKey }}
                            </div>
                            @foreach ($dayItems as $item)
                                @php
                                    $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                                    $paxType = strtolower((string) ($meta['pax_type'] ?? ''));
                                    $paxBadgeLabel = $paxType === 'adult' ? ui_phrase('Adult Publish Rate') : ($paxType === 'child' ? ui_phrase('Child Publish Rate') : '');
                                    $cleanDescription = trim((string) ($item->description ?? ''));
                                    $cleanDescription = preg_replace('/^\s*Day\s+\d+\s*-\s*/i', '', $cleanDescription) ?? $cleanDescription;
                                    $cleanDescription = preg_replace('/^\s*Without\s+Day\s*-\s*/i', '', $cleanDescription) ?? $cleanDescription;
                                    if ($cleanDescription === '') {
                                        $cleanDescription = '-';
                                    }
                                    $typePart = '';
                                    $detailPart = $cleanDescription;
                                    if (preg_match('/^\s*([^:]+):\s*(.+)$/', $cleanDescription, $typeMatches)) {
                                        $typePart = trim((string) ($typeMatches[1] ?? ''));
                                        $detailPart = trim((string) ($typeMatches[2] ?? ''));
                                    } elseif (str_contains($cleanDescription, ' - ')) {
                                        [$typePart, $detailPart] = explode(' - ', $cleanDescription, 2);
                                        $typePart = trim((string) $typePart);
                                        $detailPart = trim((string) $detailPart);
                                    }
                                    $typeColorClass = 'text-primary dark:text-teal-300';
                                @endphp
                                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <p class="text-sm text-gray-900 dark:text-gray-100">
                                            @if ($detailPart !== '')
                                                @if (trim((string) $typePart) !== '')
                                                    <span class="font-semibold {{ $typeColorClass }}">{{ trim((string) $typePart) }}</span>
                                                    <span class="text-gray-500 dark:text-gray-400">:</span>
                                                @endif
                                                <span class="font-normal text-gray-900 dark:text-gray-100">{{ trim((string) $detailPart) }}</span>
                                            @else
                                                <span class="font-normal text-gray-900 dark:text-gray-100">{{ $cleanDescription }}</span>
                                            @endif
                                        </p>
                                        @if ($paxBadgeLabel !== '')
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $paxType === 'child' ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">{{ $paxBadgeLabel }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                        <div>{{ ui_phrase('Contract Rate') }}</div><div class="text-right"><x-ui.money :amount="$item->contract_rate ?? 0" :currency="$currentCurrency ?? 'IDR'" /></div>
                                        <div>{{ ui_phrase('Markup') }}</div>
                                        <div class="text-right">
                                            @if (($item->markup_type ?? 'fixed') === 'percent')
                                                {{ number_format($item->markup ?? 0, 2, ',', '.') }}%
                                            @else
                                                <x-ui.money :amount="$item->markup ?? 0" :currency="$currentCurrency ?? 'IDR'" />
                                            @endif
                                        </div>
                                        <div>{{ ui_phrase('Unit Price') }}</div><div class="text-right"><x-ui.money :amount="$item->unit_price ?? 0" :currency="$currentCurrency ?? 'IDR'" /></div>
                                        <div>{{ ui_phrase('Qty') }}</div><div class="text-right">{{ $item->qty }}</div>
                                        <div class="font-semibold">{{ ui_phrase('Total') }}</div><div class="text-right font-semibold"><x-ui.money :amount="$item->total ?? 0" :currency="$currentCurrency ?? 'IDR'" /></div>
                                    </div>
                                </div>
                            @endforeach
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ ui_phrase('No items available.') }}</div>
                        @endforelse

                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    <div class="font-semibold">{{ ui_phrase('Sub Total') }}</div>
                                    <div class="text-right font-semibold"><x-ui.money :amount="$subTotal" :currency="$currentCurrency ?? 'IDR'" /></div>
                                    <div class="font-semibold">{{ ui_phrase('Discount') }}</div>
                                    <div class="text-right font-semibold"><x-ui.money :amount="$globalDiscountAmount" :currency="$currentCurrency ?? 'IDR'" /></div>
                                    <div class="font-bold text-emerald-700 dark:text-emerald-300">{{ ui_phrase('Final Amount') }}</div>
                                    <div class="text-right font-bold text-emerald-700 dark:text-emerald-300"><x-ui.money :amount="$finalAmount" :currency="$currentCurrency ?? 'IDR'" /></div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="module-grid-side">
                @php
                    $normalizedQuotationStatus = \App\Models\Quotation::normalizeStatus((string) ($quotation->status ?? 'draft'));
                    $logicalQuotationStatus = \App\Support\Workflow\QuotationStatusNormalizer::normalize($normalizedQuotationStatus);
                    $pendingRevisionResponseCount = $quotation->customerResponses
                        ->where('requires_revision', true)
                        ->where('is_used_for_revision', false)
                        ->count();
                    $flowSteps = [
                        ['key' => 'validation', 'label' => ui_phrase('Validation'), 'icon' => 'fa-clipboard-check'],
                        ['key' => 'ready', 'label' => ui_phrase('Ready'), 'icon' => 'fa-paper-plane'],
                        ['key' => 'sent', 'label' => ui_phrase('Sent'), 'icon' => 'fa-envelope-circle-check'],
                        ['key' => 'revision', 'label' => ui_phrase('Revision'), 'icon' => 'fa-code-branch'],
                        ['key' => 'approved', 'label' => ui_phrase('Approved'), 'icon' => 'fa-circle-check'],
                        ['key' => 'completed', 'label' => ui_phrase('Done'), 'icon' => 'fa-flag-checkered'],
                    ];
                    if ($bookingsModuleEnabled ?? false) {
                        array_splice($flowSteps, 5, 0, [[
                            'key' => 'booking',
                            'label' => ui_phrase('Booking'),
                            'icon' => 'fa-suitcase-rolling',
                        ]]);
                    }
                    $currentFlowKey = match (true) {
                        in_array($logicalQuotationStatus, ['ready_to_send'], true) => 'ready',
                        in_array($logicalQuotationStatus, ['sent', 'pending'], true) => 'sent',
                        in_array($logicalQuotationStatus, ['revision_requested', 'under_revision', 'need_revalidation'], true) || $pendingRevisionResponseCount > 0 => 'revision',
                        in_array($logicalQuotationStatus, ['approved', 'customer_approved'], true) => 'approved',
                        in_array($logicalQuotationStatus, ['converted_to_booking', 'booking_in_progress', 'booking_issue', 'invoiced', 'waiting_payment', 'in_operation', 'operation_adjustment', 'finalized'], true) => (($bookingsModuleEnabled ?? false) ? 'booking' : 'approved'),
                        $logicalQuotationStatus === 'completed' => 'completed',
                        default => 'validation',
                    };
                    $currentFlowIndex = collect($flowSteps)->search(fn (array $step): bool => $step['key'] === $currentFlowKey);
                    $currentFlowIndex = $currentFlowIndex === false ? 0 : (int) $currentFlowIndex;
                    $validationPercent = (int) ($validationProgress['percent'] ?? (($validationProgress['is_complete'] ?? false) ? 100 : 0));
                @endphp

                <div class="app-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Quotation Status Summary') }}</h3>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('Compact flow based on current quotation status.') }}</p>
                        </div>
                        <x-ui.status-badge :status="$displayQuotationStatus" size="xs" />
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-1.5 text-[11px] sm:grid-cols-3">
                        @foreach ($flowSteps as $index => $step)
                            @php
                                $isDone = $index < $currentFlowIndex;
                                $isActive = $index === $currentFlowIndex;
                                $stepClass = $isActive
                                    ? 'border-primary/40 bg-primary/10 text-primary dark:border-teal-700 dark:bg-teal-900/20 dark:text-teal-200'
                                    : ($isDone
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300'
                                        : 'border-gray-200 bg-white text-gray-500 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-400');
                            @endphp
                            <div class="inline-flex min-h-8 items-center gap-1.5 rounded-md border px-2 py-1 font-semibold {{ $stepClass }}">
                                <i class="fa-solid {{ $isDone ? 'fa-check' : $step['icon'] }} w-3.5 text-center"></i>
                                <span class="truncate">{{ $step['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <dl class="mt-4 grid grid-cols-3 gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                        <div class="rounded-md border border-gray-200 px-2 py-1.5 dark:border-gray-700">
                            <dt>{{ ui_phrase('Validation') }}</dt>
                            <dd class="mt-0.5 font-semibold text-gray-900 dark:text-gray-100">{{ $validationPercent }}%</dd>
                        </div>
                        <div class="rounded-md border border-gray-200 px-2 py-1.5 dark:border-gray-700">
                            <dt>{{ ui_phrase('Revision') }}</dt>
                            <dd class="mt-0.5 font-semibold text-gray-900 dark:text-gray-100">{{ $pendingRevisionResponseCount }}</dd>
                        </div>
                        <div class="rounded-md border border-gray-200 px-2 py-1.5 dark:border-gray-700">
                            <dt>{{ ui_phrase('Next') }}</dt>
                            <dd class="mt-0.5 truncate font-semibold text-gray-900 dark:text-gray-100">{{ \Illuminate\Support\Str::headline((string) ($quotation->next_action ?? '-')) }}</dd>
                        </div>
                    </dl>
                </div>

                @if (($revisionHistory ?? collect())->isNotEmpty())
                    <div class="app-card p-6">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Revision History') }}</h3>
                        <ul class="mt-3 max-h-60 space-y-2 overflow-y-auto pr-1 text-xs text-gray-700 dark:text-gray-200">
                            @foreach (collect($revisionHistory)->sortByDesc(fn ($row) => (int) ($row->revision_number ?? 1)) as $revisionRow)
                                @php
                                    $isCurrentRevision = (int) $revisionRow->id === (int) $quotation->id
                                        && (int) ($revisionRow->revision_number ?? 1) === (int) ($quotation->revision_number ?? 1);
                                    $linkedResponse = $revisionRow->getAttribute('revision_customer_response');
                                    $revisionModalName = 'quotation-revision-detail-' . (int) ($revisionRow->revision_number ?? 1);
                                @endphp
                                <li class="rounded-md border px-3 py-2 {{ $isCurrentRevision ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-700 dark:bg-emerald-900/20' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/30' }}">
                                    <button
                                        type="button"
                                        class="block w-full text-left"
                                        x-data
                                        x-on:click.prevent="$dispatch('open-modal', '{{ $revisionModalName }}')"
                                    >
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-semibold text-primary dark:text-teal-300">
                                                {{ $revisionRow->getAttribute('revision_label') ?? ($revisionRow->quotation_number ?? '-') }}
                                            </span>
                                            <x-ui.status-badge :status="(string) ($revisionRow->status ?? 'draft')" size="xs" />
                                        </div>
                                        <div class="mt-1 flex items-center justify-between gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                                            <span class="truncate">{{ $revisionRow->getAttribute('revision_trigger_label') ?? ui_phrase('Original quotation') }}</span>
                                            <span class="shrink-0"><x-local-time :value="$revisionRow->getAttribute('revision_started_at') ?? $revisionRow->created_at" /></span>
                                        </div>
                                        <div class="mt-1 text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                            {{ $revisionRow->getAttribute('revision_progress_text') ?? '-' }}
                                        </div>
                                    </button>
                                    <x-modal name="{{ $revisionModalName }}" focusable maxWidth="2xl">
                                    <div class="p-6">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $revisionRow->getAttribute('revision_label') ?? ui_phrase('Revision Detail') }}
                                                </h3>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $revisionRow->quotation_number ?? '-' }}</p>
                                            </div>
                                            <button type="button" class="btn-ghost px-2 py-1 text-xs" x-data x-on:click.prevent="$dispatch('close-modal', '{{ $revisionModalName }}')">{{ ui_phrase('Close') }}</button>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 gap-3 text-xs text-gray-700 dark:text-gray-200 sm:grid-cols-2">
                                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}</div>
                                                <div class="mt-1 font-semibold">{{ $revisionRow->getAttribute('revision_status_label') ?? '-' }}</div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Trigger') }}</div>
                                                <div class="mt-1 font-semibold">{{ $revisionRow->getAttribute('revision_trigger_label') ?? ui_phrase('Original quotation') }}</div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Items') }}</div>
                                                <div class="mt-1 font-semibold">{{ $revisionRow->getAttribute('revision_changed_summary') ?? '-' }}</div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Validation Progress') }}</div>
                                                <div class="mt-1 font-semibold">{{ $revisionRow->getAttribute('revision_progress_text') ?? '-' }}</div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Started By') }}</div>
                                                <div class="mt-1 font-semibold">{{ $revisionRow->getAttribute('revision_started_by_name') ?? '-' }}</div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Started At') }}</div>
                                                <div class="mt-1 font-semibold"><x-local-time :value="$revisionRow->getAttribute('revision_started_at') ?? $revisionRow->created_at" /></div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Finished By') }}</div>
                                                <div class="mt-1 font-semibold">{{ $revisionRow->getAttribute('revision_finished_by_name') ?? '-' }}</div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                                <div class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Finished At') }}</div>
                                                <div class="mt-1 font-semibold"><x-local-time :value="$revisionRow->getAttribute('revision_finished_at') ?? $revisionRow->updated_at" /></div>
                                            </div>
                                        </div>

                                        @if ($linkedResponse)
                                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                                                <div class="font-semibold">{{ ui_phrase('Customer Response') }}: {{ \Illuminate\Support\Str::headline((string) ($linkedResponse->response_status ?? '-')) }}</div>
                                                @if (!empty($linkedResponse->response_note))
                                                    <div class="mt-1">{{ $linkedResponse->response_note }}</div>
                                                @endif
                                            </div>
                                        @elseif ((int) ($revisionRow->revision_number ?? 1) <= 1)
                                            <div class="mt-4 rounded-lg border border-gray-200 p-3 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                                {{ ui_phrase('Original quotation') }}
                                            </div>
                                        @endif
                                    </div>
                                    </x-modal>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $pendingCustomerRevisionResponses = $quotation->customerResponses
                        ->where('requires_revision', true)
                        ->where('is_used_for_revision', false);
                @endphp
                @if ($pendingCustomerRevisionResponses->isNotEmpty())
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                        <div class="font-semibold">{{ ui_phrase('Customer requested changes. Please review before revising quotation.') }}</div>
                    </div>
                @endif

                <div class="app-card p-6">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Follow-up History') }}</h3>
                    <div class="mt-3 max-h-60 space-y-2 overflow-y-auto pr-1 text-xs text-gray-700 dark:text-gray-200">
                        @forelse (($followUpHistory ?? collect()) as $followUp)
                            <div class="rounded-md border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900/30">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold text-primary dark:text-teal-300">{{ $followUp->getAttribute('history_title') ?? '-' }}</div>
                                        <div class="mt-0.5 truncate text-[11px] text-gray-500 dark:text-gray-400">{{ $followUp->getAttribute('history_channel_label') ?? '-' }}</div>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ (string) ($followUp->getAttribute('history_type_key') ?? '') === 'quotation_sent' ? 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}">
                                        {{ $followUp->getAttribute('history_kind_label') ?? ui_phrase('Manual') }}
                                    </span>
                                </div>
                                @if (!empty($followUp->getAttribute('history_note')))
                                    <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $followUp->getAttribute('history_note') }}</p>
                                @endif
                                <div class="mt-1 flex items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                    <span class="shrink-0"><x-local-time :value="$followUp->getAttribute('history_effective_at') ?? $followUp->follow_up_at" /></span>
                                    <span class="truncate">{{ ui_phrase('By') }}: {{ $followUp->getAttribute('history_actor_name') ?? ui_user_name($followUp->creator) }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                    <span class="truncate">
                                        {{ ui_phrase('Next Follow-up') }}:
                                        @if ($followUp->next_follow_up_at)
                                            <x-local-time :value="$followUp->next_follow_up_at" />
                                        @else
                                            -
                                        @endif
                                    </span>
                                    @php
                                        $handlerName = trim((string) ($followUp->getAttribute('history_handler_name') ?? ''));
                                        $actorName = trim((string) ($followUp->getAttribute('history_actor_name') ?? ''));
                                    @endphp
                                    @if ($handlerName !== '' && strcasecmp($handlerName, $actorName) !== 0)
                                        <span class="truncate">{{ ui_phrase('Handled By') }}: {{ $handlerName }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('No follow-up recorded yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="app-card p-6">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Customer Response History') }}</h3>
                    <ul class="mt-3 max-h-60 space-y-2 overflow-y-auto pr-1 text-xs text-gray-700 dark:text-gray-200">
                        @forelse ($quotation->customerResponses as $response)
                            @php
                                $responseStatus = (string) ($response->response_status ?? '-');
                                $responseStatusLabel = $responseStatus !== '-' ? \Illuminate\Support\Str::headline($responseStatus) : '-';
                                $responseNote = trim((string) ($response->response_note ?? ''));
                            @endphp
                            <li class="rounded-md border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900/30">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="min-w-0 truncate font-semibold text-primary dark:text-teal-300">{{ $response->response_channel ?? '-' }}</span>
                                    <x-ui.status-badge :status="$responseStatus" size="xs" />
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                                    <span class="truncate">{{ $responseStatusLabel }}</span>
                                    <span class="shrink-0"><x-local-time :value="$response->response_at" /></span>
                                </div>
                                <div class="mt-1 flex items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                    <span class="min-w-0 truncate">
                                        {{ $responseNote !== '' ? \Illuminate\Support\Str::limit($responseNote, 72) : ui_phrase('No response note') }}
                                    </span>
                                    @if ($response->requires_revision)
                                        @if ($response->is_used_for_revision)
                                            <span class="shrink-0 font-semibold text-emerald-600 dark:text-emerald-300">{{ ui_phrase('Handled') }}</span>
                                        @else
                                            <span class="shrink-0 font-semibold text-amber-600 dark:text-amber-300">{{ ui_phrase('Pending') }}</span>
                                        @endif
                                    @else
                                        <span class="shrink-0">{{ ui_phrase('By') }}: {{ ui_user_name($response->creator) }}</span>
                                    @endif
                                </div>
                                @if ($response->requires_revision)
                                    <div class="mt-1 text-right text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('By') }}: {{ ui_user_name($response->creator) }}
                                    </div>
                                @endif
                            </li>
                        @empty
                            <li class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('No customer response recorded yet.') }}</li>
                        @endforelse
                    </ul>
                </div>

            </aside>
        </div>
    </div>

    <x-modal name="quotation-follow-up-modal" focusable maxWidth="2xl">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Add Follow-up') }}</h3>
                <button type="button" class="btn-ghost px-2 py-1 text-xs" x-data x-on:click.prevent="$dispatch('close-modal', 'quotation-follow-up-modal')">{{ ui_phrase('Close') }}</button>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Next follow-up is scheduled automatically for the next day.') }}</p>
            <form method="POST" action="{{ route('quotations.follow-ups.store', $quotation) }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Channel') }}</label>
                    <select name="channel" class="mt-1 w-full app-input" required>
                        @foreach (['WhatsApp', 'Email', 'WeChat', 'Line', 'Phone', 'Telegram', 'Manual', 'Other'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Follow-up At') }}</label>
                    <input type="datetime-local" name="follow_up_at" value="{{ now()->format('Y-m-d\\TH:i') }}" class="mt-1 w-full app-input">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Follow-up Note') }}</label>
                    <textarea name="follow_up_note" rows="4" class="mt-1 w-full app-input"></textarea>
                </div>
                <div class="flex justify-end gap-2 md:col-span-2">
                    <button type="button" class="btn-secondary-sm" x-data x-on:click.prevent="$dispatch('close-modal', 'quotation-follow-up-modal')">{{ ui_phrase('Cancel') }}</button>
                    <button type="submit" class="btn-primary-sm">{{ ui_phrase('Save Follow-up') }}</button>
                </div>
            </form>
        </div>
    </x-modal>

    <x-modal name="quotation-customer-response-modal" focusable maxWidth="2xl">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Add Customer Response') }}</h3>
                <button type="button" class="btn-ghost px-2 py-1 text-xs" x-data x-on:click.prevent="$dispatch('close-modal', 'quotation-customer-response-modal')">{{ ui_phrase('Close') }}</button>
            </div>
            <form method="POST" action="{{ route('quotations.customer-responses.store', $quotation) }}" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Response Channel') }}</label>
                    <select name="response_channel" class="mt-1 w-full app-input" required>
                        @foreach (['WhatsApp', 'Email', 'WeChat', 'Line', 'Phone', 'Telegram', 'Manual', 'Other'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Response Status') }}</label>
                    <select name="response_status" class="mt-1 w-full app-input" required>
                        <option value="revision_requested">{{ ui_phrase('Revision') }}</option>
                        <option value="approved">{{ ui_phrase('Approved') }}</option>
                        <option value="cancelled">{{ ui_phrase('Cancelled') }}</option>
                        <option value="rejected">{{ ui_phrase('Rejected') }}</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Response Note') }}</label>
                    <textarea name="response_note" rows="4" class="mt-1 w-full app-input"></textarea>
                </div>
                <div class="flex justify-end gap-2 md:col-span-2">
                    <button type="button" class="btn-secondary-sm" x-data x-on:click.prevent="$dispatch('close-modal', 'quotation-customer-response-modal')">{{ ui_phrase('Cancel') }}</button>
                    <button type="submit" class="btn-primary-sm">{{ ui_phrase('Save Customer Response') }}</button>
                </div>
            </form>
        </div>
    </x-modal>
@endsection
