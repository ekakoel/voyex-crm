<?php

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationDraftDeadlineReminder extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Inquiry $inquiry
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $this->inquiry->loadMissing(['customer:id,name']);

        return (new MailMessage)
            ->subject('Reminder: Draft inquiry deadline is tomorrow')
            ->markdown('emails.inquiries.reservation-draft-deadline-reminder', [
                'userName' => (string) ($notifiable->name ?? ''),
                'inquiryNumber' => (string) ($this->inquiry->inquiry_number ?? '-'),
                'customerName' => (string) ($this->inquiry->customer?->name ?? '-'),
                'deadline' => $this->inquiry->deadline?->format('Y-m-d') ?? '-',
                'status' => (string) ($this->inquiry->status ?? '-'),
                'priority' => (string) ($this->inquiry->priority ?? '-'),
                'inquiryUrl' => route('inquiries.show', $this->inquiry),
            ]);
    }
}

