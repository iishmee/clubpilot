<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\Equipment;
use App\Models\Membership;
use App\Models\User;

class EquipmentPolicy
{
    /**
     * Determine who can access the equipment directory.
     */
    public function viewAny(User $user): bool
    {
        return $user->isUniversityAdmin()
            || $user->isStudent()
            || $user->clubRoles()
                ->where('role', 'officer')
                ->exists();
    }

    /**
     * Determine who can view individual equipment.
     */
    public function view(User $user, Equipment $equipment): bool
    {
        if ($user->isUniversityAdmin()) {
            return true;
        }

        // Assigned club officers can view their club equipment.
        if ($this->managesClub($user, $equipment->club_id)) {
            return true;
        }

        // Students can view active equipment in clubs
        // where they have active memberships.
        return $user->isStudent()
            && $equipment->is_active
            && $user->memberships()
                ->where('club_id', $equipment->club_id)
                ->where('status', Membership::STATUS_ACTIVE)
                ->exists();
    }

    /**
     * Determine who can add equipment to a club.
     */
    public function create(User $user, Club $club): bool
    {
        return $user->isUniversityAdmin()
            || $this->managesClub($user, $club->id);
    }

    /**
     * Determine who can update equipment.
     */
    public function update(User $user, Equipment $equipment): bool
    {
        return $user->isUniversityAdmin()
            || $this->managesClub($user, $equipment->club_id);
    }

    /**
     * Determine who can deactivate equipment.
     */
    public function deactivate(User $user, Equipment $equipment): bool
    {
        return $equipment->is_active
            && (
                $user->isUniversityAdmin()
                || $this->managesClub($user, $equipment->club_id)
            );
    }

    /**
     * Determine who can reactivate equipment.
     */
    public function activate(User $user, Equipment $equipment): bool
    {
        return !$equipment->is_active
            && (
                $user->isUniversityAdmin()
                || $this->managesClub($user, $equipment->club_id)
            );
    }

    /**
     * Check whether a user is an officer of a particular club.
     */
    private function managesClub(User $user, int $clubId): bool
    {
        return $user->clubRoles()
            ->where('club_id', $clubId)
            ->where('role', 'officer')
            ->exists();
    }
}
