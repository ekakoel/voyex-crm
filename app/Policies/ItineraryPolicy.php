<?php

namespace App\Policies;

use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ItineraryPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Itinerary $itinerary): bool
    {
        return $user->can('module.itineraries.update')
            && $itinerary->isCreator($user);
    }

    public function delete(User $user, Itinerary $itinerary): bool
    {
        return $user->can('module.itineraries.delete');
    }
}
