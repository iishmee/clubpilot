<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipService
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    public function submitApplication(User $student, array $data): Membership
    {
        return DB::transaction(function () use ($student, $data) {

            $existing = Membership::query()
                ->where('club_id', $data['club_id'])
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status !== Membership::STATUS_REJECTED) {
                throw ValidationException::withMessages([
                    'club_id' => 'You already have a membership record for this club.',
                ]);
            }

            $attributes = [
                'club_id' => $data['club_id'],
                'user_id' => $student->id,
                'student_id' => $data['student_id'],
                'department' => $data['department'],
                'semester' => $data['semester'],
                'application_message' => $data['application_message'] ?? null,
                'status' => Membership::STATUS_PENDING,
                'applied_at' => now(),
                'invited_by' => null,
                'invited_at' => null,
                'joined_at' => null,
                'ended_at' => null,
                'status_reason' => null,
            ];

            if ($existing) {
                $fromState = $existing->status;
                $existing->update($attributes);
                $membership = $existing;
            } else {
                $fromState = null;
                $membership = Membership::create($attributes);
            }

            $this->auditService->record(
                $student,
                $membership,
                $fromState,
                Membership::STATUS_PENDING,
                'Membership application submitted.'
            );

            return $membership;
        });
    }

    public function inviteStudent(
        Club $club,
        User $student,
        User $actor
    ): Membership {

        return DB::transaction(function () use ($club, $student, $actor) {

            $membership = Membership::query()
                ->where('club_id', $club->id)
                ->where('user_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($membership && in_array($membership->status, [
                Membership::STATUS_ACTIVE,
                Membership::STATUS_GRADUATED,
            ], true)) {
                throw ValidationException::withMessages([
                    'user_id' => 'This student already has an active or graduated membership.',
                ]);
            }

            $fromState = $membership?->status;

            $attributes = [
                'club_id' => $club->id,
                'user_id' => $student->id,
                'invited_by' => $actor->id,
                'status' => Membership::STATUS_INVITED,
                'invited_at' => now(),
                'status_reason' => null,
                'ended_at' => null,
            ];

            if ($membership) {
                $membership->update($attributes);
            } else {
                $membership = Membership::create($attributes);
            }

            $this->auditService->record(
                $actor,
                $membership,
                $fromState,
                Membership::STATUS_INVITED,
                'Student invited to join the club.'
            );

            return $membership;
        });
    }

    public function changeStatus(
        Membership $membership,
        User $actor,
        string $newStatus,
        ?string $reason = null
    ): Membership {

        return DB::transaction(function () use (
            $membership,
            $actor,
            $newStatus,
            $reason
        ) {

            $membership = Membership::query()
                ->lockForUpdate()
                ->findOrFail($membership->id);

            $fromState = $membership->status;

            $membership->status = $newStatus;
            $membership->status_reason = $reason;

            if ($newStatus === Membership::STATUS_ACTIVE) {
                $membership->joined_at ??= now();
                $membership->ended_at = null;
            }

            if (in_array($newStatus, [
                Membership::STATUS_INACTIVE,
                Membership::STATUS_GRADUATED,
                Membership::STATUS_REJECTED,
            ], true)) {
                $membership->ended_at = now();
            }

            $membership->save();

            $this->auditService->record(
                $actor,
                $membership,
                $fromState,
                $newStatus,
                $reason
            );

            return $membership;
        });
    }
}
