<?php

namespace App\Console\Commands;

use App\Models\Inquiry;
use App\Models\Quotation;
use Illuminate\Console\Command;

class ResyncInquiryStatusFromQuotations extends Command
{
    protected $signature = 'inquiries:resync-status-from-quotations
                            {--dry-run : Preview changes without updating data}
                            {--chunk=200 : Chunk size for processing inquiries}';

    protected $description = 'Resync inquiry status from linked quotation lifecycle (final/processed).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(50, (int) $this->option('chunk'));

        $processed = 0;
        $updated = 0;

        Inquiry::query()
            ->select(['id', 'status'])
            ->where(function ($query): void {
                $query->whereHas('quotation', function ($quotationQuery): void {
                    $quotationQuery->whereNull('deleted_at');
                })->orWhereHas('itineraries.quotation', function ($quotationQuery): void {
                    $quotationQuery->whereNull('deleted_at');
                });
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function ($inquiries) use (&$processed, &$updated, $dryRun): void {
                foreach ($inquiries as $inquiry) {
                    $processed++;

                    $targetStatus = $this->resolveTargetStatus((int) $inquiry->id);
                    if ((string) ($inquiry->status ?? '') === $targetStatus) {
                        continue;
                    }

                    $updated++;
                    if ($dryRun) {
                        $this->line("INQ #{$inquiry->id}: {$inquiry->status} -> {$targetStatus}");
                        continue;
                    }

                    $inquiry->update([
                        'status' => $targetStatus,
                    ]);
                }
            });

        if ($dryRun) {
            $this->warn('Dry-run mode: no database update was performed.');
        }

        $this->info("Processed inquiries: {$processed}");
        $this->info("Updated inquiries: {$updated}");

        return Command::SUCCESS;
    }

    private function resolveTargetStatus(int $inquiryId): string
    {
        $hasFinalQuotation = Quotation::query()
            ->whereNull('deleted_at')
            ->where('status', Quotation::FINAL_STATUS)
            ->where(function ($query) use ($inquiryId): void {
                $query->where('inquiry_id', $inquiryId)
                    ->orWhereHas('itinerary', function ($itineraryQuery) use ($inquiryId): void {
                        $itineraryQuery->where('inquiry_id', $inquiryId);
                    });
            })
            ->exists();

        return $hasFinalQuotation ? Inquiry::FINAL_STATUS : 'processed';
    }
}

