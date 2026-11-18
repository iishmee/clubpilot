<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;
use App\Models\VolunteerOpportunity;

class CertificatePolicy
{
    /** Everyone logged in can open the list; the controller limits which rows they see. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** View/download: the volunteer, the event's organizer, or an admin. */
    public function view(User $user, Certificate $certificate): bool
    {
        return $user->isAdmin()
            || $certificate->user_id === $user->id
            || $this->issue($user, $certificate->opportunity);
    }

    /** Only the organizer who owns the event can issue certificates for it. */
    public function issue(User $user, ?VolunteerOpportunity $opportunity): bool
    {
        return $opportunity !== null
            && $user->isOrganizer()
            && $opportunity->event?->organizer_id === $user->id;
    }
}
