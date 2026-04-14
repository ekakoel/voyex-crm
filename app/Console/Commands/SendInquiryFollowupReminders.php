<?php

namespace App\Console\Commands;

use App\Models\InquiryFollowUp;
use App\Notifications\InquiryFollowUpReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendInquiryFollowupReminders extends Command
{
    protected $signature = 'inquiries:send-followup-reminders {--date=}';
    protected $description = 'Send inquiry follow-up reminder emails on due date only';

    public function handle(): int
    {
        $baseDate = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();
        $date = $baseDate->toDateString();

        $followUps = InquiryFollowUp::query()
            ->whereDate('due_date', $date)
            ->where('is_done', false)
            ->whereNull('last_reminded_at')
            ->whereHas('inquiry', function ($q) {
                $q->where('reminder_enabled', true);
            })
            ->with(['inquiry.customer', 'inquiry.creator', 'inquiry.assignedUser'])
            ->get();

        $sent = 0;
        foreach ($followUps as $followUp) {
            $user = $followUp->inquiry?->creator ?? $followUp->inquiry?->assignedUser;
            if ($user && $user->email) {
                $user->notify(new InquiryFollowUpReminder($followUp, 'H-0'));
                $followUp->forceFill([
                    'last_reminded_at' => now(),
                ])->saveQuietly();
                $sent++;
            }
        }

        $this->info("Reminders sent: {$sent}");
        return Command::SUCCESS;
    }
}
