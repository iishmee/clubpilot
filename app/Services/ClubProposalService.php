<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubProposal;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClubProposalService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function submit(User $student, array $data): ClubProposal
    {
        $constitution = $data['constitution'] ?? null;
        unset($data['constitution']);

        $constitutionPath = null;

        if ($constitution instanceof UploadedFile) {
            $constitutionPath = $constitution->store('club-constitutions', 'local');

            if (! $constitutionPath) {
                throw ValidationException::withMessages([
                    'constitution' => 'The constitution file could not be stored. Please try again.',
                ]);
            }
        }

        try {
            return DB::transaction(fn () => ClubProposal::query()->create([
                'user_id' => $student->getKey(),
                'name' => $data['name'],
                'details' => $data['details'],
                'objectives' => $data['objectives'],
                'constitution_path' => $constitutionPath,
                'status' => ClubProposal::STATUS_PENDING,
            ]));
        } catch (Throwable $exception) {
            if ($constitutionPath !== null) {
                Storage::disk('local')->delete($constitutionPath);
            }

            throw $exception;
        }
    }

    public function approve(ClubProposal $proposal, User $administrator): Club
    {
        return DB::transaction(function () use ($proposal, $administrator): Club {
            $lockedProposal = ClubProposal::query()
                ->whereKey($proposal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePending($lockedProposal);

            $club = Club::query()->create([
                'club_proposal_id' => $lockedProposal->getKey(),
                'name' => $lockedProposal->name,
                'details' => $lockedProposal->details,
                'objectives' => $lockedProposal->objectives,
            ]);

            $fromState = $lockedProposal->status;
            $lockedProposal->update(['status' => ClubProposal::STATUS_APPROVED]);

            $this->auditService->record(
                $administrator,
                $lockedProposal,
                $fromState,
                ClubProposal::STATUS_APPROVED,
            );

            return $club;
        });
    }

    public function reject(
        ClubProposal $proposal,
        User $administrator,
        ?string $reason = null,
    ): ClubProposal {
        return DB::transaction(function () use ($proposal, $administrator, $reason): ClubProposal {
            $lockedProposal = ClubProposal::query()
                ->whereKey($proposal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePending($lockedProposal);

            $fromState = $lockedProposal->status;
            $lockedProposal->update(['status' => ClubProposal::STATUS_REJECTED]);

            $this->auditService->record(
                $administrator,
                $lockedProposal,
                $fromState,
                ClubProposal::STATUS_REJECTED,
                $reason,
            );

            return $lockedProposal->fresh();
        });
    }

    private function ensurePending(ClubProposal $proposal): void
    {
        if ($proposal->status !== ClubProposal::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'This proposal has already been reviewed.',
            ]);
        }
    }
}
