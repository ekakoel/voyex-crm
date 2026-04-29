@component('mail::message')
# Draft Inquiry Needs Follow-up

Hello {{ $userName }},

There is a draft inquiry with deadline **tomorrow** that has not been responded/followed up yet.

@component('mail::panel')
**Inquiry:** {{ $inquiryNumber }}  
**Customer:** {{ $customerName }}  
**Deadline:** {{ $deadline }}  
**Status:** {{ ucfirst((string) $status) }}  
**Priority:** {{ ucfirst((string) $priority) }}
@endcomponent

@if (!empty($inquiryUrl))
@component('mail::button', ['url' => $inquiryUrl])
Open Inquiry Detail
@endcomponent
@endif

Please follow up this inquiry as soon as possible.

Thank you,  
{{ config('app.name') }}
@endcomponent

