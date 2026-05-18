<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $voucher->voucher_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .sheet {
            height: 45%;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 2mm;
            box-sizing: border-box;
        }
        .voucher-wrap { width: 100%; }
        .outer { border: 1px solid #111; padding: 0; }
        .row { width: 100%; border-collapse: collapse; }
        .row td { border: 1px solid #111; vertical-align: top; padding: 4px 6px; }
        .row-no-border td { border: 0; }
        .title { font-size: 14px; font-weight: 700; text-align: center; line-height: 1.2; }
        .brand { font-size: 14px; font-weight: 700; }
        .label { font-weight: 700; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .big-no { font-size: 12px; font-weight: 700; }
        .compact { line-height: 1.15; }
        .stamp-box { min-height: 120px; }
        .signature-line { margin-top: 20px; border-top: 1px solid #111; }
        .footer-note { margin-top: 0; font-size: 10px; line-height: 1.15; }
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
        $vendorLine = trim((string) ($preview['vendor_name'] ?? '-'));
        $vendorAddress = trim((string) ($preview['to_location'] ?? '-'));
        $vendorContact = trim((string) ($preview['to_contact'] ?? '-'));
        $serviceDate = trim((string) ($preview['service_date'] ?? '-'));
        $issueDate = trim((string) ($preview['issue_date'] ?? now()->format('d-M-y')));
        $tourName = trim((string) ($preview['tour_name'] ?? ($voucher->tour_name ?? '-')));
        $qty = (int) ($preview['qty'] ?? (int) ($bookingItem->qty ?? 0));
        $itemLabel = trim((string) ($preview['item_label'] ?? ($bookingItem->description ?? '-')));
        $confirmation = trim((string) ($preview['confirmation'] ?? ($voucher->confirmation_code ?? '#')));
        $contactedPerson = trim((string) ($preview['contacted_person'] ?? '-'));
        $contactChannel = trim((string) ($preview['contact_channel'] ?? '-'));
        $contactDetail = trim((string) ($preview['contact_detail'] ?? '-'));
        $stampPath = public_path('assets/images/stempel_bali_kami.png');
        $hasStamp = is_file($stampPath);
    @endphp

    <div class="sheet">
    <div class="voucher-wrap">
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
                    <table class="row row-no-border" style="margin-top:2px;">
                        <tr>
                            <td style="width:28%; border:0;" class="label right">{{ ui_phrase('TO') }} :</td>
                            <td style="width:72%; border:0;" class="right">
                                <div class="bold">{{ $vendorLine }}</div>
                                <div>{{ $vendorAddress }}</div>
                                <div>{{ $vendorContact }}</div>
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
                    {{ $tourName }}
                </td>
                <td style="width:32%;">
                    <span class="label">{{ ui_phrase('Total Pax') }} :</span><br>
                    {{ $qty }}
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
                    {{ $itemLabel }}<br>
                    {{ ui_phrase('Confirmation No') }} : {{ $confirmation }}<br><br>
                    {{ ui_phrase("All other services not specified above are not for client's account") }}
                </td>
                <td class="stamp-box">
                    <span class="label">{{ ui_phrase('Official Stamp') }}</span><br><br>
                    @if ($hasStamp)
                        <div style="margin-top:4px;">
                            <img src="{{ $stampPath }}" alt="Official Stamp" style="width:95px; height:auto;">
                        </div>
                    @else
                        <div style="margin-top:12px;">{{ $companyName }}</div>
                    @endif
                    <div style="margin-top:8px;" class="label">{{ ui_phrase('Authorized Signature') }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">{{ ui_phrase('Final service to be rendered as') }} :</span><br><br>
                    {{ ui_phrase('Confirmed By') }} : {{ $contactedPerson }}<br>
                    {{ ui_phrase('Contact Channel') }} : {{ $contactChannel }}<br>
                    {{ ui_phrase('Contact Detail') }} : {{ $contactDetail }}
                </td>
                <td><span class="label">{{ ui_phrase('Tour Guide') }}:</span></td>
                <td><span class="label">{{ ui_phrase('Remarks') }}</span></td>
            </tr>
        </table>
    </div>
    </div>
    </div>
    <div class="footer-note">
        {{ ui_phrase('This voucher not valid unless officially signed & stamp. Please attach original voucher for billing.') }}
    </div>
</body>
</html>
