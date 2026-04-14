@component('mail::message')
# Reminder Follow-up ({{ $label }})

Hello {{ $userName }},

This is your follow-up reminder for an inquiry that needs action.

@component('mail::panel')
**Inquiry:** {{ $inquiryNumber }}  
**Customer:** {{ $customerName }}  
**Due Date:** {{ $dueDate }}  
**Inquiry Status:** {{ ucfirst((string) $inquiryStatus) }}  
**Priority:** {{ ucfirst((string) $priority) }}  
**Channel:** {{ $channel }}  
**Reminder Note:** {{ $summary }}
@endcomponent

@if (!empty($inquiryUrl))
@component('mail::button', ['url' => $inquiryUrl])
Open Inquiry
@endcomponent
@endif

## Quick Links

@if (!empty($inquiryUrl))
@component('mail::button', ['url' => $inquiryUrl])
Open Inquiry
@endcomponent
@endif

@if (!empty($itineraryUrl))
@component('mail::button', ['url' => $itineraryUrl])
Open Itinerary
@endcomponent
@else
Itinerary: {{ $itineraryTitle ?? '-' }}
@endif

@if (!empty($quotationUrl))
@component('mail::button', ['url' => $quotationUrl])
Open Quotation
@endcomponent
@else
Quotation: {{ $quotationNumber ?? '-' }}
@endif

Please continue the follow-up according to the reminder schedule.

Thank you,  
{{ config('app.name') }}
@endcomponent
