<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Schema;

class InquiryPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Inquiry $inquiry): bool
    {
        if (! $user->can('module.inquiries.update')) {
            return false;
        }
        if (! $user->hasAnyRole(['Reservation', 'Manager', 'Director'])) {
            return false;
        }

        $handlerId = null;
        if (Schema::hasColumn($inquiry->getTable(), 'handled_by')) {
            $handlerId = (int) ($inquiry->handled_by ?? 0);
        }
        if (Schema::hasColumn($inquiry->getTable(), 'assigned_to')) {
            if (! $handlerId) {
                $handlerId = (int) ($inquiry->assigned_to ?? 0);
            }
        }
        if (! $handlerId && Schema::hasColumn($inquiry->getTable(), 'created_by')) {
            $handlerId = (int) ($inquiry->created_by ?? 0);
        }

        return $handlerId > 0 && $handlerId === (int) $user->id;
    }

    public function delete(User $user, Inquiry $inquiry): bool
    {
        return $user->can('module.inquiries.delete') && $this->update($user, $inquiry);
    }
}
