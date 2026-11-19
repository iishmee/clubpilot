<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\Membership;
use App\Models\User;

class MembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isUniversityAdmin();
    }

    public function view(User $user, Membership $membership): bool
    {
        return $user->isUniversityAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    public function invite(User $user, Club $club): bool
    {
        return $user->isUniversityAdmin();
    }

    public function acceptInvitation(
        User $user,
        Membership $membership
    ): bool {
        return $user->isStudent()
            && (int) $membership->user_id === (int) $user->id
            && $membership->status === Membership::STATUS_INVITED;
    }

    public function approve(User $user, Membership $membership): bool
    {
        return $user->isUniversityAdmin()
            && $membership->status === Membership::STATUS_PENDING;
    }

    public function reject(User $user, Membership $membership): bool
    {
        return $user->isUniversityAdmin()
            && $membership->status === Membership::STATUS_PENDING;
    }

    public function deactivate(User $user, Membership $membership): bool
    {
        return $user->isUniversityAdmin()
            && $membership->status === Membership::STATUS_ACTIVE;
    }

    public function graduate(User $user, Membership $membership): bool
    {
        return $user->isUniversityAdmin()
            && in_array($membership->status, [
                Membership::STATUS_ACTIVE,
                Membership::STATUS_INACTIVE,
            ], true);
    }

    public function reactivate(User $user, Membership $membership): bool
    {
        return $user->isUniversityAdmin()
            && in_array($membership->status, [
                Membership::STATUS_INACTIVE,
                Membership::STATUS_GRADUATED,
            ], true);
    }

    public function manageMembers(User $user): bool
    {
        return $user->isUniversityAdmin();
    }

    public function canManageClub(User $user, Club $club): bool
    {
        return $user->isUniversityAdmin();
    }
}
