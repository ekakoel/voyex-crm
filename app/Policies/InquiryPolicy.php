<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InquiryPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Inquiry $inquiry): bool
    {
        if ($inquiry->isCreator($user)) {
            return true;
        }

        return (int) ($inquiry->assigned_to ?? 0) === (int) $user->id;
    }

    public function delete(User $user, Inquiry $inquiry): bool
    {
        return $user->hasRole('Super Admin');
    }
}
