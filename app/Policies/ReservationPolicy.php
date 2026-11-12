<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOrganizer();
    }

    public function create(User $user, Event $event): bool
    {
        return $user->isOrganizer()
            && $event->organizer_id === $user->id
            && $event->proposal_status === Event::STATUS_APPROVED;
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin()
            || ($user->isOrganizer() && $reservation->event?->organizer_id === $user->id);
    }

    public function reviewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function review(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin() && $reservation->status === Reservation::STATUS_PENDING;
    }
}
