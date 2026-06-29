<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ ui_phrase('Itinerary') }} {{ $pdfMeta['title'] ?? $itinerary->title }}</title>
    <style>
        {!! $pdfFontFaceCss ?? '' !!}
        html, body, table, tr, td, th, div, span, p, strong, em, ul, ol, li { font-family: {!! $pdfFontFamilyCss ?? "'DejaVu Sans', Arial, sans-serif" !!} !important; }
        body { color: #111827; font-size: 11px; line-height: 1.45; }
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .title-cell { display: table-cell; vertical-align: top; }
        .doc-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #2563eb; }
        .title { font-size: 22px; font-weight: 700; letter-spacing: .2px; color: #111827; line-height: 1.2; margin-top: 2px; }
        .subtitle { font-size: 10px; color: #6b7280; margin-top: 4px; }
        .meta-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 8px 0 10px; }
        .meta-table td { border: 1px solid #e5e7eb; background: #f8fafc; padding: 6px 8px; vertical-align: top; }
        .meta-label { display: block; margin-bottom: 2px; color: #6b7280; font-size: 9px; text-transform: uppercase; letter-spacing: .06em; }
        .meta-value { display: block; color: #111827; font-size: 11px; font-weight: 700; line-height: 1.35; }
        .summary-strip { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 8px 0 10px; }
        .summary-strip td { border: 1px solid #dbeafe; background: #eff6ff; padding: 6px 8px; vertical-align: top; }
        .summary-value { display: block; color: #1d4ed8; font-size: 13px; font-weight: 700; }
        .panel { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; }
        .panel-plain { border: none; border-radius: 0; padding: 0; background: transparent; }
        .panel-title { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: #4b5563; font-weight: 700; margin-bottom: 6px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        .info-label { color: #6b7280; width: 120px; }
        .day-header { background: #0f172a; color: #ffffff; padding: 7px 9px; border-radius: 4px; }
        .day-title { font-size: 13px; font-weight: 700; color: #ffffff; }
        .day-time { font-size: 10px; color: #6b7280; }
        .day-header .day-time { color: #cbd5e1; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th, table.items td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
        table.items th { background: #f1f5f9; font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #334155; }
        table.items { table-layout: fixed; }
        tr.highlight-item td { background: #fffbeb; border-color: #fcd34d; }
        tr.highlight-item td:first-child { border-left: 3px solid #f59e0b; }
        tr.connector-row td { background: #f8fafc; color: #475569; font-size: 10px; }
        tr.break-row td { background: #f3f4f6; color: #1f2937; font-size: 10px; }
        .connector-label, .break-label { font-weight: 700; text-transform: uppercase; letter-spacing: .04em; font-size: 9px; }
        .thumb-box { width: 100%; height: 64px; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden; background: #ffffff; }
        .thumb-box img { width: 100%; height: 64px; object-fit: cover; display: block; }
        .muted { color: #6b7280; }
        .richtext { line-height: 1.45; color: #6b7280; }
        .richtext p { margin: 0 0 4px; }
        .richtext ul, .richtext ol { margin: 2px 0 4px 16px; }
        .richtext ul { list-style: disc; }
        .richtext ol { list-style: decimal; }
        .richtext blockquote { border-left: 2px solid #94a3b8; padding-left: 6px; margin: 2px 0; color: #475569; }
        .highlight-badge {
            display: inline-block;
            margin-left: 6px;
            border: 1px solid #f59e0b;
            background: #fef3c7;
            color: #92400e;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            line-height: 1;
            padding: 2px 6px;
            text-transform: uppercase;
        }
        .type-badge { display: inline-block; border-radius: 999px; padding: 2px 7px; font-size: 9px; font-weight: 700; border: 1px solid #d1d5db; }
        .type-attraction { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .type-activity { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .type-fnb { background: #fffbeb; color: #b45309; border-color: #fcd34d; }
        .type-point { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
        .right { text-align: right; }
        .itinerary-inc-exc { margin-top: 6px; width: 100%; border-collapse: separate; border-spacing: 0; background: transparent; }
        .itinerary-inc-exc td { border: none; padding: 0 8px 0 0; vertical-align: top; background: transparent; }
        .itinerary-inc-exc td:last-child { padding-right: 0; }
        .itinerary-inc-exc .inc { background: transparent; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px; }
        .itinerary-inc-exc .exc { background: transparent; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px; }
        .itinerary-inc-exc .title, .additional-info-title { display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 3px; color: #374151; }
        .transport-box { margin-top: 8px; }
        .transport-head-block { display: table; width: 100%; page-break-inside: avoid; break-inside: avoid; page-break-before: auto; }
        .transport-title { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #334155; font-weight: 700; margin-bottom: 4px; }
        .transport-item { page-break-inside: avoid; break-inside: avoid; margin-top: 6px; border: 1px solid #cbd5e1; }
        table.transport-table { width: 100%; border-collapse: collapse; }
        table.transport-table td { vertical-align: top; padding: 3px 5px; }
        .transport-thumb { width: 140px; }
        .transport-thumb .thumb-box { height: 78px; }
        .transport-thumb .thumb-box img { height: 78px; }
        .transport-detail { font-size: 9.5px; color: #374151; line-height: 1.35; }
        .transport-detail strong { color: #111827; }
        .footer { margin-top: 14px; font-size: 10px; color: #6b7280; text-align: right; }
        .day-panel { page-break-before: always; }
        .day-panel.first { page-break-before: auto; }
    </style>
</head>
<body>
    @php
        $pdfMeta = $pdfMeta ?? [];
        $overviewHtml = trim((string) ($overviewHtml ?? ''));
        $tourHighlightsHtml = trim((string) ($tourHighlightsHtml ?? ''));
        $itineraryIncludeHtml = trim((string) ($itineraryIncludeHtml ?? ''));
        $itineraryExcludeHtml = trim((string) ($itineraryExcludeHtml ?? ''));
        $itineraryTermConditionsHtml = trim((string) ($itineraryTermConditionsHtml ?? ''));
        $renderTransportDetail = static function (array $dayTransport, int $transportIndex): string {
            return '<div><strong>' . e(ui_phrase('Transport')) . ' #' . ($transportIndex + 1) . '</strong></div>'
                . '<div><strong>' . e(ui_phrase('Unit')) . ':</strong> ' . e($dayTransport['unit_name'] ?? '-') . '</div>'
                . '<div><strong>' . e(ui_phrase('Transport')) . ':</strong> ' . e($dayTransport['transport_name'] ?? '-') . ' (' . e(ui_phrase((string) ($dayTransport['transport_type'] ?? '-'))) . ')</div>'
                . '<div><strong>' . e(ui_phrase('Vehicle')) . ':</strong> ' . e($dayTransport['brand_model'] ?? '-') . '</div>'
                . '<div><strong>' . e(ui_phrase('Capacity')) . ':</strong> ' . e(ui_phrase('Seat')) . ' ' . e($dayTransport['seat_capacity'] ?? '-') . ' | ' . e(ui_phrase('Luggage')) . ' ' . e($dayTransport['luggage_capacity'] ?? '-') . '</div>'
                . '<div><strong>' . e(ui_phrase('Driver')) . ':</strong> ' . e(!empty($dayTransport['with_driver']) ? ui_phrase('With driver') : ui_phrase('Without driver')) . ' | <strong>' . e(ui_phrase('AC')) . ':</strong> ' . e(!empty($dayTransport['air_conditioned']) ? ui_phrase('Yes') : ui_phrase('No')) . '</div>';
        };
    @endphp

    <div class="header">
        <div class="title-cell">
            <div class="doc-label">{{ ui_phrase('Itinerary') }}</div>
            <div class="title">{{ $pdfMeta['title'] ?? $itinerary->title }}</div>
            <div class="subtitle">{{ ui_phrase('Generated') }}: {{ $pdfMeta['generated_at'] ?? '-' }}</div>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
            <tr>
                <td style="width: 25%">
                    <span class="meta-label">{{ ui_phrase('Destination') }}</span>
                    <span class="meta-value">{{ $pdfMeta['destination'] ?? '-' }}</span>
                </td>
                <td style="width: 25%">
                    <span class="meta-label">{{ ui_phrase('Duration') }}</span>
                    <span class="meta-value">{{ $pdfMeta['duration'] ?? '-' }}</span>
                </td>
                <td style="width: 25%">
                    <span class="meta-label">{{ ui_phrase('Order Number') }}</span>
                    <span class="meta-value">{{ ($pdfMeta['order_number'] ?? '') !== '' ? $pdfMeta['order_number'] : '-' }}</span>
                </td>
                <td style="width: 25%">
                    <span class="meta-label">{{ ui_phrase('Generated') }}</span>
                    <span class="meta-value">{{ $pdfMeta['generated_at'] ?? '-' }}</span>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="summary-strip">
        <tr>
            <td>
                <span class="meta-label">{{ ui_phrase('Days') }}</span>
                <span class="summary-value">{{ (int) ($pdfMeta['total_days'] ?? 0) }}</span>
            </td>
            <td>
                <span class="meta-label">{{ ui_phrase('Timeline Items') }}</span>
                <span class="summary-value">{{ (int) ($pdfMeta['total_schedule_items'] ?? 0) }}</span>
            </td>
            <td>
                <span class="meta-label">{{ ui_phrase('Transport Units') }}</span>
                <span class="summary-value">{{ (int) ($pdfMeta['total_transport_units'] ?? 0) }}</span>
            </td>
        </tr>
    </table>

    @if ($overviewHtml !== '' || $tourHighlightsHtml !== '')
        <div class="panel panel-plain">
            @if ($overviewHtml !== '')
                <div class="panel-title">{{ ui_phrase('Overview') }}</div>
                <div class="richtext">{!! $overviewHtml !!}</div>
            @endif
            @if ($tourHighlightsHtml !== '')
                <div class="panel-title" style="margin-top: 8px;">{{ ui_phrase('Tour Highlights') }}</div>
                <div class="richtext">{!! $tourHighlightsHtml !!}</div>
            @endif
        </div>
    @endif

    <div class="panel-title">{{ ui_phrase('Schedule by Day') }}</div>

    @foreach ($scheduleByDay as $day)
        <div class="panel panel-plain day-panel {{ $loop->first ? 'first' : '' }}">
            <div class="day-header">
                <div class="day-title">{{ ui_phrase('Day') }} {{ $day['day'] }}</div>
                <div class="day-time">
                    {{ ui_phrase('Start Tour') }}: {{ $day['start_time'] }} |
                    {{ ui_phrase('End Tour') }}: {{ $day['end_time'] }} |
                    {{ ui_phrase('Break Time') }}:
                    {{ !empty($day['break_start_time']) && !empty($day['break_end_time']) ? $day['break_start_time'].' - '.$day['break_end_time'] : '-' }}
                </div>
            </div>

            <table class="items">
                <thead>
                    <tr>
                        <th style="width: 8%;">{{ ui_phrase('No') }}</th>
                        <th style="width: 18%;">{{ ui_phrase('Time') }}</th>
                        <th style="width: 37%;">{{ ui_phrase('Service / Details') }}</th>
                        <th style="width: 22%;">{{ ui_phrase('Location') }}</th>
                        <th style="width: 15%;">{{ ui_phrase('Photo') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $pdfRows = $day['pdf_rows'] ?? collect();
                        if (!($pdfRows instanceof \Illuminate\Support\Collection)) {
                            $pdfRows = collect($pdfRows);
                        }
                        $itemCounter = 0;
                    @endphp
                    @forelse ($pdfRows as $row)
                        @if (($row['row_type'] ?? 'item') === 'connector')
                            <tr class="connector-row">
                                <td></td>
                                <td colspan="4" style="text-align: left;">
                                    {{ ui_phrase(':minutes min', ['minutes' => (int) ($row['minutes'] ?? 0)]) }} - {{ ui_phrase('Estimated travel time to') }} {{ $row['to_name'] ?? '-' }}
                                </td>
                            </tr>
                        @elseif (($row['row_type'] ?? 'item') === 'break')
                            @php
                                $breakStart = (string) ($row['break_start_time'] ?? '--:--');
                                $breakEnd = (string) ($row['break_end_time'] ?? '--:--');
                                $breakDurationLabel = '-';
                                if (preg_match('/^\d{2}:\d{2}$/', $breakStart) && preg_match('/^\d{2}:\d{2}$/', $breakEnd)) {
                                    $breakStartMinutes = ((int) substr($breakStart, 0, 2) * 60) + (int) substr($breakStart, 3, 2);
                                    $breakEndMinutes = ((int) substr($breakEnd, 0, 2) * 60) + (int) substr($breakEnd, 3, 2);
                                    if ($breakEndMinutes >= $breakStartMinutes) {
                                        $breakDurationLabel = ui_phrase(':minutes min', ['minutes' => (int) ($breakEndMinutes - $breakStartMinutes)]);
                                    }
                                }
                            @endphp
                            <tr class="break-row">
                                <td></td>
                                <td colspan="4" style="text-align: left;">
                                    {{ $breakDurationLabel }} - {{ ui_phrase('Break Time') }} {{ $breakStart }} - {{ $breakEnd }}
                                </td>
                            </tr>
                        @else
                            @php
                                $item = $row['item'] ?? [];
                                $itemCounter++;
                            @endphp
                            <tr class="{{ !empty($item['is_main_experience']) ? 'highlight-item' : '' }}">
                                <td class="center">{{ $itemCounter }}</td>
                                <td>
                                    <strong>
                                        @if (($item['point_role'] ?? '') === 'start')
                                            {{ $item['point_type_label'] ?? ui_phrase('Unknown') }}
                                        @elseif (($item['point_role'] ?? '') === 'end')
                                            {{ $item['point_type_label'] ?? ui_phrase('Unknown') }}
                                        @else
                                            {{ ui_phrase((string) ($item['type'] ?? '')) }}
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
                                            <div>{{ ui_phrase('Main Experience') }}</div>
                                        @endif
                                        @php
                                            $menuHighlights = \App\Support\SafeRichText::sanitize((string) ($item['menu_highlights'] ?? ''));
                                        @endphp
                                        <div class="richtext"><strong>{{ ui_phrase('Menu Highlights') }}:</strong></div>
                                        <div class="richtext">{!! $menuHighlights !== '' ? $menuHighlights : '-' !!}</div>
                                    @else
                                        <div>
                                            @if (filled($item['vendor_name'] ?? ''))
                                                <strong>{{ $item['vendor_name'] }}</strong><br>
                                            @endif
                                            <strong>{{ $item['name'] }}</strong><br>
                                            @if (!empty($item['is_main_experience']))
                                                <span>{{ ui_phrase('Main Experience') }}</span>
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
                                            <div class="richtext"><strong>{{ ui_phrase('Inclusions') }}:</strong></div>
                                            <div class="richtext">{!! $activityIncludeHtml !!}</div>
                                        @endif
                                        @if (filled($activityExcludeText))
                                            <div class="richtext"><strong>{{ ui_phrase('Exclusions') }}:</strong></div>
                                            <div class="richtext">{!! $activityExcludeHtml !!}</div>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $item['location'] }}</td>
                                <td>
                                    @if (!empty($item['thumbnail_data_uri']))
                                        <div class="thumb-box">
                                            <img src="{{ $item['thumbnail_data_uri'] }}" alt="{{ ui_phrase('Thumbnail') }}">
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="muted">{{ ui_phrase('No schedule item for this day.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @php
                $dayTransports = $day['transport_units'] ?? [];
                if (!is_array($dayTransports)) {
                    $dayTransports = [];
                }
            @endphp
            <div class="transport-box">
                @if (count($dayTransports) === 0)
                    <div class="transport-head-block">
                        <div class="transport-title">{{ ui_phrase('Transport Unit') }} {{ ui_phrase('Day') }} {{ $day['day'] }}</div>
                        <div class="muted">{{ ui_phrase('No transport unit assigned for this day.') }}</div>
                    </div>
                @else
                    @foreach ($dayTransports as $transportIndex => $dayTransport)
                        @if ($transportIndex === 0)
                            <div class="transport-head-block">
                                <div class="transport-title">{{ ui_phrase('Transport Unit') }} {{ ui_phrase('Day') }} {{ $day['day'] }}</div>
                                <table class="app-table transport-table transport-item">
                                    <tr>
                                        <td class="transport-thumb">
                                            @if (!empty($dayTransport['thumbnail_data_uri']))
                                                <div class="thumb-box">
                                                    <img src="{{ $dayTransport['thumbnail_data_uri'] }}" alt="{{ ui_phrase('Transport Unit Thumbnail') }}">
                                                </div>
                                            @else
                                                <span class="muted">{{ ui_phrase('No transport image.') }}</span>
                                            @endif
                                        </td>
                                        <td class="transport-detail">
                                            {!! $renderTransportDetail($dayTransport, $transportIndex) !!}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        @else
                            <table class="app-table transport-table transport-item">
                                <tr>
                                    <td class="transport-thumb">
                                        @if (!empty($dayTransport['thumbnail_data_uri']))
                                            <div class="thumb-box">
                                                <img src="{{ $dayTransport['thumbnail_data_uri'] }}" alt="{{ ui_phrase('Transport Unit Thumbnail') }}">
                                            </div>
                                        @else
                                            <span class="muted">{{ ui_phrase('No transport image.') }}</span>
                                        @endif
                                    </td>
                                    <td class="transport-detail">
                                        {!! $renderTransportDetail($dayTransport, $transportIndex) !!}
                                    </td>
                                </tr>
                            </table>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    @endforeach

    @php
        $hasItineraryInclude = $itineraryIncludeHtml !== '';
        $hasItineraryExclude = $itineraryExcludeHtml !== '';
        $hasItineraryTermConditions = $itineraryTermConditionsHtml !== '';
    @endphp
    @if ($hasItineraryInclude || $hasItineraryExclude || $hasItineraryTermConditions)
        <div class="panel panel-plain">
            <div class="panel-title">{{ ui_phrase('Additional Info') }}</div>
            <table class="itinerary-inc-exc">
                <tr>
                    @if ($hasItineraryInclude)
                        <td class="inc">
                            <span class="title">{{ ui_phrase('Inclusions') }}</span>
                            <div class="richtext">{!! $itineraryIncludeHtml !!}</div>
                        </td>
                    @endif
                    @if ($hasItineraryExclude)
                        <td class="exc">
                            <span class="title">{{ ui_phrase('Exclusions') }}</span>
                            <div class="richtext">{!! $itineraryExcludeHtml !!}</div>
                        </td>
                    @endif
                </tr>
            </table>
            @if ($hasItineraryTermConditions)
                <div style="margin-top: 8px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px;">
                    <span class="additional-info-title">{{ ui_phrase('Terms & Conditions') }}</span>
                    <div class="richtext">{!! $itineraryTermConditionsHtml !!}</div>
                </div>
            @endif
        </div>
    @endif

</body>
</html>
