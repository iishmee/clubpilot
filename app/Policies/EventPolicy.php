<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOrganizer();
    }

    public function view(User $user, Event $event): bool
    {
        return $user->isAdmin() || ($user->isOrganizer() && $event->organizer_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isOrganizer()
            && $event->organizer_id === $user->id
            && in_array($event->proposal_status, [Event::STATUS_DRAFT, Event::STATUS_REVISION_REQUESTED], true);
    }

    public function submit(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function review(User $user): bool
    {
        return $user->isAdmin();
    }
}
