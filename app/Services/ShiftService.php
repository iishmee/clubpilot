<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    /**
     * @param  array{starts_at: string, ends_at: string, capacity: int}  $data
     */
    public function createShift(VolunteerOpportunity $opportunity, array $data): Shift
    {
        return Shift::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'title' => $data['title'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'capacity' => $data['capacity'],
        ]);
    }

    public function assign(Shift $shift, int $userId): ShiftAssignment
    {
        return DB::transaction(function () use ($shift, $userId): ShiftAssignment {
            // Lock the volunteer and the shift so two requests can't double-book.
            User::query()->lockForUpdate()->findOrFail($userId);
            $locked = Shift::query()->lockForUpdate()->findOrFail($shift->id);

            $isAccepted = VolunteerApplication::query()
                ->where('volunteer_opportunity_id', $locked->volunteer_opportunity_id)
                ->where('user_id', $userId)
                ->where('status', VolunteerApplication::STATUS_ACCEPTED)
                ->exists();

            if (! $isAccepted) {
                throw ValidationException::withMessages([
                    'user_id' => 'Only accepted volunteers of this opportunity can be assigned.',
                ]);
            }

            if ($locked->assignments()->where('user_id', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'user_id' => 'This volunteer is already assigned to this shift.',
                ]);
            }

            if ($locked->assignments()->count() >= $locked->capacity) {
                throw ValidationException::withMessages([
                    'user_id' => 'This shift is already full.',
                ]);
            }

            // Overlap rule: existing.start < new.end AND existing.end > new.start.
            // Shifts that only touch (one ends exactly when the next starts) are allowed.
            $hasConflict = ShiftAssignment::query()
                ->where('user_id', $userId)
                ->whereHas('shift', fn ($query) => $query
                    ->where('starts_at', '<', $locked->ends_at)
                    ->where('ends_at', '>', $locked->starts_at))
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'user_id' => 'This volunteer already has another shift that overlaps this time.',
                ]);
            }

            return ShiftAssignment::query()->create([
                'shift_id' => $locked->id,
                'user_id' => $userId,
            ]);
        });
    }

    public function unassign(ShiftAssignment $assignment): void
    {
        $assignment->delete();
    }

    public function deleteShift(Shift $shift): void
    {
        if ($shift->assignments()->exists()) {
            throw ValidationException::withMessages([
                'shift' => 'Remove the assigned volunteers before deleting this shift.',
            ]);
        }

        $shift->delete();
    }
}
