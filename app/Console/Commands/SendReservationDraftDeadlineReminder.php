<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Inquiry;
use App\Models\User;
use App\Notifications\ReservationDraftDeadlineReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendReservationDraftDeadlineReminder extends Command
{
    protected $signature = 'inquiries:notify-reservation-draft-deadline-tomorrow {--date=}';

    protected $description = 'Send 09:00 reminder emails to all Reservation users for draft inquiries with deadline tomorrow';

    public function handle(): int
    {
        $timezone = (string) config('app.schedule_timezone', config('app.timezone'));
        $baseDate = $this->option('date')
            ? Carbon::parse((string) $this->option('date'), $timezone)->startOfDay()
            : now($timezone)->startOfDay();
        $targetDeadlineDate = $baseDate->copy()->addDay()->toDateString();
        $notifyDate = $baseDate->toDateString();

        $reservationUsers = User::role(['Reservation'])
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->get(['id', 'name', 'email']);

        if ($reservationUsers->isEmpty()) {
            $this->info('No reservation users found. Skipped.');
            return Command::SUCCESS;
        }

        $inquiries = Inquiry::query()
            ->where('status', 'draft')
            ->whereDate('deadline', $targetDeadlineDate)
            ->with(['customer:id,name'])
            ->get(['id', 'inquiry_number', 'customer_id', 'status', 'priority', 'deadline']);

        if ($inquiries->isEmpty()) {
            $this->info("No draft inquiries found for deadline {$targetDeadlineDate}. Skipped.");
            return Command::SUCCESS;
        }

        $sent = 0;
        foreach ($inquiries as $inquiry) {
            foreach ($reservationUsers as $user) {
                $inserted = DB::table('inquiry_notification_logs')->insertOrIgnore([
                    'inquiry_id' => (int) $inquiry->id,
                    'user_id' => (int) $user->id,
                    'type' => 'reservation_draft_deadline_tomorrow',
                    'notify_date' => $notifyDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ((int) $inserted !== 1) {
                    continue;
                }

                $sentAt = now($timezone);
                $user->notify(new ReservationDraftDeadlineReminder($inquiry));
                ActivityLog::query()->create([
                    'user_id' => null,
                    'module' => 'Inquiry',
                    'action' => 'reminder_email_sent',
                    'subject_id' => (int) $inquiry->id,
                    'subject_type' => $inquiry->getMorphClass(),
                    'properties' => [
                        'note' => sprintf(
                            'Email remainder has been send to %s %s!',
                            (string) ($user->name ?? '-'),
                            $sentAt->format('Y-m-d (H:i)')
                        ),
                        'recipient_name' => (string) ($user->name ?? ''),
                        'recipient_email' => (string) ($user->email ?? ''),
                        'sent_at' => $sentAt->toDateTimeString(),
                    ],
                ]);
                $sent++;
            }
        }

        $this->info("Reservation reminder emails sent: {$sent}");

        return Command::SUCCESS;
    }
}
