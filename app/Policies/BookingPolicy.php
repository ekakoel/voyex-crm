<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Booking $booking): bool
    {
        return $user->can('module.bookings.update')
            && $booking->isCreator($user);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->can('module.bookings.delete')
            && $booking->isCreator($user);
    }
}
