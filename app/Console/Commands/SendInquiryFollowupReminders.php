<?php

namespace App\Console\Commands;

use App\Models\InquiryFollowUp;
use App\Notifications\InquiryFollowUpReminder;
use Illuminate\Console\Command;

class SendInquiryFollowupReminders extends Command
{
    protected $signature = 'inquiries:send-followup-reminders {--date=}';
    protected $description = 'Send inquiry follow-up reminder emails for due date';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->toDateString();

        $followUps = InquiryFollowUp::query()
            ->whereDate('due_date', $date)
            ->where('is_done', false)
            ->whereHas('inquiry', function ($q) {
                $q->where('reminder_enabled', true);
            })
            ->with(['inquiry.assignedUser'])
            ->get();

        $sent = 0;
        foreach ($followUps as $followUp) {
            $user = $followUp->inquiry?->assignedUser;
            if ($user && $user->email) {
                $user->notify(new InquiryFollowUpReminder($followUp, 'H-0'));
                $sent++;
            }
        }

        $dateH1 = now()->addDay()->toDateString();
        $followUpsH1 = InquiryFollowUp::query()
            ->whereDate('due_date', $dateH1)
            ->where('is_done', false)
            ->whereHas('inquiry', function ($q) {
                $q->where('reminder_enabled', true);
            })
            ->with(['inquiry.assignedUser'])
            ->get();

        foreach ($followUpsH1 as $followUp) {
            $user = $followUp->inquiry?->assignedUser;
            if ($user && $user->email) {
                $user->notify(new InquiryFollowUpReminder($followUp, 'H-1'));
                $sent++;
            }
        }

        $this->info("Reminders sent: {$sent}");
        return Command::SUCCESS;
    }
}
