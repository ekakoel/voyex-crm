<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Itinerary {{ $itinerary->title }}</title>
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
        .thumb-box { width: 100%; aspect-ratio: 4 / 3; border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden; background: #ffffff; }
        .thumb-box img { width: 100%; height: 180px; object-fit: cover; display: block; }
        .muted { color: #6b7280; }
        .type-badge { display: inline-block; border-radius: 999px; padding: 2px 7px; font-size: 9px; font-weight: 700; border: 1px solid #d1d5db; }
        .type-attraction { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .type-activity { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .type-fnb { background: #fffbeb; color: #b45309; border-color: #fcd34d; }
        .type-point { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
        .right { text-align: right; }
        .footer { margin-top: 14px; font-size: 10px; color: #6b7280; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Travel Itinerary</div>
        <div class="subtitle">Generated on {{ now()->format('d M Y H:i') }}</div>
        <span class="chip">Title: {{ $itinerary->title }}</span>
        <span class="chip">Duration: {{ $itinerary->duration_days }} day(s)</span>
    </div>

    <div class="panel">
        <div class="panel-title">Itinerary Overview</div>
        <table class="info-table">
            <tr>
                <td>{{ $itinerary->description ?: '-' }}</td>
            </tr>
        </table>
    </div>

    @foreach ($scheduleByDay as $day)
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="day-title">Day {{ $day['day'] }}</div>
                <div class="day-time">Start Tour: {{ $day['start_time'] }} | End Tour: {{ $day['end_time'] }}</div>
            </div>
            <div class="day-time" style="margin-top: 4px;">Travel from Start Point: {{ (int) ($day['start_travel_minutes'] ?? 0) }} min</div>

            <table class="items">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 20%;">Time</th>
                        <th style="width: 25%;">Item</th>
                        <th style="width: 15%;">location</th>
                        <th style="width: 35%;">Thumbnail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($day['items'] as $index => $item)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ in_array(($item['point_role'] ?? ''), ['start', 'end'], true) ? ($item['point_type_label'] ?? $item['type']) : $item['type'] }}</strong><br>
                                @if (($item['point_role'] ?? '') === 'start')
                                    {{ $item['start_time'] ?: '--:--' }}
                                @elseif (($item['point_role'] ?? '') === 'end')
                                    {{ $item['end_time'] ?: '--:--' }}
                                @else
                                    {{ $item['start_time'] }} - {{ $item['end_time'] }}
                                @endif
                            </td>
                            <td>
                                <div><strong>{{ $item['name'] }}</strong></div>
                                <div class="muted">{{ \Illuminate\Support\Str::limit(strip_tags((string) ($item['description'] ?? '-')), 120) }}</div>
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
        </div>
    @endforeach

    <div class="footer">
        Generated by Voyex CRM
    </div>
</body>
</html>
