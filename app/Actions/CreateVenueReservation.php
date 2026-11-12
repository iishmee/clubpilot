<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateVenueReservation
{
    public function handle(
        int $eventId,
        int $venueId,
        User $requester,
        string $startsAt,
        string $endsAt,
    ): Reservation {
        $startsAt = Carbon::parse($startsAt)->toDateTimeString();
        $endsAt = Carbon::parse($endsAt)->toDateTimeString();

        return DB::transaction(function () use ($eventId, $venueId, $requester, $startsAt, $endsAt): Reservation {
            $event = Event::query()->lockForUpdate()->findOrFail($eventId);
            Gate::forUser($requester)->authorize('create', [Reservation::class, $event]);

            $venue = Venue::query()->lockForUpdate()->findOrFail($venueId);

            if ($venue->availability_status !== 'available') {
                throw ValidationException::withMessages([
                    'venue_id' => 'This venue is currently unavailable. Choose an available venue.',
                ]);
            }

            $conflict = Reservation::query()
                ->where('venue_id', $venue->id)
                ->whereIn('status', [Reservation::STATUS_PENDING, Reservation::STATUS_APPROVED])
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'venue_id' => 'This venue already has a pending or approved reservation during that time. Choose another venue or time slot.',
                ]);
            }

            return Reservation::query()->create([
                'event_id' => $event->id,
                'venue_id' => $venue->id,
                'requester_id' => $requester->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => Reservation::STATUS_PENDING,
            ]);
        });
    }
}
