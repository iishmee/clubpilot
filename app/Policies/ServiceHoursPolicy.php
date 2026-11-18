<?php

namespace App\Policies;

use App\Models\ServiceEntry;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;

class ServiceHoursPolicy
{
    /** Everyone logged in can open the page; the controller limits which rows they see. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServiceEntry $entry): bool
    {
        return $user->isAdmin()
            || $entry->user_id === $user->id
            || $this->review($user, $entry);
    }

    /** Only members can log hours (organizers and admins review them). */
    public function create(User $user): bool
    {
        return $user->role === 'member';
    }

    /** A member can log hours only for an opportunity where they were ACCEPTED. */
    public function log(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $user->role === 'member'
            && VolunteerApplication::query()
                ->where('volunteer_opportunity_id', $opportunity->id)
                ->where('user_id', $user->id)
                ->where('status', VolunteerApplication::STATUS_ACCEPTED)
                ->exists();
    }

    /** Only the organizer who owns the event can approve or reject the hours. */
    public function review(User $user, ServiceEntry $entry): bool
    {
        return $user->isOrganizer()
            && $entry->opportunity?->event?->organizer_id === $user->id;
    }
}
