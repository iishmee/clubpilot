<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VolunteerRecruitmentService
{
    /**
     * @param  array{title: string, description?: ?string, slots_needed: int}  $data
     */
    public function createOpportunity(Event $event, User $organizer, array $data): VolunteerOpportunity
    {
        Gate::forUser($organizer)->authorize('create', [VolunteerOpportunity::class, $event]);

        return VolunteerOpportunity::query()->create([
            'event_id' => $event->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'slots_needed' => $data['slots_needed'],
            'status' => VolunteerOpportunity::STATUS_OPEN,
        ]);
    }

    public function apply(VolunteerOpportunity $opportunity, User $user, ?string $message): VolunteerApplication
    {
        return DB::transaction(function () use ($opportunity, $user, $message): VolunteerApplication {
            // Lock the row so two people can't grab the last slot at the same moment.
            $locked = VolunteerOpportunity::query()->lockForUpdate()->findOrFail($opportunity->id);
            Gate::forUser($user)->authorize('apply', $locked);

            if ($locked->isFull()) {
                throw ValidationException::withMessages([
                    'message' => 'All volunteer slots for this opportunity are already filled.',
                ]);
            }

            $alreadyApplied = VolunteerApplication::query()
                ->where('volunteer_opportunity_id', $locked->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyApplied) {
                throw ValidationException::withMessages([
                    'message' => 'You have already applied to this opportunity.',
                ]);
            }

            return VolunteerApplication::query()->create([
                'volunteer_opportunity_id' => $locked->id,
                'user_id' => $user->id,
                'message' => $message,
                'status' => VolunteerApplication::STATUS_PENDING,
            ]);
        });
    }

    public function review(VolunteerApplication $application, string $decision): VolunteerApplication
    {
        return DB::transaction(function () use ($application, $decision): VolunteerApplication {
            $locked = VolunteerApplication::query()->lockForUpdate()->findOrFail($application->id);

            abort_unless(
                $locked->status === VolunteerApplication::STATUS_PENDING,
                409,
                'Only pending applications can be reviewed.',
            );

            if ($decision === VolunteerApplication::STATUS_ACCEPTED) {
                $opportunity = VolunteerOpportunity::query()
                    ->lockForUpdate()
                    ->findOrFail($locked->volunteer_opportunity_id);

                if ($opportunity->isFull()) {
                    throw ValidationException::withMessages([
                        'decision' => 'All volunteer slots are already filled.',
                    ]);
                }
            }

            $locked->update(['status' => $decision, 'reviewed_at' => now()]);

            return $locked;
        });
    }

    public function close(VolunteerOpportunity $opportunity): void
    {
        $opportunity->update(['status' => VolunteerOpportunity::STATUS_CLOSED]);
    }
}
