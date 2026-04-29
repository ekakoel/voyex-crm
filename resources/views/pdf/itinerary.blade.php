<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Itinerary {{ $itinerary->title }}</title>
    <style>
        {!! $pdfFontFaceCss ?? '' !!}
        body { font-family: {!! $pdfFontFamilyCss ?? "'DejaVu Sans', Arial, sans-serif" !!}; color: #111827; font-size: 11px; line-height: 1.45; }
        .header { margin-bottom: 14px; }
        .title { font-size: 22px; font-weight: 700; letter-spacing: .2px; color: #111827; }
        .subtitle { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .chip { display: inline-block; margin-right: 6px; margin-top: 6px; border: 1px solid #d1d5db; border-radius: 999px; padding: 3px 8px; font-size: 10px; color: #374151; }
        .panel { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; }
        .panel-plain { border: none; border-radius: 0; padding: 0; background: transparent; }
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
        .itinerary-inc-exc .title { display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 3px; color: #374151; }
        .transport-box { margin-top: 8px; }
        .transport-head-block { display: table; width: 100%; page-break-inside: avoid; break-inside: avoid; page-break-before: auto; }
        .transport-title { font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: #334155; font-weight: 700; margin-bottom: 4px; }
        .transport-item { page-break-inside: avoid; break-inside: avoid; margin-top: 6px; border: 1px solid #cbd5e1; }
        table.transport-table { width: 100%; border-collapse: collapse; }
        table.transport-table td { vertical-align: top; padding: 3px 5px; }
        .transport-thumb { width: 120px; }
        .transport-thumb .thumb-box img { height: 82px; }
        .transport-detail { font-size: 9.5px; color: #374151; line-height: 1.35; }
        .transport-detail strong { color: #111827; }
        .footer { margin-top: 14px; font-size: 10px; color: #6b7280; text-align: right; }
        .day-panel { page-break-before: always; }
        .day-panel.first { page-break-before: auto; }
    </style>
</head>
<body>
    <div class="header">
        <span>{{ ui_phrase('Itinerary') }}</span><br>
        <div class="title">{{ $itinerary->title }}</div>
        <div class="subtitle">Generated on {{ \App\Support\DateTimeDisplay::datetime(now()) }}</div>
        <span>Duration: {{ $itinerary->duration_days."D" }}{{ $itinerary->duration_nights > 0 ? "/".$itinerary->duration_nights."N":"";  }}</span>
    </div>

    <div class="panel panel-plain">
        <div class="panel-title">Overview</div>
        <table class="app-table info-table">
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
        <div class="panel panel-plain day-panel {{ $loop->first ? 'first' : '' }}">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="day-title">Day {{ $day['day'] }}</div>
                <div class="day-time">Start Tour: {{ $day['start_time'] }} | End Tour: {{ $day['end_time'] }}</div>
            </div>

            <table class="items">
                <thead>
                    <tr>
                        <th style="width: 5%;">{{ ui_phrase('No') }}</th>
                        <th style="width: 20%;">{{ ui_phrase('Time') }}</th>
                        <th style="width: 25%;">{{ ui_phrase('Item') }}</th>
                        <th style="width: 15%;">{{ ui_phrase('Location') }}</th>
                        <th style="width: 35%;">{{ ui_phrase('Image') }}</th>
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
                $dayTransports = $day['transport_units'] ?? [];
                if (!is_array($dayTransports)) {
                    $dayTransports = [];
                }
            @endphp
            <div class="transport-box">
                @if (count($dayTransports) === 0)
                    <div class="transport-head-block">
                        <div class="transport-title">Transport Unit Day {{ $day['day'] }}</div>
                        <div class="muted">No transport unit assigned for this day.</div>
                    </div>
                @else
                    @foreach ($dayTransports as $transportIndex => $dayTransport)
                        @if ($transportIndex === 0)
                            <div class="transport-head-block">
                                <div class="transport-title">Transport Unit Day {{ $day['day'] }}</div>
                                <table class="app-table transport-table transport-item">
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
                                            <div><strong>Transport #{{ $transportIndex + 1 }}</strong></div>
                                            <div><strong>Unit:</strong> {{ $dayTransport['unit_name'] ?? '-' }}</div>
                                            <div><strong>Transport:</strong> {{ $dayTransport['transport_name'] ?? '-' }} ({{ $dayTransport['transport_type'] ?? '-' }})</div>
                                            <div><strong>Vehicle:</strong> {{ $dayTransport['brand_model'] ?? '-' }}</div>
                                            <div><strong>Capacity:</strong> Seat {{ $dayTransport['seat_capacity'] ?? '-' }} | Luggage {{ $dayTransport['luggage_capacity'] ?? '-' }}</div>
                                            <div><strong>Driver:</strong> {{ !empty($dayTransport['with_driver']) ? 'With driver' : 'Without driver' }} | <strong>AC:</strong> {{ !empty($dayTransport['air_conditioned']) ? 'Yes' : 'No' }}</div>
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
                                                <img src="{{ $dayTransport['thumbnail_data_uri'] }}" alt="Transport Unit Thumbnail">
                                            </div>
                                        @else
                                            <span class="muted">{{ __('No transport image.') }}</span>
                                        @endif
                                    </td>
                                    <td class="transport-detail">
                                        <div><strong>Transport #{{ $transportIndex + 1 }}</strong></div>
                                        <div><strong>Unit:</strong> {{ $dayTransport['unit_name'] ?? '-' }}</div>
                                        <div><strong>Transport:</strong> {{ $dayTransport['transport_name'] ?? '-' }} ({{ $dayTransport['transport_type'] ?? '-' }})</div>
                                        <div><strong>Vehicle:</strong> {{ $dayTransport['brand_model'] ?? '-' }}</div>
                                        <div><strong>Capacity:</strong> Seat {{ $dayTransport['seat_capacity'] ?? '-' }} | Luggage {{ $dayTransport['luggage_capacity'] ?? '-' }}</div>
                                        <div><strong>Driver:</strong> {{ !empty($dayTransport['with_driver']) ? 'With driver' : 'Without driver' }} | <strong>AC:</strong> {{ !empty($dayTransport['air_conditioned']) ? 'Yes' : 'No' }}</div>
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
        $itineraryIncludeText = \App\Support\SafeRichText::plainText($itinerary->itinerary_include ?? null);
        $itineraryExcludeText = \App\Support\SafeRichText::plainText($itinerary->itinerary_exclude ?? null);
        $itineraryIncludeHtml = \App\Support\SafeRichText::sanitize((string) ($itinerary->itinerary_include ?? ''));
        $itineraryExcludeHtml = \App\Support\SafeRichText::sanitize((string) ($itinerary->itinerary_exclude ?? ''));
    @endphp
    @if (filled($itineraryIncludeText) || filled($itineraryExcludeText))
        <div class="panel panel-plain">
            <div class="panel-title">Itinerary Include & Exclude</div>
            <table class="itinerary-inc-exc">
                <tr>
                    @if (filled($itineraryIncludeText))
                        <td class="inc">
                            <span class="title">{{ ui_phrase('Itinerary Include') }}</span>
                            <div class="richtext">{!! $itineraryIncludeHtml !!}</div>
                        </td>
                    @endif
                    @if (filled($itineraryExcludeText))
                        <td class="exc">
                            <span class="title">{{ ui_phrase('Itinerary Exclude') }}</span>
                            <div class="richtext">{!! $itineraryExcludeHtml !!}</div>
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    @endif

</body>
</html>
