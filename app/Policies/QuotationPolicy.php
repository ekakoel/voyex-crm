<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Schema;

class QuotationPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Quotation $quotation): bool
    {
        if (! $user->can('module.quotations.update')) {
            return false;
        }

        return $this->isQuotationHandler($user, $quotation);
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        if (! $user->can('module.quotations.delete')) {
            return false;
        }

        return $this->update($user, $quotation);
    }

    public function validateQuotation(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.validate')
            && $this->isQuotationHandler($user, $quotation)
            && ! $quotation->isStatus(
                Quotation::STATUS_SENT,
                Quotation::STATUS_CUSTOMER_APPROVED,
                Quotation::FINAL_STATUS,
                Quotation::STATUS_IN_OPERATION,
                Quotation::STATUS_COMPLETED
            );
    }

    private function isQuotationHandler(User $user, Quotation $quotation): bool
    {
        if (Schema::hasColumn($quotation->getTable(), 'created_by')
            && (int) ($quotation->created_by ?? 0) === (int) $user->id) {
            return true;
        }

        $handlerId = 0;
        if (Schema::hasColumn($quotation->getTable(), 'handled_by')) {
            $handlerId = (int) ($quotation->handled_by ?? 0);
        }

        $inquiry = $quotation->inquiry;
        if ($handlerId <= 0 && $inquiry && Schema::hasColumn($inquiry->getTable(), 'handled_by')) {
            $handlerId = (int) ($inquiry->handled_by ?? 0);
        }
        if ($handlerId <= 0 && $inquiry && Schema::hasColumn($inquiry->getTable(), 'assigned_to')) {
            $handlerId = (int) ($inquiry->assigned_to ?? 0);
        }
        if ($handlerId <= 0 && Schema::hasColumn($quotation->getTable(), 'created_by')) {
            $handlerId = (int) ($quotation->created_by ?? 0);
        }
        if ($handlerId <= 0 && $inquiry && Schema::hasColumn($inquiry->getTable(), 'created_by')) {
            $handlerId = (int) ($inquiry->created_by ?? 0);
        }

        return $handlerId > 0 && $handlerId === (int) $user->id;
    }
}
