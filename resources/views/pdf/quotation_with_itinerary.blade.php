<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 11px; line-height: 1.45; }
        .header { margin-bottom: 14px; }
        .title { font-size: 22px; font-weight: 700; letter-spacing: .2px; color: #111827; }
        .subtitle { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .chip { display: inline-block; margin-right: 6px; margin-top: 6px; border: 1px solid #d1d5db; border-radius: 999px; padding: 3px 8px; font-size: 10px; color: #374151; }
        .panel { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; }
        .panel-title { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #4b5563; font-weight: 700; margin-bottom: 6px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        .info-label { color: #6b7280; width: 120px; }
        .day-title { font-size: 13px; font-weight: 700; color: #111827; }
        .day-time { font-size: 10px; color: #6b7280; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th, table.items td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
        table.items th { background: #f9fafb; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #374151; }
        tr.highlight-item td { background: #fffbeb; border-color: #fcd34d; }
        tr.highlight-item td:first-child { border-left: 3px solid #f59e0b; }
        .thumb-box { width: 100%; aspect-ratio: 4 / 3; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden; background: #ffffff; }
        .thumb-box img { width: 100%; height: 180px; object-fit: cover; display: block; }
        .muted { color: #6b7280; }
        .richtext { line-height: 1.45; color: #6b7280; }
        .richtext p { margin: 0 0 4px; }
        .richtext ul, .richtext ol { margin: 2px 0 4px 16px; }
        .richtext ul { list-style: disc; }
        .richtext ol { list-style: decimal; }
        .richtext blockquote { border-left: 2px solid #94a3b8; padding-left: 6px; margin: 2px 0; color: #475569; }
        .transport-box { margin-top: 8px; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; background: #f8fafc; }
        .transport-title { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #334155; font-weight: 700; margin-bottom: 6px; }
        table.transport-table { width: 100%; border-collapse: collapse; }
        table.transport-table td { vertical-align: top; padding: 4px 6px; }
        .transport-thumb { width: 190px; }
        .transport-thumb .thumb-box img { height: 130px; }
        .transport-detail { font-size: 10px; color: #374151; }
        .transport-detail strong { color: #111827; }
        .day-panel { page-break-before: always; }
        .day-panel.first { page-break-before: auto; }
        .itinerary-inc-exc { margin-top: 6px; width: 100%; border-collapse: collapse; }
        .itinerary-inc-exc td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
        .itinerary-inc-exc .inc { background: #ecfdf5; border-color: #a7f3d0; }
        .itinerary-inc-exc .exc { background: #fff1f2; border-color: #fecdd3; }
        .itinerary-inc-exc .title { display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 3px; color: #374151; }

        .quotation-page-break { page-break-before: always; }
        .q-header { display: flex; justify-content: space-between; margin-bottom: 16px; }
        .q-title { font-size: 20px; font-weight: 700; }
        .q-muted { color: #6b7280; }
        .q-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        table.q-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.q-table th, table.q-table td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        table.q-table th { background: #f9fafb; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .right { text-align: right; }
        .total { font-weight: 700; }
    </style>
</head>
<body>
    <div class="header">
        <span>{{ __('ITINERARY') }}</span><br>
        <div class="title">{{ $itinerary->title }}</div>
        <div class="subtitle">Generated on {{ \App\Support\DateTimeDisplay::datetime(now()) }}</div>
        <span>Duration: {{ $itinerary->duration_days."D" }}{{ $itinerary->duration_nights > 0 ? "/".$itinerary->duration_nights."N":"";  }}</span>
    </div>

    <div class="panel">
        <div class="panel-title">Overview</div>
        <table class="info-table">
            <tr>
                <td>
                    @php
                        $overview = \App\Support\SafeRichText::sanitize($itinerary->description);
                    @endphp
                    {!! $overview !== '' ? $overview : '-' !!}
                </td>
            </tr>
        </table>
    </div>

    @foreach ($scheduleByDay as $day)
        <div class="panel day-panel {{ $loop->first ? 'first' : '' }}">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="day-title">Day {{ $day['day'] }}</div>
                <div class="day-time">Start Tour: {{ $day['start_time'] }} | End Tour: {{ $day['end_time'] }}</div>
            </div>

            <table class="items">
                <thead>
                    <tr>
                        <th style="width: 5%;">{{ __('No') }}</th>
                        <th style="width: 20%;">{{ __('Time') }}</th>
                        <th style="width: 25%;">{{ __('Item') }}</th>
                        <th style="width: 15%;">{{ __('location') }}</th>
                        <th style="width: 35%;">{{ __('Image') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($day['items'] as $index => $item)
                        <tr class="{{ !empty($item['is_main_experience']) ? 'highlight-item' : '' }}">
                            <td class="center">{{ $index + 1 }}</td>
                            <td>
                                <strong>
                                    @if (($item['point_role'] ?? '') === 'start')
                                        {{ $item['point_type_label'] ?? 'Unknown' }}
                                    @elseif (($item['point_role'] ?? '') === 'end')
                                        {{ $item['point_type_label'] ?? 'Unknown' }}
                                    @else
                                        {{ $item['type'] }}
                                    @endif
                                </strong><br>
                                @if (($item['point_role'] ?? '') === 'start')
                                    {{ $item['start_time'] ?: '--:--' }}
                                @elseif (($item['point_role'] ?? '') === 'end')
                                    {{ $item['end_time'] ?: '--:--' }}
                                @else
                                    {{ $item['start_time'] }} - {{ $item['end_time'] }}
                                @endif
                            </td>
                            <td>
                                @if (strtolower((string) ($item['type'] ?? '')) === 'f&b')
                                    <div><strong>{{ $item['vendor_name'] ?? '-' }}</strong></div>
                                    <div><strong>{{ $item['name'] }}</strong></div>
                                    @if (!empty($item['is_main_experience']))
                                        <div>Main Experience</div>
                                    @endif
                                    @php
                                        $menuHighlights = \App\Support\SafeRichText::sanitize((string) ($item['menu_highlights'] ?? ''));
                                    @endphp
                                    <div class="richtext"><strong>Menu Highlights:</strong></div>
                                    <div class="richtext">{!! $menuHighlights !== '' ? $menuHighlights : '-' !!}</div>
                                @else
                                    <div>
                                        <strong>{{ $item['name'] }}</strong><br>
                                        @if (!empty($item['is_main_experience']))
                                            <span>{{ __('Main Experience') }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if (strtolower((string) ($item['type'] ?? '')) === 'activity')
                                    @php
                                        $activityIncludeText = \App\Support\SafeRichText::plainText($item['includes'] ?? null);
                                        $activityExcludeText = \App\Support\SafeRichText::plainText($item['excludes'] ?? null);
                                        $activityIncludeHtml = \App\Support\SafeRichText::sanitize((string) ($item['includes'] ?? ''));
                                        $activityExcludeHtml = \App\Support\SafeRichText::sanitize((string) ($item['excludes'] ?? ''));
                                    @endphp
                                    @if (filled($activityIncludeText))
                                        <div class="richtext"><strong>Includes:</strong></div>
                                        <div class="richtext">{!! $activityIncludeHtml !!}</div>
                                    @endif
                                    @if (filled($activityExcludeText))
                                        <div class="richtext"><strong>Excludes:</strong></div>
                                        <div class="richtext">{!! $activityExcludeHtml !!}</div>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $item['location'] }}</td>
                            <td>
                                @if (!empty($item['thumbnail_data_uri']))
                                    <div class="thumb-box">
                                        <img src="{{ $item['thumbnail_data_uri'] }}" alt="Thumbnail">
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">No schedule item for this day.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @php
                $dayTransport = $day['transport_unit'] ?? null;
            @endphp
            <div class="transport-box">
                <div class="transport-title">Transport Unit Day {{ $day['day'] }}</div>
                <table class="transport-table">
                    <tr>
                        <td class="transport-thumb">
                            @if (!empty($dayTransport['thumbnail_data_uri']))
                                <div class="thumb-box">
                                    <img src="{{ $dayTransport['thumbnail_data_uri'] }}" alt="Transport Unit Thumbnail">
                                </div>
                            @else
                                <span class="muted">{{ __('No transport image.') }}</span>
                            @endif
                        </td>
                        <td class="transport-detail">
                            @if (empty($dayTransport['assigned']))
                                <div class="muted">No transport unit assigned for this day.</div>
                            @else
                                <div><strong>Unit:</strong> {{ $dayTransport['unit_name'] ?? '-' }}</div>
                                <div><strong>Transport:</strong> {{ $dayTransport['transport_name'] ?? '-' }} ({{ $dayTransport['transport_type'] ?? '-' }})</div>
                                <div><strong>Vehicle:</strong> {{ $dayTransport['brand_model'] ?? '-' }}</div>
                                <div><strong>Capacity:</strong> Seat {{ $dayTransport['seat_capacity'] ?? '-' }} | Luggage {{ $dayTransport['luggage_capacity'] ?? '-' }}</div>
                                <div><strong>Driver:</strong> {{ !empty($dayTransport['with_driver']) ? 'With driver' : 'Without driver' }} | <strong>AC:</strong> {{ !empty($dayTransport['air_conditioned']) ? 'Yes' : 'No' }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach

    @php
        $itineraryIncludeText = \App\Support\SafeRichText::plainText($itinerary->itinerary_include ?? null);
        $itineraryExcludeText = \App\Support\SafeRichText::plainText($itinerary->itinerary_exclude ?? null);
        $itineraryIncludeHtml = \App\Support\SafeRichText::sanitize((string) ($itinerary->itinerary_include ?? ''));
        $itineraryExcludeHtml = \App\Support\SafeRichText::sanitize((string) ($itinerary->itinerary_exclude ?? ''));
    @endphp
    @if (filled($itineraryIncludeText) || filled($itineraryExcludeText))
        <div class="panel">
            <div class="panel-title">Itinerary Include & Exclude</div>
            <table class="itinerary-inc-exc">
                <tr>
                    @if (filled($itineraryIncludeText))
                        <td class="inc">
                            <span class="title">{{ __('Itinerary Include') }}</span>
                            <div class="richtext">{!! $itineraryIncludeHtml !!}</div>
                        </td>
                    @endif
                    @if (filled($itineraryExcludeText))
                        <td class="exc">
                            <span class="title">{{ __('Itinerary Exclude') }}</span>
                            <div class="richtext">{!! $itineraryExcludeHtml !!}</div>
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    @endif

    <div class="quotation-page-break"></div>

    <div class="q-header">
        <div>
            <div class="q-title">Quotation</div>
            <div class="q-muted">No: {{ $quotation->quotation_number }}</div>
        </div>
        <div class="q-muted">
            @if ($quotation->created_at)
                <div>Date: {{ \App\Support\DateTimeDisplay::date($quotation->created_at) }}</div>
            @endif
            @if ($quotation->validity_date)
                <div>Valid Until: {{ \App\Support\DateTimeDisplay::date($quotation->validity_date) }}</div>
            @endif
        </div>
    </div>

    @php
        $itineraryTitle = $quotation->itinerary?->title;
    @endphp
    @if ($itineraryTitle)
        <div class="q-card">
            <div><strong>Itinerary:</strong> {{ $itineraryTitle }}</div>
        </div>
    @endif

    <table class="q-table">
        <thead>
        <tr>
            <th style="width: 50%">{{ __('Description') }}</th>
            <th style="width: 10%">{{ __('Qty') }}</th>
            <th style="width: 20%">{{ __('Unit Price') }}</th>
            <th style="width: 20%">{{ __('Total') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($quotation->items as $item)
            @php
                $qty = max(0, (int) ($item->qty ?? 0));
                $lineTotal = (float) ($item->total ?? 0);
                $itemDiscount = (float) ($item->discount ?? 0);
                $displayUnitPrice = (float) ($item->unit_price ?? 0);
                if ($itemDiscount > 0) {
                    $displayUnitPrice = $qty > 0 ? ($lineTotal / $qty) : $lineTotal;
                }
            @endphp
            <tr>
                @php
                    $normalizedDescription = str_ireplace(['(Adult)', '(Child)'], '', (string) ($item->description ?? ''));
                    $normalizedDescription = trim(preg_replace('/\s+/', ' ', $normalizedDescription) ?? '');
                @endphp
                <td>{{ $normalizedDescription }}</td>
                <td class="right">{{ $qty }}</td>
                <td class="right"><x-money :amount="$displayUnitPrice" currency="IDR" /></td>
                <td class="right"><x-money :amount="$lineTotal" currency="IDR" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="q-muted">No items available.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @php
        $subTotalValue = $quotation->sub_total;
        $discountType = $quotation->discount_type;
        $discountValue = $quotation->discount_value;
        $finalAmountValue = $quotation->final_amount;
        $hasTotals = $subTotalValue !== null || $discountType || $discountValue !== null || $finalAmountValue !== null;
    @endphp
    @if ($hasTotals)
        @php
            $globalDiscountAmount = 0;
            if ($discountType === 'percent') {
                $globalDiscountAmount = (float) $subTotalValue * ((float) $discountValue / 100);
            } elseif ($discountType === 'fixed') {
                $globalDiscountAmount = (float) $discountValue;
            }
        @endphp
        <table class="q-table" style="margin-top: 12px;">
            <tbody>
            @if ($subTotalValue !== null)
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
            @if ($finalAmountValue !== null)
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
