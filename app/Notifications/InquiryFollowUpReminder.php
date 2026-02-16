<?php

namespace App\Notifications;

use App\Models\InquiryFollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InquiryFollowUpReminder extends Notification
{
    use Queueable;

    public function __construct(
        private readonly InquiryFollowUp $followUp,
        private readonly string $label
    )
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $inquiry = $this->followUp->inquiry;
        $customerName = $inquiry?->customer?->name ?? '-';
        $dueDate = $this->followUp->due_date?->format('Y-m-d');

        return (new MailMessage)
            ->subject("Reminder Follow-up Inquiry ({$this->label})")
            ->markdown('emails.inquiries.followup-reminder', [
                'userName' => $notifiable->name ?? '',
                'label' => $this->label,
                'inquiryNumber' => $inquiry->inquiry_number ?? '-',
                'customerName' => $customerName,
                'dueDate' => $dueDate,
            ]);
    }
}
