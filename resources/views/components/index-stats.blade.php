@props(['cards' => []])

@if (!empty($cards))
    <div {{ $attributes->merge(['class' => 'app-card-grid']) }}>
        @foreach ($cards as $card)
            @php
                $key = strtolower((string) ($card['key'] ?? $card['label'] ?? ''));
                $label = (string) ($card['label'] ?? '-');
                $value = (int) ($card['value'] ?? 0);
                $caption = (string) ($card['caption'] ?? 'Total');
                $tone = (string) ($card['tone'] ?? 'slate');
                $colorMap = [
                    'total' => 'slate',
                    'all' => 'slate',
                    'active' => 'emerald',
                    'approved' => 'emerald',
                    'processed' => 'emerald',
                    'enabled' => 'emerald',
                    'inactive' => 'rose',
                    'rejected' => 'rose',
                    'disabled' => 'rose',
                    'pending' => 'amber',
                    'draft' => 'slate',
                    'final' => 'violet',
                    'vendors' => 'teal',
                    'accommodations' => 'indigo',
                    'attractions' => 'sky',
                    'airports' => 'sky',
                    'transports' => 'cyan',
                    'customers' => 'indigo',
                    'inquiries' => 'amber',
                    'quotations' => 'teal',
                    'bookings' => 'emerald',
                    'invoices' => 'violet',
                    'sales' => 'indigo',
                    'revenue' => 'emerald',
                    'payments' => 'teal',
                    'expenses' => 'rose',
                    'countries' => 'sky',
                    'top-country' => 'violet',
                ];
                $iconMap = [
                    'total' => 'fa-layer-group',
                    'all' => 'fa-layer-group',
                    'active' => 'fa-circle-check',
                    'approved' => 'fa-circle-check',
                    'processed' => 'fa-circle-check',
                    'enabled' => 'fa-toggle-on',
                    'inactive' => 'fa-circle-xmark',
                    'rejected' => 'fa-circle-xmark',
                    'disabled' => 'fa-toggle-off',
                    'pending' => 'fa-clock',
                    'draft' => 'fa-file-lines',
                    'final' => 'fa-award',
                    'vendors' => 'fa-handshake',
                    'accommodations' => 'fa-hotel',
                    'attractions' => 'fa-landmark',
                    'airports' => 'fa-plane-departure',
                    'transports' => 'fa-bus',
                    'customers' => 'fa-users',
                    'inquiries' => 'fa-envelope-open-text',
                    'quotations' => 'fa-file-invoice-dollar',
                    'bookings' => 'fa-calendar-check',
                    'invoices' => 'fa-receipt',
                    'sales' => 'fa-chart-line',
                    'revenue' => 'fa-sack-dollar',
                    'payments' => 'fa-credit-card',
                    'expenses' => 'fa-file-invoice',
                    'countries' => 'fa-flag',
                    'top-country' => 'fa-location-dot',
                ];
                $iconClass = $iconMap[$key] ?? 'fa-chart-pie';
                $colorKey = (string) ($card['color'] ?? $colorMap[$key] ?? $tone);
            @endphp
            <div class="app-card p-4">
                <div class="flex items-center justify-between h-full relative">
                    <div class="data-card">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($value) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $caption }}</p>
                    </div>
                    <div class="icon-kpi icon-kpi--{{ $colorKey }}">
                        <i class="fa-solid {{ $iconClass }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
