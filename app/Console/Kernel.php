<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the application's custom Artisan commands explicitly when needed.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        // Add manually-registered command classes here when auto-discovery is not enough.
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('inquiries:send-followup-reminders')
            ->timezone((string) config('app.schedule_timezone', config('app.timezone')))
            ->dailyAt('09:00');
        $schedule->command('inquiries:notify-reservation-draft-deadline-tomorrow')
            ->timezone((string) config('app.schedule_timezone', config('app.timezone')))
            ->dailyAt('09:00');
        $schedule->command('hotels:sync-status-from-prices')->dailyAt('00:10');
        $schedule->command('currencies:sync-market-rates')->dailyAt('06:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
