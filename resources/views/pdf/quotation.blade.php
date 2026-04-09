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

    <table>
            <thead>
            <tr>
                <th style="width: 50%">Description</th>
                <th style="width: 10%">Qty</th>
                <th style="width: 20%">Unit Price</th>
                <th style="width: 20%">Total</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($quotation->items as $item)
                @php
                    $meta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
                    $paxType = strtolower((string) ($meta['pax_type'] ?? ''));
                    $paxSuffix = $paxType === 'adult' ? ' [Adult Publish Rate]' : ($paxType === 'child' ? ' [Child Publish Rate]' : '');
                    $qty = max(0, (int) ($item->qty ?? 0));
                    $lineTotal = (float) ($item->total ?? 0);
                    $itemDiscount = (float) ($item->discount ?? 0);
                    $displayUnitPrice = (float) ($item->unit_price ?? 0);
                    if ($itemDiscount > 0) {
                        $displayUnitPrice = $qty > 0 ? ($lineTotal / $qty) : $lineTotal;
                    }
                @endphp
                <tr>
                    <td>{{ $item->description }}{{ $paxSuffix }}</td>
                    <td class="right">{{ $qty }}</td>
                    <td class="right"><x-money :amount="$displayUnitPrice" currency="IDR" /></td>
                    <td class="right"><x-money :amount="$lineTotal" currency="IDR" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted">No items available.</td>
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
            <table style="margin-top: 12px;">
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
