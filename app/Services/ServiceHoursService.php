<?php

namespace App\Services;

use App\Models\ServiceEntry;
use App\Models\User;
use App\Models\VolunteerOpportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ServiceHoursService
{
    /**
     * @param  array{hours: numeric, worked_on: string, description?: ?string}  $data
     */
    public function log(User $user, VolunteerOpportunity $opportunity, array $data): ServiceEntry
    {
        Gate::forUser($user)->authorize('log', [ServiceEntry::class, $opportunity]);

        return ServiceEntry::query()->create([
            'user_id' => $user->id,
            'volunteer_opportunity_id' => $opportunity->id,
            'hours' => round((float) $data['hours'], 2),
            'worked_on' => $data['worked_on'],
            'description' => $data['description'] ?? null,
            'status' => ServiceEntry::STATUS_PENDING,
        ]);
    }

    public function review(ServiceEntry $entry, User $reviewer, string $decision, ?string $note): ServiceEntry
    {
        return DB::transaction(function () use ($entry, $reviewer, $decision, $note): ServiceEntry {
            $locked = ServiceEntry::query()->lockForUpdate()->findOrFail($entry->id);

            Gate::forUser($reviewer)->authorize('review', $locked);

            abort_unless(
                $locked->status === ServiceEntry::STATUS_PENDING,
                409,
                'Only pending hours can be reviewed.',
            );

            if ($decision === ServiceEntry::STATUS_REJECTED && blank($note)) {
                throw ValidationException::withMessages([
                    'review_note' => 'Please give a reason when rejecting hours.',
                ]);
            }

            $locked->update([
                'status' => $decision,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => filled($note) ? $note : null,
            ]);

            return $locked;
        });
    }

    /** Total APPROVED hours for a volunteer (optionally for one opportunity only). */
    public function approvedHours(User $user, ?VolunteerOpportunity $opportunity = null): float
    {
        return $this->sumHours($user, ServiceEntry::STATUS_APPROVED, $opportunity);
    }

    /** Total hours still waiting for review. */
    public function pendingHours(User $user): float
    {
        return $this->sumHours($user, ServiceEntry::STATUS_PENDING, null);
    }

    private function sumHours(User $user, string $status, ?VolunteerOpportunity $opportunity): float
    {
        $query = ServiceEntry::query()
            ->where('user_id', $user->id)
            ->where('status', $status);

        if ($opportunity !== null) {
            $query->where('volunteer_opportunity_id', $opportunity->id);
        }

        return round((float) $query->sum('hours'), 2);
    }
}
