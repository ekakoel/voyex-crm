<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: 700; }
        .muted { color: #6b7280; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #f9fafb; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .right { text-align: right; }
        .total { font-weight: 700; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">Quotation</div>
            <div class="muted">No: {{ $quotation->quotation_number }}</div>
        </div>
        <div class="muted">
            <div>Date: {{ $quotation->created_at?->format('d M Y') }}</div>
            <div>Valid Until: {{ $quotation->validity_date?->format('d M Y') ?? '-' }}</div>
        </div>
    </div>

    <div class="card">
        <div><strong>Inquiry:</strong> {{ $quotation->inquiry->inquiry_number ?? '-' }}</div>
        <div><strong>Customer:</strong> {{ $quotation->inquiry->customer->name ?? '-' }}</div>
        <div><strong>Status:</strong> {{ ucfirst($quotation->status) }}</div>
    </div>

    @if ($quotation->template)
        <div class="card">
            <div><strong>Template:</strong> {{ $quotation->template->name }}</div>
            <div style="margin-top:8px;">
                {!! $quotation->template->body_html !!}
            </div>
        </div>
    @endif

    <div class="card">
        <strong>Items</strong>
        <table>
            <thead>
            <tr>
                <th style="width: 40%">Description</th>
                <th style="width: 10%">Qty</th>
                <th style="width: 15%">Unit Price</th>
                <th style="width: 15%">Discount</th>
                <th style="width: 20%">Total</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($quotation->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->qty }}</td>
                    <td class="right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->discount ?? 0, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->total ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">No items available.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <table style="margin-top: 12px;">
            <tbody>
            <tr>
                <td class="right"><strong>Sub Total</strong></td>
                <td class="right" style="width: 30%">Rp {{ number_format($quotation->sub_total ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="right">
                    <strong>Discount</strong>
                    @if ($quotation->discount_type)
                        ({{ $quotation->discount_type === 'percent' ? $quotation->discount_value.'%' : 'Rp '.number_format($quotation->discount_value, 0, ',', '.') }})
                    @endif
                </td>
                <td class="right">Rp {{ number_format(($quotation->discount_type ? ($quotation->discount_type === 'percent' ? ($quotation->sub_total * ($quotation->discount_value / 100)) : $quotation->discount_value) : 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="right"><strong>Promo Discount</strong></td>
                <td class="right">Rp {{ number_format($quotation->promo_discount ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="right total">Final Amount</td>
                <td class="right total">Rp {{ number_format($quotation->final_amount ?? 0, 0, ',', '.') }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
