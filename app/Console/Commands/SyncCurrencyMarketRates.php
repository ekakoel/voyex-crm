<?php

namespace App\Console\Commands;

use App\Services\CurrencyMarketRateSyncService;
use Illuminate\Console\Command;

class SyncCurrencyMarketRates extends Command
{
    protected $signature = 'currencies:sync-market-rates {--force : Force sync even if already synced today}';

    protected $description = 'Sync market currency rates to IDR from external provider (daily baseline).';

    public function handle(CurrencyMarketRateSyncService $syncService): int
    {
        $result = $syncService->sync((bool) $this->option('force'));

        $this->info(sprintf(
            'Currency market rates sync completed. Updated: %d, Skipped: %d%s',
            (int) ($result['updated'] ?? 0),
            (int) ($result['skipped'] ?? 0),
            isset($result['fetched_at']) && $result['fetched_at']
                ? ', Synced At: ' . $result['fetched_at']->toDateTimeString()
                : ''
        ));

        return self::SUCCESS;
    }
}
