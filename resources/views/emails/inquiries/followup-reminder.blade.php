@component('mail::message')
# Reminder Follow-up ({{ $label }})

Halo {{ $userName }},

Anda memiliki reminder follow-up inquiry yang harus ditindaklanjuti.

@component('mail::panel')
**Inquiry:** {{ $inquiryNumber }}  
**Customer:** {{ $customerName }}  
**Due Date:** {{ $dueDate }}
@endcomponent

Silakan segera follow-up sesuai jadwal yang telah ditentukan.

Terima kasih,  
{{ config('app.name') }}
@endcomponent
