<?php

namespace App\Policies;

use App\Models\ClubProposal;
use App\Models\User;

class ClubProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStudent() || $user->isUniversityAdmin();
    }

    public function view(User $user, ClubProposal $clubProposal): bool
    {
        return $user->isUniversityAdmin()
            || ($user->isStudent() && (int) $clubProposal->user_id === (int) $user->getKey());
    }

    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    public function approve(User $user, ClubProposal $clubProposal): bool
    {
        return $user->isUniversityAdmin()
            && $clubProposal->status === ClubProposal::STATUS_PENDING;
    }

    public function reject(User $user, ClubProposal $clubProposal): bool
    {
        return $user->isUniversityAdmin()
            && $clubProposal->status === ClubProposal::STATUS_PENDING;
    }

    public function downloadConstitution(User $user, ClubProposal $clubProposal): bool
    {
        return $clubProposal->constitution_path !== null
            && ($user->isUniversityAdmin()
                || ($user->isStudent() && (int) $clubProposal->user_id === (int) $user->getKey()));
    }

    public function viewDirectory(User $user): bool
    {
        return $user->isStudent() || $user->isUniversityAdmin();
    }
}
