<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $voucher->voucher_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .outer { border: 1px solid #111; padding: 0; }
        .row { width: 100%; border-collapse: collapse; }
        .row td { border: 1px solid #111; vertical-align: top; padding: 6px 8px; }
        .row-no-border td { border: 0; }
        .title { font-size: 44px; font-weight: 700; letter-spacing: 1px; text-align: center; line-height: 1; }
        .brand { font-size: 36px; font-weight: 700; }
        .label { font-weight: 700; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .big-no { font-size: 34px; font-weight: 700; }
        .compact { line-height: 1.25; }
        .stamp-box { min-height: 170px; }
        .signature-line { margin-top: 34px; border-top: 1px solid #111; }
        .footer-note { margin-top: 4px; font-size: 11px; }
    </style>
</head>
<body>
    @php
        $company = \App\Models\CompanySetting::query()->first();
        $companyName = trim((string) ($company?->company_name ?: 'BALI KAMI TOURS'));
        $companyAddress = collect([
            $company?->address,
            $company?->city,
            $company?->province,
        ])->filter(fn ($v) => trim((string) $v) !== '')->implode(', ');
        $companyPhone = trim((string) ($company?->contact_phone ?? ''));
        $companyEmail = trim((string) ($company?->contact_email ?? ''));
        $vendorLine = trim((string) ($voucher->vendor_contact_name ?: '-'));
        $vendorAddress = trim((string) ($bookingItem->serviceable?->vendor?->address ?? '-'));
        $serviceDate = optional($voucher->service_date)->format('d-M-y') ?? optional($booking->travel_date)->format('d-M-y') ?? '-';
        $issueDate = optional($voucher->issued_at)->format('d-M-y') ?? now()->format('d-M-y');
        $stampPath = public_path('assets/images/stempel_bali_kami.png');
        $hasStamp = is_file($stampPath);
    @endphp

    <div class="outer">
        <table class="row row-no-border">
            <tr>
                <td style="width:40%; border-right:0;">
                    <div class="brand">{{ $companyName }}</div>
                    <div class="compact">{{ $companyAddress !== '' ? $companyAddress : '-' }}</div>
                    <div class="compact">{{ $companyPhone !== '' ? $companyPhone : '-' }}</div>
                    <div class="compact">{{ ui_phrase('E-mail') }} : {{ $companyEmail !== '' ? $companyEmail : '-' }}</div>
                </td>
                <td style="width:60%; border-left:0;">
                    <div class="title">{{ strtoupper((string) ui_phrase('Voucher')) }}</div>
                    <table class="row row-no-border" style="margin-top:4px;">
                        <tr>
                            <td style="width:28%; border:0;" class="label right">{{ ui_phrase('TO') }} :</td>
                            <td style="width:72%; border:0;" class="right">
                                <div class="bold">{{ $vendorLine }}</div>
                                <div>{{ $vendorAddress }}</div>
                                <div>{{ $voucher->vendor_contact_phone ?: '-' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:0;" class="label right">{{ ui_phrase('No') }} :</td>
                            <td style="border:0;" class="big-no">{{ $voucher->voucher_number }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="row">
            <tr>
                <td style="width:44%;">
                    <span class="label">{{ ui_phrase('Tour / Name') }} :</span><br>
                    {{ $voucher->tour_name ?: '-' }}
                </td>
                <td style="width:32%;">
                    <span class="label">{{ ui_phrase('Total Pax') }} :</span><br>
                    {{ (int) ($bookingItem->qty ?? 0) }}
                </td>
                <td style="width:24%;">
                    <span class="label">{{ ui_phrase('Issuing Date') }} :</span><br>
                    {{ $issueDate }}
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label">{{ ui_phrase('Please provide bearer of this voucher with services as below:') }}</span><br><br>
                    {{ ui_phrase('Date') }} {{ $serviceDate }}<br>
                    {{ $bookingItem->description }}<br>
                    {{ ui_phrase('Confirmation No') }} : {{ $voucher->confirmation_code ?: '#' }}<br><br>
                    {{ ui_phrase("All other services not specified above are not for client's account") }}
                </td>
                <td class="stamp-box">
                    <span class="label">{{ ui_phrase('Official Stamp') }}</span><br><br>
                    @if ($hasStamp)
                        <div style="margin-top:8px;">
                            <img src="{{ $stampPath }}" alt="Official Stamp" style="width:120px; height:auto;">
                        </div>
                    @else
                        <div style="margin-top:20px;">{{ $companyName }}</div>
                    @endif
                    <div style="margin-top:12px;" class="label">{{ ui_phrase('Authorized Signature') }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">{{ ui_phrase('Final service to be rendered as') }} :</span><br><br>
                    {{ ui_phrase('Confirmed By') }} : {{ $companyName }}
                </td>
                <td><span class="label">{{ ui_phrase('Tour Guide') }}:</span></td>
                <td><span class="label">{{ ui_phrase('Remarks') }}</span></td>
            </tr>
        </table>
    </div>
    <div class="footer-note">
        {{ ui_phrase('This voucher not valid unless officially signed & stamp. Please attach original voucher for billing.') }}
    </div>
</body>
</html>
