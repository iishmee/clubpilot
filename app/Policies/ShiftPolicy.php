<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;
use App\Models\VolunteerOpportunity;

class ShiftPolicy
{
    /** View the shift schedule of an opportunity: its organizer or an admin. */
    public function viewAny(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $user->isAdmin() || $this->owns($user, $opportunity);
    }

    /** Create shifts: only the organizer who owns the event. */
    public function create(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $this->owns($user, $opportunity);
    }

    /** Assign/remove volunteers or delete a shift. */
    public function manage(User $user, Shift $shift): bool
    {
        return $shift->opportunity !== null && $this->owns($user, $shift->opportunity);
    }

    private function owns(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $user->isOrganizer() && $opportunity->event?->organizer_id === $user->id;
    }
}
