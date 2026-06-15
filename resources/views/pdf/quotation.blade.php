<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        {!! $pdfFontFaceCss ?? '' !!}
        body { font-family: {!! $pdfFontFamilyCss ?? "'DejaVu Sans', Arial, sans-serif" !!}; color: #111827; font-size: 12px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 12px; }
        .title { font-size: 20px; font-weight: 700; }
        .muted { color: #6b7280; }
        .meta-table { margin-top: 0; margin-bottom: 10px; table-layout: fixed; }
        .meta-table td { padding: 5px 8px; background: #f8fafc; }
        .meta-label { display: block; margin-bottom: 2px; color: #6b7280; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; }
        .meta-value { display: block; color: #111827; font-size: 11px; line-height: 1.35; }
        .meta-subvalue { display: block; margin-top: 2px; color: #6b7280; font-size: 10px; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #0f172a; color: #ffffff; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .right { text-align: right; }
        .total { font-weight: 700; }
        .group-row td { background: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .desc-type { font-weight: 700; color: #0f766e; }
        .desc-sep { color: #9ca3af; }
        .desc-detail { color: #111827; line-height: 1.45; }
        .desc-extra { margin-top: 4px; font-size: 10px; line-height: 1.45; color: #475569; }
        .desc-extra-label { font-weight: 700; color: #334155; }
        .desc-extra-value { color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">Quotation</div>
            <div class="muted">{{ ui_phrase('Number') }}: {{ $quotation->quotation_number }}</div>
        </div>
    </div>

    @php
        $groupedItemsByDay = $groupedItemsByDay ?? collect();
        $quotationInquiry = $quotation->inquiry;
        $quotationCustomer = $quotationInquiry?->customer;
        $quotationCustomerCode = trim((string) ($quotationCustomer?->code ?? ''));
        $quotationCustomerName = trim((string) ($quotationCustomer?->name ?? $quotationCustomer?->company_name ?? ''));
        $quotationCustomerDisplay = $quotationCustomerName !== ''
            ? (($quotationCustomerCode !== '' ? '(' . $quotationCustomerCode . ') ' : '') . $quotationCustomerName)
            : '-';
        $quotationCustomerContactParts = array_values(array_filter([
            trim((string) ($quotationCustomer?->email ?? '')),
            trim((string) ($quotationCustomer?->phone ?? '')),
            trim((string) ($quotationCustomer?->country ?? '')),
        ], static fn ($value) => $value !== ''));
        $quotationInquirySummaryParts = array_values(array_filter([
            trim((string) ($quotationInquiry?->source ?? '')) !== ''
                ? ui_phrase('Source') . ': ' . \Illuminate\Support\Str::headline((string) $quotationInquiry->source)
                : '',
            $quotationInquiry?->deadline
                ? ui_phrase('Deadline') . ': ' . \App\Support\DateTimeDisplay::date($quotationInquiry->deadline)
                : '',
        ], static fn ($value) => $value !== ''));
        $quotationItinerary = $quotation->itinerary;
        $quotationItineraryName = trim((string) ($quotationItinerary?->title ?? ''));
        $quotationItineraryDuration = null;
        if ($quotationItinerary) {
            $durationDays = max(0, (int) ($quotationItinerary->duration_days ?? 0));
            $durationNights = max(0, (int) ($quotationItinerary->duration_nights ?? 0));
            if ($durationDays > 0) {
                $quotationItineraryDuration = $durationDays . 'D / ' . $durationNights . 'N';
            }
        }
        $quotationItinerarySummaryParts = array_values(array_filter([
            $quotationItineraryDuration,
            trim((string) ($quotationItinerary?->destination ?? '')) !== ''
                ? ui_phrase('Destination') . ': ' . $quotationItinerary->destination
                : '',
        ], static fn ($value) => $value !== ''));
        $revisionNumber = max(1, (int) ($quotation->revision_number ?? 1));
        $revisionLabel = $revisionNumber <= 1
            ? ui_phrase('Original') . ' / v1'
            : ui_phrase('Revision') . ' ' . $revisionNumber . ' / v' . $revisionNumber;
        $revisionSummaryParts = array_values(array_filter([
            $quotation->validity_date
                ? ui_phrase('Valid Until') . ': ' . \App\Support\DateTimeDisplay::date($quotation->validity_date)
                : '',
        ], static fn ($value) => $value !== ''));
        $serviceDateLabel = $quotation->service_date
            ? \App\Support\DateTimeDisplay::date($quotation->service_date)
            : '-';
        $paxAdult = (int) ($quotation->pax_adult ?? 0);
        $paxChild = (int) ($quotation->pax_child ?? 0);
        $paxSummary = ui_phrase('Adult') . ': ' . $paxAdult . ' | ' . ui_phrase('Child') . ': ' . $paxChild;
    @endphp

    <table class="meta-table">
        <tbody>
            <tr>
                <td style="width: 34%">
                    <span class="meta-label">{{ ui_phrase('Version') }}</span>
                    <span class="meta-value">{{ $revisionLabel }}</span>
                    <span class="meta-subvalue">{{ !empty($revisionSummaryParts) ? implode(' | ', $revisionSummaryParts) : '-' }}</span>
                </td>
                <td style="width: 33%">
                    <span class="meta-label">{{ ui_phrase('Service Date') }}</span>
                    <span class="meta-value">{{ $serviceDateLabel }}</span>
                    <span class="meta-subvalue">-</span>
                </td>
                <td style="width: 33%">
                    <span class="meta-label">{{ ui_phrase('Pax (Adult / Child)') }}</span>
                    <span class="meta-value">{{ $paxSummary }}</span>
                    <span class="meta-subvalue">-</span>
                </td>
            </tr>
            <tr>
                <td style="width: 34%">
                    <span class="meta-label">{{ ui_phrase('Customer') }}</span>
                    <span class="meta-value">{{ $quotationCustomerDisplay }}</span>
                    <span class="meta-subvalue">{{ !empty($quotationCustomerContactParts) ? implode(' | ', $quotationCustomerContactParts) : '-' }}</span>
                </td>
                <td style="width: 33%">
                    <span class="meta-label">{{ ui_phrase('Inquiry') }}</span>
                    <span class="meta-value">{{ $quotationInquiry?->inquiry_number ?? '-' }}</span>
                    <span class="meta-subvalue">{{ !empty($quotationInquirySummaryParts) ? implode(' | ', $quotationInquirySummaryParts) : '-' }}</span>
                </td>
                <td style="width: 33%">
                    <span class="meta-label">{{ ui_phrase('Itinerary') }}</span>
                    <span class="meta-value">{{ $quotationItineraryName !== '' ? $quotationItineraryName : '-' }}</span>
                    <span class="meta-subvalue">{{ !empty($quotationItinerarySummaryParts) ? implode(' | ', $quotationItinerarySummaryParts) : '-' }}</span>
                </td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 58%">{{ ui_phrase('Description') }}</th>
                <th style="width: 16%">{{ ui_phrase('Unit Price') }}</th>
                <th style="width: 10%">{{ ui_phrase('Qty') }}</th>
                <th style="width: 16%">{{ ui_phrase('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedItemsByDay as $dayKey => $dayItems)
                @php
                    $dayLabel = $dayKey === 'without_day'
                        ? ui_phrase('Additional Services')
                        : (ui_phrase('Day') . ' ' . $dayKey);
                @endphp
                <tr class="group-row">
                    <td colspan="4">{{ $dayLabel }}</td>
                </tr>
                @foreach ($dayItems as $item)
                    @php
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
                        $pdfMenuHighlights = trim((string) ($item->pdf_menu_highlights ?? ''));
                    @endphp
                    <tr>
                        <td>
                            @if ($detailPart !== '')
                                @if ($typePart !== '')
                                    <span class="desc-type">{{ $typePart }}</span>
                                    <span class="desc-sep">:</span>
                                @endif
                                <span class="desc-detail">{{ $detailPart }}</span>
                            @else
                                <span class="desc-detail">{{ $cleanDescription }}</span>
                            @endif
                            @if ($pdfMenuHighlights !== '')
                                <div class="desc-extra">
                                    <span class="desc-extra-label">{{ ui_phrase('Menu') }}:</span>
                                    <span class="desc-extra-value">{{ $pdfMenuHighlights }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="right"><x-money :amount="$item->unit_price ?? 0" currency="IDR" /></td>
                        <td class="right">{{ (int) ($item->qty ?? 0) }}</td>
                        <td class="right"><x-money :amount="$item->total ?? 0" currency="IDR" /></td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="4" class="muted">{{ ui_phrase('No items available.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $subTotalValue = (float) ($kpiSummary['sub_total'] ?? 0);
        $discountType = (string) ($kpiSummary['global_discount_type'] ?? $quotation->discount_type ?? '');
        $discountValue = (float) ($kpiSummary['global_discount_value'] ?? $quotation->discount_value ?? 0);
        $globalDiscountAmount = (float) ($kpiSummary['global_discount_amount'] ?? 0);
        $finalAmountValue = (float) ($kpiSummary['final_amount'] ?? 0);
        $hasTotals = $quotation->items->isNotEmpty() || $subTotalValue > 0 || $globalDiscountAmount > 0 || $finalAmountValue > 0;
    @endphp
    @if ($hasTotals)
        <table style="margin-top: 12px;">
            <tbody>
                @if ($quotation->items->isNotEmpty() || $subTotalValue > 0)
                    <tr>
                        <td class="right"><strong>Sub Total</strong></td>
                        <td class="right" style="width: 30%"><x-money :amount="$subTotalValue" currency="IDR" /></td>
                    </tr>
                @endif
                <tr>
                    <td class="right">
                        <strong>Global Discount</strong>
                        @if ($discountType === 'percent')
                            ({{ $discountValue }}%)
                        @elseif ($discountType === 'fixed')
                            (<x-money :amount="$discountValue" currency="IDR" />)
                        @endif
                    </td>
                    <td class="right">
                        <x-money :amount="$globalDiscountAmount" currency="IDR" />
                    </td>
                </tr>
                @if ($quotation->items->isNotEmpty() || $finalAmountValue > 0)
                    <tr>
                        <td class="right total">Final Amount</td>
                        <td class="right total"><x-money :amount="$finalAmountValue" currency="IDR" /></td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif
</body>
</html>
