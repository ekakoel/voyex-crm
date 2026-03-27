<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use Illuminate\Console\Command;

class SyncHotelStatusFromPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotels:sync-status-from-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync hotel status both ways based on active hotel prices (end_date >= today).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->toDateString();
        $now = now();

        $activated = Hotel::query()
            ->where('status', '!=', 'active')
            ->whereHas('prices', function ($query) use ($today) {
                $query->whereNotNull('end_date')
                    ->whereDate('end_date', '>=', $today);
            })
            ->update([
                'status' => 'active',
                'updated_at' => $now,
            ]);

        $inactivated = Hotel::query()
            ->where('status', '!=', 'inactive')
            ->whereDoesntHave('prices', function ($query) use ($today) {
                $query->whereNotNull('end_date')
                    ->whereDate('end_date', '>=', $today);
            })
            ->update([
                'status' => 'inactive',
                'updated_at' => $now,
            ]);

        $this->info("Hotel status sync completed. Activated {$activated}, inactivated {$inactivated}.");

        return self::SUCCESS;
    }
}
