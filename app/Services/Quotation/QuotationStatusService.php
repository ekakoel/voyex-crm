<?php

namespace App\Services\Quotation;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Support\Workflow\QuotationStatusNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuotationStatusService
{
    public const VALIDATION_PENDING = 'pending';
    public const VALIDATION_PARTIAL = 'partial';
    public const VALIDATION_VALIDATED = 'validated';

    public function syncStatus(Quotation $quotation): void
    {
        $quotation->refresh();
        $progress = $this->validationProgress($quotation);
        $logicalStatus = QuotationStatusNormalizer::normalize((string) ($quotation->status ?? ''));

        $patch = [
            'validation_status' => $progress['validation_status'],
        ];

        if (Schema::hasColumn('quotations', 'validation_progress')) {
            $patch['validation_progress'] = $progress['percent'];
        }

        if (! QuotationStatusNormalizer::isFinal((string) ($quotation->status ?? ''))
            && $logicalStatus !== 'sent') {
            if ($this->hasPendingRevisionResponse($quotation)) {
                $patch['status'] = QuotationStatus::RevisionRequested->value;
            } else {
                $patch['status'] = $progress['percent'] >= 100
                ? QuotationStatus::ReadyToSend->value
                : ($this->isRevisionQuotation($quotation)
                    ? QuotationStatus::NeedRevalidation->value
                    : QuotationStatus::NeedValidation->value);
            }
        }

        $safePatch = [];
        foreach ($patch as $column => $value) {
            if (Schema::hasColumn('quotations', $column)) {
                $safePatch[$column] = $value;
            }
        }

        if ($safePatch !== []) {
            DB::table('quotations')->where('id', (int) $quotation->id)->update($safePatch);
            $quotation->refresh();
        }
    }

    public function validationProgress(Quotation $quotation): array
    {
        $required = $this->requiredItemsQuery($quotation)->count();
        $validated = $this->requiredItemsQuery($quotation)
            ->where(function ($query): void {
                $query->where('is_validated', true);
                if (Schema::hasColumn('quotation_items', 'validation_status')) {
                    $query->orWhere('validation_status', self::VALIDATION_VALIDATED);
                }
            })
            ->count();

        $percent = $required > 0
            ? (int) min(100, round(($validated / $required) * 100))
            : 100;

        return [
            'required' => (int) $required,
            'validated' => (int) $validated,
            'percent' => $percent,
            'is_complete' => $percent >= 100,
            'validation_status' => $this->validationStatusForPercent($percent),
        ];
    }

    public function assertReadyToSend(Quotation $quotation): void
    {
        $progress = $this->validationProgress($quotation);
        if (! (bool) ($progress['is_complete'] ?? false)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Quotation cannot be marked as ready to send because validation is not 100% complete.',
            ]);
        }
        if ($this->hasPendingRevisionResponse($quotation)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Quotation cannot be marked as ready to send because there are unhandled customer revision responses.',
            ]);
        }
    }

    public function hasPendingRevisionResponse(Quotation $quotation): bool
    {
        if (! Schema::hasTable('quotation_customer_responses')) {
            return false;
        }

        return DB::table('quotation_customer_responses')
            ->where('quotation_id', (int) $quotation->id)
            ->where('requires_revision', true)
            ->where(function ($query): void {
                $query->where('is_used_for_revision', false)
                    ->orWhereNull('is_used_for_revision');
            })
            ->exists();
    }

    private function validationStatusForPercent(int $percent): string
    {
        if ($percent >= 100) {
            return self::VALIDATION_VALIDATED;
        }

        return $percent > 0 ? self::VALIDATION_PARTIAL : self::VALIDATION_PENDING;
    }

    private function requiredItemsQuery(Quotation $quotation)
    {
        $query = DB::table('quotation_items')->where('quotation_id', (int) $quotation->id);

        $hasValidationRequired = Schema::hasColumn('quotation_items', 'validation_required');
        $hasLegacyValidationRequired = Schema::hasColumn('quotation_items', 'is_validation_required');

        if ($hasValidationRequired && $hasLegacyValidationRequired) {
            return $query->where(function ($builder): void {
                $builder->where('validation_required', true)
                    ->orWhere('is_validation_required', true);
            });
        }

        if ($hasValidationRequired) {
            return $query->where('validation_required', true);
        }

        return $query->where('is_validation_required', true);
    }

    private function isRevisionQuotation(Quotation $quotation): bool
    {
        return (int) ($quotation->revision_number ?? 1) > 1
            || (int) ($quotation->revision_of_id ?? 0) > 0
            || QuotationStatusNormalizer::normalize((string) ($quotation->status ?? '')) === 'need_revalidation';
    }
}
