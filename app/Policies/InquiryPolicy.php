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
        return $user->can('module.inquiries.update');
    }

    public function delete(User $user, Inquiry $inquiry): bool
    {
        return $user->can('module.inquiries.delete');
    }
}
