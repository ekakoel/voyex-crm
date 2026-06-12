<?php

namespace App\Console\Commands;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeQuotationStatus extends Command
{
    protected $signature = 'quotations:normalize-status
                            {--dry-run : Preview changes without updating data}
                            {--apply : Apply the legacy-to-final status mapping}';

    protected $description = 'Preview or apply safe quotation legacy status normalization.';

    /**
     * @var array<string, string>
     */
    private const STATUS_MAP = [
        Quotation::STATUS_PENDING_VALIDATION => Quotation::STATUS_NEED_VALIDATION,
        Quotation::STATUS_PENDING_REVALIDATION => Quotation::STATUS_NEED_REVALIDATION,
        Quotation::STATUS_CUSTOMER_APPROVED => Quotation::STATUS_APPROVED,
        Quotation::STATUS_BOOKING_CREATED => Quotation::STATUS_CONVERTED_TO_BOOKING,
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('quotations') || ! Schema::hasColumn('quotations', 'status')) {
            $this->error('The quotations.status column is not available.');

            return Command::FAILURE;
        }

        foreach (self::STATUS_MAP as $from => $to) {
            if (! QuotationStatus::isKnown($to)) {
                $this->error("Target status {$to} is not supported by QuotationStatus enum.");

                return Command::FAILURE;
            }
        }

        $apply = (bool) $this->option('apply') && ! (bool) $this->option('dry-run');
        $summary = $this->legacyStatusCounts();

        $this->table(
            ['Legacy Status', 'Final Status', 'Rows'],
            collect(self::STATUS_MAP)
                ->map(fn (string $to, string $from): array => [$from, $to, (int) ($summary[$from] ?? 0)])
                ->values()
                ->all()
        );

        $total = (int) collect($summary)->sum();
        if (! $apply) {
            $this->warn('Dry-run mode: no quotation status was updated.');
            $this->info("Rows that would be updated: {$total}");

            return Command::SUCCESS;
        }

        $updated = DB::transaction(function (): int {
            $changed = 0;
            foreach (self::STATUS_MAP as $from => $to) {
                $changed += DB::table('quotations')
                    ->where('status', $from)
                    ->update([
                        'status' => $to,
                        'updated_at' => now(),
                    ]);
            }

            return $changed;
        });

        $this->info("Updated quotation rows: {$updated}");

        return Command::SUCCESS;
    }

    /**
     * @return array<string, int>
     */
    private function legacyStatusCounts(): array
    {
        return DB::table('quotations')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereIn('status', array_keys(self::STATUS_MAP))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
}
