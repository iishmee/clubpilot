<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VolunteerOpportunity;
use App\Models\VolunteerTask;

class VolunteerTaskPolicy
{
    /** See the task list of an opportunity: its organizer or an admin. */
    public function viewAny(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $user->isAdmin() || $this->owns($user, $opportunity);
    }

    /** Create tasks: only the organizer who owns the event. */
    public function create(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $this->owns($user, $opportunity);
    }

    /** Re-assign or delete a task. */
    public function manage(User $user, VolunteerTask $task): bool
    {
        return $task->opportunity !== null && $this->owns($user, $task->opportunity);
    }

    /** Change a task's status: the person it is assigned to, or the organizer. */
    public function updateStatus(User $user, VolunteerTask $task): bool
    {
        return ($task->assigned_to !== null && $task->assigned_to === $user->id)
            || $this->manage($user, $task);
    }

    private function owns(User $user, VolunteerOpportunity $opportunity): bool
    {
        return $user->isOrganizer() && $opportunity->event?->organizer_id === $user->id;
    }
}
