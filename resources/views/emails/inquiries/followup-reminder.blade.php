@component('mail::message')
# Reminder Follow-up ({{ $label }})

Hello {{ $userName }},

You have an inquiry follow-up reminder that needs action.

@component('mail::panel')
**Inquiry:** {{ $inquiryNumber }}  
**Customer:** {{ $customerName }}  
**Due Date:** {{ $dueDate }}
@endcomponent

Please follow up as soon as possible based on the scheduled timeline.

Thank you,  
{{ config('app.name') }}
@endcomponent
