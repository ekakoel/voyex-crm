<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotationPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->can('module.quotations.update')
            && $quotation->isCreator($user);
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->can('module.quotations.delete')
            && $quotation->isCreator($user);
    }

    public function validateQuotation(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.validate')
            && ! $quotation->isFinal()
            && ! in_array((string) ($quotation->status ?? ''), ['approved'], true);
    }
}
