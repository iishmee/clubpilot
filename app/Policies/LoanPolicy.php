<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\Loan;
use App\Models\Membership;
use App\Models\User;

class LoanPolicy
{
    /**
     * Determine whether the user can view the loan dashboard.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStudent()
            || $user->isUniversityAdmin()
            || $user->clubRoles()
                ->where('role', 'officer')
                ->exists();
    }

    /**
     * Determine whether the user can view a particular loan.
     */
    public function view(User $user, Loan $loan): bool
    {
        $loan->loadMissing([
            'equipment',
            'membership',
        ]);

        if ($user->isUniversityAdmin()) {
            return true;
        }

        // Students can view their own loans only.
        if (
            $user->isStudent()
            && (int) $loan->membership->user_id === (int) $user->id
        ) {
            return true;
        }

        // Club officers can view loans for their assigned clubs.
        return $this->canManageLoan($user, $loan);
    }

    /**
     * Students can request equipment using their own
     * active membership in the equipment's club.
     */
    public function create(
        User $user,
        Equipment $equipment,
        Membership $membership
    ): bool {

        return $user->isStudent()
            && (bool) $equipment->is_active
            && $membership->status === Membership::STATUS_ACTIVE
            && (int) $membership->user_id === (int) $user->id
            && (int) $membership->club_id === (int) $equipment->club_id;
    }

    /**
     * Approve pending loan requests.
     */
    public function approve(User $user, Loan $loan): bool
    {
        return $loan->status === Loan::STATUS_PENDING
            && $this->canManageLoan($user, $loan);
    }

    /**
     * Reject pending loan requests.
     */
    public function reject(User $user, Loan $loan): bool
    {
        return $loan->status === Loan::STATUS_PENDING
            && $this->canManageLoan($user, $loan);
    }

    /**
     * Issue approved equipment.
     */
    public function issue(User $user, Loan $loan): bool
    {
        return $loan->status === Loan::STATUS_APPROVED
            && $this->canManageLoan($user, $loan);
    }

    /**
     * Record equipment returns.
     */
    public function returnEquipment(
        User $user,
        Loan $loan
    ): bool {

        return $loan->status === Loan::STATUS_ISSUED
            && $this->canManageLoan($user, $loan);
    }

    /**
     * Cancel a pending or approved loan.
     */
    public function cancel(User $user, Loan $loan): bool
    {
        if (!in_array($loan->status, [
            Loan::STATUS_PENDING,
            Loan::STATUS_APPROVED,
        ], true)) {
            return false;
        }

        // Students can cancel their own requests.
        if (
            $user->isStudent()
            && (int) $loan->membership->user_id === (int) $user->id
        ) {
            return true;
        }

        // Administrators and assigned club officers.
        return $this->canManageLoan($user, $loan);
    }

    /**
     * Check whether the user manages the club
     * associated with a particular equipment loan.
     */
    private function canManageLoan(
        User $user,
        Loan $loan
    ): bool {

        if ($user->isUniversityAdmin()) {
            return true;
        }

        $loan->loadMissing('equipment');

        if (!$loan->equipment) {
            return false;
        }

        return $user->clubRoles()
            ->where('club_id', $loan->equipment->club_id)
            ->where('role', 'officer')
            ->exists();
    }
}


