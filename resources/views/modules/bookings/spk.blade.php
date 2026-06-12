<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ui_phrase('SPK') }} - {{ $booking->booking_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; font-size: 12px; }
        h1,h2,h3 { margin: 0 0 8px; }
        .muted { color: #555; }
        .section { margin-top: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cfcfcf; padding: 6px; vertical-align: top; }
        th { background: #f5f5f5; text-align: left; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .print-btn { margin-top: 8px; }
        @media print { .print-btn { display: none; } body { margin: 10px; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ ui_phrase('Service Order / SPK') }}</h1>
            <div class="muted">{{ ui_phrase('Generated at') }}: {{ now()->format('Y-m-d H:i') }}</div>
        </div>
        <button class="print-btn" onclick="window.print()">{{ ui_phrase('Print') }}</button>
    </div>

    <div class="section">
        <h3>{{ ui_phrase('Booking Summary') }}</h3>
        <table>
            <tr>
                <th>{{ ui_phrase('Booking Number') }}</th>
                <td>{{ $booking->booking_number }}</td>
                <th>{{ ui_phrase('Booking Status') }}</th>
                <td>{{ ui_phrase((string) ($booking->status ?? '-')) }}</td>
            </tr>
            <tr>
                <th>{{ ui_phrase('Customer') }}</th>
                <td>{{ $booking->quotation?->inquiry?->customer?->name ?? '-' }}</td>
                <th>{{ ui_phrase('Service Date') }}</th>
                <td>{{ $booking->travel_date?->format('Y-m-d') ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ ui_phrase('Pax (Adult/Child)') }}</th>
                <td>{{ (int) ($booking->pax_adult ?? 0) }} / {{ (int) ($booking->pax_child ?? 0) }}</td>
                <th>{{ ui_phrase('Itinerary') }}</th>
                <td>{{ data_get($booking->itinerary_snapshot, 'title') ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>{{ ui_phrase('Operational Service Items') }}</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ ui_phrase('Description') }}</th>
                    <th>{{ ui_phrase('Service Date') }}</th>
                    <th>{{ ui_phrase('Vendor Confirmation') }}</th>
                    <th>{{ ui_phrase('Dispatch Status') }}</th>
                    <th>{{ ui_phrase('Driver') }}</th>
                    <th>{{ ui_phrase('Guide') }}</th>
                    <th>{{ ui_phrase('Operation Notes') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($booking->items as $index => $item)
                    @php
                        $serviceDate = optional($item->latestBookingLog?->service_date)->format('Y-m-d')
                            ?? ($booking->travel_date && $item->day_number ? $booking->travel_date->copy()->addDays(((int) $item->day_number) - 1)->format('Y-m-d') : '-');
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ $serviceDate }}</td>
                        <td>{{ ui_phrase((string) ($item->vendor_confirmation_status ?? \App\Models\BookingItem::VENDOR_CONFIRMATION_PENDING)) }}</td>
                        <td>{{ ui_phrase((string) ($item->dispatch_status ?? 'pending')) }}</td>
                        <td>{{ trim((string) ($item->assigned_driver_name ?? '-')) }} @if(!empty($item->assigned_driver_phone)) ({{ $item->assigned_driver_phone }}) @endif</td>
                        <td>{{ trim((string) ($item->assigned_guide_name ?? '-')) }} @if(!empty($item->assigned_guide_phone)) ({{ $item->assigned_guide_phone }}) @endif</td>
                        <td>{{ $item->operation_notes ?: ($item->issue_note ?: '-') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Items')]) }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
