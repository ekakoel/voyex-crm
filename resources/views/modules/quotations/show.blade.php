@extends('layouts.master')

@section('page_title', ui_phrase('show page title'))
@section('page_subtitle', ui_phrase('show page subtitle'))
@section('page_actions')
    @if (in_array(($quotation->status ?? ''), ['approved', 'final'], true))
        <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener" class="btn-outline">{{ ui_phrase('Preview PDF') }}</a>
    @endif
    <a href="{{ route('quotations.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    @php
        $subTotal = (float) ($kpiSummary['sub_total'] ?? 0);
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
                        <div class="mb-3">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Number') }}</p>
                            <h2 class="text-lg pb-3 font-semibold text-gray-900 dark:text-gray-100">{{ $quotation->quotation_number }}</h2>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Itinerary') }}:</dt>
                                <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->itinerary?->title ?? '-' }}</dd>
                            </div>
                            
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
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Service Date') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $quotation->service_date?->format('Y-m-d') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Pax (Adult / Child)') }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ (int) ($quotation->pax_adult ?? 0) }} / {{ (int) ($quotation->pax_child ?? 0) }}</dd>
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
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Sub Total') }}</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100"><x-money :amount="$subTotal" :currency="$currentCurrency ?? 'IDR'" /></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Discount') }}</p>
                            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100"><x-money :amount="$globalDiscountAmount" :currency="$currentCurrency ?? 'IDR'" /></p>
                        </div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-700 dark:bg-emerald-900/20">
                            <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ ui_phrase('Final Amount') }}</p>
                            <p class="mt-1 text-lg font-semibold text-emerald-800 dark:text-emerald-200"><x-money :amount="$finalAmount" :currency="$currentCurrency ?? 'IDR'" /></p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Items') }}</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('items count', ['count' => $quotation->items->count()]) }}</span>
                    </div>
                    @php
                        $groupedItemsByDay = $quotation->items
                            ->sort(function ($left, $right) {
                                $leftDay = (int) ($left->day_number ?? 0);
                                $rightDay = (int) ($right->day_number ?? 0);

                                $leftDayRank = $leftDay > 0 ? 0 : 1;
                                $rightDayRank = $rightDay > 0 ? 0 : 1;
                                if ($leftDayRank !== $rightDayRank) {
                                    return $leftDayRank <=> $rightDayRank;
                                }

                                if ($leftDayRank === 0 && $leftDay !== $rightDay) {
                                    return $leftDay <=> $rightDay;
                                }

                                $leftMeta = is_array($left->serviceable_meta ?? null) ? $left->serviceable_meta : [];
                                $rightMeta = is_array($right->serviceable_meta ?? null) ? $right->serviceable_meta : [];

                                $leftVisitOrder = isset($leftMeta['visit_order']) && is_numeric($leftMeta['visit_order'])
                                    ? (int) $leftMeta['visit_order']
                                    : PHP_INT_MAX;
                                $rightVisitOrder = isset($rightMeta['visit_order']) && is_numeric($rightMeta['visit_order'])
                                    ? (int) $rightMeta['visit_order']
                                    : PHP_INT_MAX;
                                if ($leftVisitOrder !== $rightVisitOrder) {
                                    return $leftVisitOrder <=> $rightVisitOrder;
                                }

                                $normalizeTimeToMinutes = static function ($value): int {
                                    $time = trim((string) $value);
                                    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
                                        return PHP_INT_MAX;
                                    }

                                    return ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);
                                };
                                $leftStartMinutes = $normalizeTimeToMinutes($leftMeta['start_time'] ?? null);
                                $rightStartMinutes = $normalizeTimeToMinutes($rightMeta['start_time'] ?? null);
                                if ($leftStartMinutes !== $rightStartMinutes) {
                                    return $leftStartMinutes <=> $rightStartMinutes;
                                }

                                return (int) ($left->id ?? 0) <=> (int) ($right->id ?? 0);
                            })
                            ->groupBy(function ($item) {
                                $dayNumber = (int) ($item->day_number ?? 0);
                                return $dayNumber > 0 ? $dayNumber : 'without_day';
                            })
                            ->sortKeysUsing(function ($left, $right) {
                                if ($left === 'without_day') {
                                    return 1;
                                }
                                if ($right === 'without_day') {
                                    return -1;
                                }

                                return (int) $left <=> (int) $right;
                            });
                    @endphp

                    <div class="hidden md:block overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Description') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Contract Rate') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Markup') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Unit Price') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Qty') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($groupedItemsByDay as $dayKey => $dayItems)
                                    <tr>
                                        <td colspan="6" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700 bg-indigo-50/60 dark:bg-indigo-900/20 dark:text-indigo-300">
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
                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                                <x-money :amount="$item->contract_rate ?? 0" :currency="$currentCurrency ?? 'IDR'" />
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                                @if (($item->markup_type ?? 'fixed') === 'percent')
                                                    {{ number_format($item->markup ?? 0, 2, ',', '.') }}%
                                                @else
                                                    <x-money :amount="$item->markup ?? 0" :currency="$currentCurrency ?? 'IDR'" />
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200"><x-money :amount="$item->unit_price ?? 0" :currency="$currentCurrency ?? 'IDR'" /></td>
                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">{{ $item->qty }}</td>
                                            <td class="px-3 py-2 text-right font-medium text-gray-700 dark:text-gray-200"><x-money :amount="$item->total ?? 0" :currency="$currentCurrency ?? 'IDR'" /></td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No items available.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="border-t border-gray-200 dark:border-gray-700">
                                <tr>
                                    <td colspan="5" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('Sub Total') }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        <x-money :amount="$subTotal" :currency="$currentCurrency ?? 'IDR'" />
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('Discount') }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        <x-money :amount="$globalDiscountAmount" :currency="$currentCurrency ?? 'IDR'" />
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                        {{ ui_phrase('Final Amount') }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-bold text-emerald-700 dark:text-emerald-300">
                                        <x-money :amount="$finalAmount" :currency="$currentCurrency ?? 'IDR'" />
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
                                        <div>{{ ui_phrase('Contract Rate') }}</div><div class="text-right"><x-money :amount="$item->contract_rate ?? 0" :currency="$currentCurrency ?? 'IDR'" /></div>
                                        <div>{{ ui_phrase('Markup') }}</div>
                                        <div class="text-right">
                                            @if (($item->markup_type ?? 'fixed') === 'percent')
                                                {{ number_format($item->markup ?? 0, 2, ',', '.') }}%
                                            @else
                                                <x-money :amount="$item->markup ?? 0" :currency="$currentCurrency ?? 'IDR'" />
                                            @endif
                                        </div>
                                        <div>{{ ui_phrase('Unit Price') }}</div><div class="text-right"><x-money :amount="$item->unit_price ?? 0" :currency="$currentCurrency ?? 'IDR'" /></div>
                                        <div>{{ ui_phrase('Qty') }}</div><div class="text-right">{{ $item->qty }}</div>
                                        <div class="font-semibold">{{ ui_phrase('Total') }}</div><div class="text-right font-semibold"><x-money :amount="$item->total ?? 0" :currency="$currentCurrency ?? 'IDR'" /></div>
                                    </div>
                                </div>
                            @endforeach
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ ui_phrase('No items available.') }}</div>
                        @endforelse

                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    <div class="font-semibold">{{ ui_phrase('Sub Total') }}</div>
                                    <div class="text-right font-semibold"><x-money :amount="$subTotal" :currency="$currentCurrency ?? 'IDR'" /></div>
                                    <div class="font-semibold">{{ ui_phrase('Discount') }}</div>
                                    <div class="text-right font-semibold"><x-money :amount="$globalDiscountAmount" :currency="$currentCurrency ?? 'IDR'" /></div>
                                    <div class="font-bold text-emerald-700 dark:text-emerald-300">{{ ui_phrase('Final Amount') }}</div>
                                    <div class="text-right font-bold text-emerald-700 dark:text-emerald-300"><x-money :amount="$finalAmount" :currency="$currentCurrency ?? 'IDR'" /></div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="module-grid-side">
                @php
                    $canAccessItineraryModule = auth()->user()?->can('module.itineraries.access');
                    $canEditLinkedItinerary = (bool) (auth()->user()?->can('update', $quotation->itinerary) ?? false);
                    $canEditQuotation = auth()->user()?->can('update', $quotation) && (($quotation->status ?? '') !== 'final');
                    $canValidateCurrentQuotation = (bool) ($canValidateQuotation ?? false);
                    $hasItineraryQuickActions = $quotation->itinerary && $canAccessItineraryModule;
                    $showQuickActions = $hasItineraryQuickActions || $canEditQuotation || $canValidateCurrentQuotation;
                    $quotationStatus = strtolower((string) ($quotation->status ?? 'draft'));
                    $isValidationComplete = (bool) ($validationProgress['is_complete'] ?? false);
                    $requiredApprovals = (int) ($approvalProgress['required_non_creator_approvals'] ?? 2);
                    $approvedCount = (int) ($approvalProgress['non_creator_approval_count'] ?? 0);
                    $isApprovedStage = in_array($quotationStatus, ['approved', 'final'], true);
                    $isFinalStage = $quotationStatus === 'final';
                    $workflowSteps = [
                        [
                            'label' => ui_phrase('Itinerary Ready'),
                            'done' => (bool) $quotation->itinerary,
                            'desc' => ui_phrase('Itinerary selected and linked to this quotation.'),
                            'process_url' => $canEditQuotation ? route('quotations.edit', $quotation) : null,
                        ],
                        [
                            'label' => ui_phrase('Draft & Review'),
                            'done' => in_array($quotationStatus, ['approved', 'final'], true) || $isValidationComplete,
                            'desc' => ui_phrase('Review quotation items, quantities, and pricing before validation.'),
                            'process_url' => $canEditQuotation ? route('quotations.edit', $quotation) : null,
                        ],
                        [
                            'label' => ui_phrase('Validation Complete'),
                            'done' => $isValidationComplete,
                            'desc' => ui_phrase('All required quotation items have been validated.'),
                            'process_url' => $canValidateCurrentQuotation ? route('quotations.validate.show', $quotation) : null,
                        ],
                        [
                            'label' => ui_phrase('Approval Complete'),
                            'done' => $isApprovedStage,
                            'desc' => ui_phrase('Non-creator approvals reached required minimum.'),
                            'process_url' => '#quotation-approval-section',
                        ],
                        [
                            'label' => ui_phrase('Quotation Final'),
                            'done' => $isFinalStage,
                            'desc' => ui_phrase('Quotation is locked and ready for final operational usage.'),
                            'process_url' => '#quotation-approval-section',
                        ],
                    ];
                    $nextActionText = ui_phrase('Review quotation details and continue the process.');
                    if (! $quotation->itinerary) {
                        $nextActionText = ui_phrase('Link or create itinerary first before proceeding.');
                    } elseif ($quotationStatus === 'final') {
                        $nextActionText = ui_phrase('Quotation is already final. Only set pending if revision is needed.');
                    } elseif ($quotationStatus === 'approved') {
                        $nextActionText = ui_phrase('Creator should set quotation to final when ready.');
                    } elseif (! $isValidationComplete) {
                        $nextActionText = ui_phrase('Complete quotation validation before requesting approval.');
                    } elseif ($approvedCount < $requiredApprovals) {
                        $nextActionText = ui_phrase('Collect required non-creator approvals to move to approved status.');
                    } else {
                        $nextActionText = ui_phrase('Proceed with approval action according to your role.');
                    }
                @endphp

                <div class="app-card p-6 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Quotation Workflow Guide') }}</h3>
                        <p class="text-xs mb-3 text-gray-600 dark:text-gray-300">{{ ui_phrase('Understand current stage and required next action.') }}</p>
                    </div>
                    <ul class="space-y-2 pb-3">
                        @foreach ($workflowSteps as $index => $step)
                            <li class="flex items-start justify-between gap-2 rounded-md border px-2.5 py-2 text-xs {{ $step['done'] ? 'border-emerald-200 bg-emerald-50/70 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-200' : 'border-gray-200 bg-white text-gray-700 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-200' }}">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold {{ $step['done'] ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                                    @if ($step['done'])
                                        <i class="fa-solid fa-check"></i>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold">{{ $step['label'] }}</span>
                                    <span class="mt-0.5 block text-[11px] opacity-85">{{ $step['desc'] }}</span>
                                </span>
                                @if (!($step['done'] ?? false) && filled($step['process_url'] ?? null))
                                    <a href="{{ $step['process_url'] }}" class="btn-outline-sm shrink-0 !px-2 !py-1 !text-[11px]">
                                        {{ ui_phrase('Process') }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="rounded border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                        <span class="font-semibold">{{ ui_phrase('What to do now') }}:</span>
                        <span class="ml-1">{{ $nextActionText }}</span>
                    </div>
                </div>

                @if ($showQuickActions)
                    <div class="app-card p-6">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Quick Actions') }}</h3>
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @if ($hasItineraryQuickActions)
                                @if ($canEditLinkedItinerary)
                                    <a
                                        href="{{ route('itineraries.edit', $quotation->itinerary) }}"
                                        class="btn-primary w-full justify-center sm:col-span-2"
                                    >
                                        {{ ui_phrase('Edit Itinerary') }}
                                    </a>
                                @endif
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
                            @endif
                            @if ($canEditQuotation)
                                <a
                                    href="{{ route('quotations.edit', $quotation) }}"
                                    class="btn-secondary w-full justify-center"
                                >
                                    {{ ui_phrase('Edit Quotation') }}
                                </a>
                            @endif
                            @if ($canValidateCurrentQuotation)
                                <a
                                    href="{{ route('quotations.validate.show', $quotation) }}"
                                    class="btn-outline w-full justify-center"
                                >
                                    {{ ui_phrase('Validate Quotation') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

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

                <div class="app-card p-6 space-y-4" id="quotation-approval-section">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Activity Timeline') }}</h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ ui_phrase('detailed audit log') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>

                @include('partials._quotation-comments', ['quotation' => $quotation])

                <div class="app-card p-6 space-y-4">
                    <div class="flex mb-3 items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Validation Progress') }}</h3>
                        <x-status-badge :status="(string) ($validationProgress['status'] ?? 'pending')" size="xs" />
                    </div>

                    <div class="space-y-2 pb-3 text-xs text-gray-700 dark:text-gray-200">
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

                </div>

                <div class="app-card p-6 space-y-4">
                    <div class="flex mb-3 items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Approval') }}</h3>
                        <x-status-badge :status="$quotation->status ?? '-'" size="xs" />
                    </div>

                    <dl class="grid grid-cols-1 gap-2 text-xs text-gray-700 dark:text-gray-200">
                        @if (in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Approved by') }}</dt>
                                <dd class="font-medium text-right">{{ $quotation->approvedBy?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Approved at') }}</dt>
                                <dd class="font-medium text-right"><x-local-time :value="$quotation->approved_at" /></dd>
                            </div>
                        @endif
                    </dl>

                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Approval Requirements') }}</div>
                        <div class="grid grid-cols-1 gap-2">
                            <div class="flex items-center justify-between rounded-md border px-3 py-2 text-xs {{ ($approvalProgress['is_ready'] ?? false) ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300' }}">
                                <span class="inline-flex items-center gap-2">
                                    <span class="inline-flex rounded-full border border-current px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">{{ ui_phrase('Rule') }}</span>
                                    <span>{{ ui_phrase('minimum two non creator') }}</span>
                                </span>
                                <span class="font-semibold">{{ (int) ($approvalProgress['non_creator_approval_count'] ?? 0) }}/{{ (int) ($approvalProgress['required_non_creator_approvals'] ?? 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md border px-3 py-2 text-xs {{ ((int) ($approvalProgress['remaining_non_creator_approvals'] ?? 0) === 0) ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300' : 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300' }}">
                                <span>{{ ui_phrase('Remaining approvals') }}</span>
                                <span class="font-semibold">{{ (int) ($approvalProgress['remaining_non_creator_approvals'] ?? 0) }}</span>
                            </div>
                        </div>
                        @if (!empty($approvalProgress['missing_labels']))
                            <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                                {{ ui_phrase('waiting for', ['names' => implode(', ', $approvalProgress['missing_labels'])]) }}
                            </p>
                        @endif
                    </div>

                    @if ($quotation->approvals->isNotEmpty())
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Approval Log') }}</div>
                            <ul class="mt-2 space-y-1.5 text-xs text-gray-700 dark:text-gray-200">
                                @foreach ($quotation->approvals as $approval)
                                    <li class="flex items-start justify-between gap-2">
                                        <span class="min-w-0">
                                            {{ ucfirst((string) $approval->approval_role) }} - <x-masked-user-name :user="$approval->user" />
                                        </span>
                                        @if ($approval->approved_at)
                                            <span class="shrink-0 text-gray-500 dark:text-gray-400"><x-local-time :value="$approval->approved_at" /></span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($quotation->approval_note))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                            <div class="font-semibold">{{ ui_phrase('Validation Note') }}</div>
                            <p class="mt-1 leading-relaxed">{{ $quotation->approval_note }}</p>
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
                            <div class="flex flex-wrap items-center gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
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
                                        <button type="button" class="btn-danger-sm" x-data x-on:click.prevent="$dispatch('open-modal', 'show-reject-modal')">{{ ui_phrase('Reject') }}</button>
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
                        <x-modal name="show-reject-modal" :show="$errors->has('approval_note')" focusable maxWidth="lg">
                            <div class="w-full rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Reject Quotation') }}</h3>
                                    <button type="button" class="btn-ghost px-2 py-1 text-xs" x-data x-on:click.prevent="$dispatch('close-modal', 'show-reject-modal')">{{ ui_phrase('Close') }}</button>
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
                                        <button type="button" class="btn-secondary-sm" x-data x-on:click.prevent="$dispatch('close-modal', 'show-reject-modal')">{{ ui_phrase('Cancel') }}</button>
                                        <button type="submit" class="btn-danger-sm">{{ ui_phrase('Confirm Reject') }}</button>
                                    </div>
                                </form>
                            </div>
                        </x-modal>
                    @endif
                </div>

            </aside>
        </div>
    </div>
@endsection
