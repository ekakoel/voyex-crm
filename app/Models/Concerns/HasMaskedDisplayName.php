<?php

namespace App\Models\Concerns;

use App\Models\User;

trait HasMaskedDisplayName
{
    public function isSuperAdmin(): bool
    {
        $email = mb_strtolower(trim((string) ($this->email ?? '')));
        if ($email === 'superadmin@example.com') {
            return true;
        }

        return $this->hasRole('Super Admin');
    }

    public function displayNameFor(?User $viewer = null, string $maskedLabel = 'System'): string
    {
        if ($this->isSuperAdmin() && ! ($viewer?->isSuperAdmin())) {
            return $maskedLabel;
        }

        return (string) ($this->name ?? '');
    }
}
