<?php

namespace App\Services\Quotation;

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @deprecated Use App\Services\QuotationValidationService for the UI validation flow.
 * This compatibility service is retained for workflow-level sync tests and should
 * continue delegating status decisions to QuotationStatusService.
 */
class QuotationValidationService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_VALID = 'valid';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_NEEDS_REVALIDATION = 'needs_revalidation';

    public function calculateProgress(Quotation $quotation): array
    {
        $itemsQuery = $quotation->items();
        $totalItems = (int) (clone $itemsQuery)->count();
        $requiredQuery = (clone $itemsQuery)->where('is_validation_required', true);
        $requiredItems = (int) (clone $requiredQuery)->count();
        $validItems = (int) (clone $requiredQuery)->where('is_validated', true)->count();

        return [
            'total_items' => $totalItems,
            'valid_items' => $validItems,
            'required_items' => $requiredItems,
            'pending_items' => max($requiredItems - $validItems, 0),
            'is_valid' => $requiredItems === 0 || $validItems >= $requiredItems,
            'validation_status' => $this->determineValidationStatus($quotation, $requiredItems, $validItems),
        ];
    }

    public function syncValidationStatus(Quotation $quotation, ?int $actorId = null): array
    {
        return DB::transaction(function () use ($quotation, $actorId): array {
            $quotation->refresh();
            $progress = $this->calculateProgress($quotation);
            $status = (string) $progress['validation_status'];

            $patch = [];
            if (Schema::hasColumn('quotations', 'validation_status')) {
                $patch['validation_status'] = $status;
            }

            if ($status === self::STATUS_VALID) {
                if (Schema::hasColumn('quotations', 'validated_at')) {
                    $patch['validated_at'] = now();
                }
                if ($actorId !== null && Schema::hasColumn('quotations', 'validated_by')) {
                    $patch['validated_by'] = $this->validUserId($actorId);
                }
            } else {
                if (Schema::hasColumn('quotations', 'validated_at')) {
                    $patch['validated_at'] = null;
                }
                if (Schema::hasColumn('quotations', 'validated_by')) {
                    $patch['validated_by'] = null;
                }
            }

            if ($patch !== []) {
                DB::table('quotations')->where('id', (int) $quotation->id)->update($patch);
                $quotation->refresh();
            }

            app(QuotationStatusService::class)->syncStatus($quotation);
            $quotation->refresh();

            return $progress;
        });
    }

    public function markItemsNeedValidation(Quotation $quotation, ?string $reason = null): int
    {
        $quotation->loadMissing('items');
        $updated = 0;

        foreach ($quotation->items as $item) {
            if (! (bool) ($item->is_validation_required ?? false)) {
                continue;
            }

            $item->forceFill([
                'is_validated' => false,
                'validated_at' => null,
                'validated_by' => null,
                'validation_notes' => $reason ?: $item->validation_notes,
            ])->save();
            $updated++;
        }

        $this->syncValidationStatus($quotation);

        return $updated;
    }

    private function determineValidationStatus(Quotation $quotation, int $requiredItems, int $validItems): string
    {
        $current = strtolower(trim((string) ($quotation->validation_status ?? '')));
        if ($current === self::STATUS_NEEDS_REVALIDATION) {
            return self::STATUS_NEEDS_REVALIDATION;
        }

        if ($requiredItems <= 0 || $validItems >= $requiredItems) {
            return self::STATUS_VALID;
        }

        return $validItems > 0 ? self::STATUS_PARTIAL : self::STATUS_PENDING;
    }

    private function validUserId(?int $userId): ?int
    {
        if (! $userId || ! Schema::hasTable('users')) {
            return null;
        }

        return DB::table('users')->where('id', $userId)->exists() ? $userId : null;
    }
}
