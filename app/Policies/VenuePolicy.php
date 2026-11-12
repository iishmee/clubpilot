<?php

namespace App\Policies;

use App\Models\User;

class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOrganizer();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }
}
