<?php

namespace App\Policies;

use App\Models\FeatureAccess;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FeatureAccessPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FeatureAccess $featureAccess): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FeatureAccess $featureAccess): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FeatureAccess $featureAccess): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FeatureAccess $featureAccess): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FeatureAccess $featureAccess): bool
    {
        //
    }
}
