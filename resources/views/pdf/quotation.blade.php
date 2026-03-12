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
            @if ($quotation->created_at)
                <div>Date: {{ $quotation->created_at->format('d M Y') }}</div>
            @endif
            @if ($quotation->validity_date)
                <div>Valid Until: {{ $quotation->validity_date->format('d M Y') }}</div>
            @endif
        </div>
    </div>

    @php
        $inquiryNumber = $quotation->inquiry?->inquiry_number;
        $customerName = $quotation->inquiry?->customer?->name;
        $itineraryTitle = $quotation->itinerary?->title;
    @endphp
    @if ($inquiryNumber || $customerName || $itineraryTitle)
        <div class="card">
            @if ($inquiryNumber)
                <div><strong>Inquiry:</strong> {{ $inquiryNumber }}</div>
            @endif
            @if ($itineraryTitle)
                <div><strong>Itinerary:</strong> {{ $itineraryTitle }}</div>
            @endif
            @if ($customerName)
                <div><strong>Customer:</strong> {{ $customerName }}</div>
            @endif
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
                <th style="width: 10%">Discount Type</th>
                <th style="width: 15%">Discount</th>
                <th style="width: 10%">Total</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($quotation->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->qty }}</td>
                    <td class="right"><x-money :amount="$item->unit_price" currency="IDR" /></td>
                    <td>{{ ($item->discount_type ?? 'fixed') === 'percent' ? 'Percent' : 'Fixed' }}</td>
                    <td class="right">
                        @if (($item->discount_type ?? 'fixed') === 'percent')
                            {{ number_format($item->discount ?? 0, 0, ',', '.') }}%
                        @else
                            <x-money :amount="$item->discount ?? 0" currency="IDR" />
                        @endif
                    </td>
                    <td class="right"><x-money :amount="$item->total ?? 0" currency="IDR" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No items available.</td>
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
            <table style="margin-top: 12px;">
                <tbody>
                @if ($subTotalValue !== null)
                    <tr>
                        <td class="right"><strong>Sub Total</strong></td>
                        <td class="right" style="width: 30%"><x-money :amount="$subTotalValue" currency="IDR" /></td>
                    </tr>
                @endif
                @if ($discountType)
                    <tr>
                        <td class="right">
                            <strong>Discount</strong>
                            @if ($discountType === 'percent')
                                ({{ $discountValue }}%)
                            @else
                                (<x-money :amount="$discountValue" currency="IDR" />)
                            @endif
                        </td>
                        <td class="right">
                            <x-money :amount="($discountType === 'percent' ? ($subTotalValue * ($discountValue / 100)) : $discountValue)" currency="IDR" />
                        </td>
                    </tr>
                @endif
                @if ($finalAmountValue !== null)
                    <tr>
                        <td class="right total">Final Amount</td>
                        <td class="right total"><x-money :amount="$finalAmountValue" currency="IDR" /></td>
                    </tr>
                @endif
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
