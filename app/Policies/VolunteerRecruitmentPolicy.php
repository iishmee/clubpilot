<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerOpportunity;

class VolunteerRecruitmentPolicy
{
    /** Everyone who is logged in can browse opportunities. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $user->isAdmin()
            || $user->role === 'member'
            || $this->manage($user, $opportunity);
    }

    /** Only the organizer who owns an APPROVED event can recruit for it. */
    public function create(User $user, Event $event): bool
    {
        return $user->isOrganizer()
            && $event->organizer_id === $user->id
            && $event->proposal_status === Event::STATUS_APPROVED;
    }

    /** Close the opportunity / accept or reject applicants. */
    public function manage(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $user->isOrganizer() && $opportunity->event?->organizer_id === $user->id;
    }

    /** Members may apply while the opportunity is open. */
    public function apply(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $user->role === 'member'
            && $opportunity->status === VolunteerOpportunity::STATUS_OPEN;
    }
}
