<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Schema;

class BookingPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Booking $booking): bool
    {
        if (! $user->can('module.bookings.update')) {
            return false;
        }

        $inquiry = $booking->quotation?->inquiry;
        if (! $inquiry) {
            return $booking->isCreator($user);
        }

        $handlerId = 0;
        if (Schema::hasColumn($inquiry->getTable(), 'handled_by')) {
            $handlerId = (int) ($inquiry->handled_by ?? 0);
        }
        if ($handlerId <= 0 && Schema::hasColumn($inquiry->getTable(), 'assigned_to')) {
            $handlerId = (int) ($inquiry->assigned_to ?? 0);
        }
        if ($handlerId <= 0 && Schema::hasColumn($inquiry->getTable(), 'created_by')) {
            $handlerId = (int) ($inquiry->created_by ?? 0);
        }

        if ($handlerId > 0) {
            return $handlerId === (int) $user->id;
        }

        return $booking->isCreator($user);
    }

    public function delete(User $user, Booking $booking): bool
    {
        if (! $user->can('module.bookings.delete')) {
            return false;
        }

        return $this->update($user, $booking);
    }
}
