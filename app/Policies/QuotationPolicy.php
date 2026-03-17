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
        return $quotation->isCreator($user);
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $quotation->isCreator($user);
    }
}
