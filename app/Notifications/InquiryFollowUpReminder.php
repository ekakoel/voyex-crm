<?php

namespace App\Notifications;

use App\Models\InquiryFollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class InquiryFollowUpReminder extends Notification
{
    use Queueable;

    private const CHANNEL_LABELS = [
        'phone' => 'Phone',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'line' => 'LINE',
        'wechat' => 'WeChat',
        'telegram' => 'Telegram',
        'meeting' => 'Meeting',
        'zoom' => 'Zoom',
        'google-meet' => 'Google Meet',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'other' => 'Other',
    ];

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
        $this->followUp->loadMissing([
            'inquiry.customer',
            'inquiry.quotation',
        ]);

        $inquiry = $this->followUp->inquiry;
        $customerName = $inquiry?->customer?->name ?? '-';
        $dueDate = $this->followUp->due_date?->format('Y-m-d');
        $channel = (string) ($this->followUp->channel ?? '');
        $channelLabel = self::CHANNEL_LABELS[$channel] ?? (filled($channel) ? Str::headline($channel) : '-');
        $summary = trim((string) ($this->followUp->note ?? ''));
        $inquiryUrl = $inquiry ? route('inquiries.show', $inquiry) : null;
        $quotation = $inquiry?->quotation;
        $quotationUrl = $quotation ? route('quotations.show', $quotation) : null;
        $itinerary = $inquiry?->itineraries()
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->first();
        $itineraryUrl = $itinerary ? route('itineraries.show', $itinerary) : null;

        return (new MailMessage)
            ->subject("Reminder Follow-up Inquiry ({$this->label})")
            ->markdown('emails.inquiries.followup-reminder', [
                'userName' => $notifiable->name ?? '',
                'label' => $this->label,
                'inquiryNumber' => $inquiry->inquiry_number ?? '-',
                'customerName' => $customerName,
                'dueDate' => $dueDate,
                'inquiryStatus' => $inquiry->status ?? '-',
                'priority' => $inquiry->priority ?? '-',
                'channel' => $channelLabel,
                'summary' => $summary !== '' ? $summary : '-',
                'inquiryUrl' => $inquiryUrl,
                'itineraryUrl' => $itineraryUrl,
                'quotationUrl' => $quotationUrl,
                'itineraryTitle' => $itinerary?->title ?? '-',
                'quotationNumber' => $quotation?->quotation_number ?? '-',
            ]);
    }
}
