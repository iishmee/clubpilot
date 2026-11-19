<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Loan;
use App\Models\Membership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanService
{
    private const RESERVED_STATUSES = [
        Loan::STATUS_PENDING,
        Loan::STATUS_APPROVED,
        Loan::STATUS_ISSUED,
        Loan::STATUS_OVERDUE,
    ];

    public function __construct(
        private IntervalService $intervalService
    ) {
    }

    /**
     * Submit a new equipment borrowing request.
     */
    public function requestLoan(
        Equipment $equipment,
        Membership $membership,
        User $actor,
        array $data
    ): Loan {

        $startsAt = CarbonImmutable::parse($data['starts_at']);

        $endsAt = CarbonImmutable::parse($data['ends_at']);

        $quantity = (int) ($data['requested_quantity'] ?? 1);

        $this->validatePeriod($startsAt, $endsAt, $quantity);

        return DB::transaction(function () use (
            $equipment,
            $membership,
            $actor,
            $data,
            $startsAt,
            $endsAt,
            $quantity
        ) {

            // Always lock the equipment record first.
            $lockedEquipment = Equipment::query()
                ->whereKey($equipment->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Lock the membership and recheck its status.
            $lockedMembership = Membership::query()
                ->whereKey($membership->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertStudentCanBorrow(
                $lockedEquipment,
                $lockedMembership,
                $actor
            );

            if (!$lockedEquipment->is_active) {
                throw ValidationException::withMessages([
                    'equipment_id' => 'This equipment is currently unavailable.',
                ]);
            }

            $this->assertCapacityAvailable(
                $lockedEquipment,
                $startsAt,
                $endsAt,
                $quantity
            );

            $loan = Loan::create([
                'equipment_id' => $lockedEquipment->id,
                'membership_id' => $lockedMembership->id,
                'requested_quantity' => $quantity,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => Loan::STATUS_PENDING,
                'request_note' => $data['request_note'] ?? null,
            ]);

            return $loan;
        });
    }

    /**
     * Approve an equipment borrowing request.
     */
    public function approve(
        Loan $loan,
        User $actor,
        ?string $note = null
    ): Loan {

        return DB::transaction(function () use ($loan, $actor, $note) {

            [$equipment, $lockedLoan] =
                $this->lockEquipmentAndLoan($loan);

            if ($lockedLoan->status !== Loan::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'loan' => 'Only pending requests can be approved.',
                ]);
            }

            if (!$equipment->is_active) {
                throw ValidationException::withMessages([
                    'loan' => 'This equipment is no longer available.',
                ]);
            }

            $membership = Membership::query()
                ->whereKey($lockedLoan->membership_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertMembershipActive(
                $membership,
                $equipment
            );

            $startsAt = CarbonImmutable::parse($lockedLoan->starts_at);

            $endsAt = CarbonImmutable::parse($lockedLoan->ends_at);

            // Recheck stock before approval.
            // Exclude this request from existing reservations,
            // then add its requested quantity again.
            $this->assertCapacityAvailable(
                $equipment,
                $startsAt,
                $endsAt,
                (int) $lockedLoan->requested_quantity,
                $lockedLoan->id
            );

            $lockedLoan->update([
                'status' => Loan::STATUS_APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'decision_note' => $note,
            ]);

            return $lockedLoan->fresh([
                'equipment',
                'membership',
            ]);
        });
    }

    /**
     * Reject an equipment borrowing request.
     */
    public function reject(
        Loan $loan,
        User $actor,
        string $reason
    ): Loan {

        return DB::transaction(function () use ($loan, $actor, $reason) {

            [$equipment, $lockedLoan] =
                $this->lockEquipmentAndLoan($loan);

            if ($lockedLoan->status !== Loan::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'loan' => 'Only pending requests can be rejected.',
                ]);
            }

            $lockedLoan->update([
                'status' => Loan::STATUS_REJECTED,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'decision_note' => $reason,
            ]);

            return $lockedLoan->fresh([
                'equipment',
                'membership',
            ]);
        });
    }

    /**
     * Issue approved equipment to a member.
     */
    public function issue(
        Loan $loan,
        User $actor
    ): Loan {

        return DB::transaction(function () use ($loan, $actor) {

            [$equipment, $lockedLoan] =
                $this->lockEquipmentAndLoan($loan);

            if ($lockedLoan->status !== Loan::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'loan' => 'Only approved loans can be issued.',
                ]);
            }

            $membership = Membership::query()
                ->whereKey($lockedLoan->membership_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertMembershipActive(
                $membership,
                $equipment
            );

            $startsAt = CarbonImmutable::parse($lockedLoan->starts_at);

            $endsAt = CarbonImmutable::parse($lockedLoan->ends_at);

            if ($startsAt->isFuture()) {
                throw ValidationException::withMessages([
                    'loan' => 'Equipment cannot be issued before the borrowing start date.',
                ]);
            }

            if (!$endsAt->isFuture()) {
                throw ValidationException::withMessages([
                    'loan' => 'The borrowing period has already expired.',
                ]);
            }

            $lockedLoan->update([
                'status' => Loan::STATUS_ISSUED,
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ]);

            return $lockedLoan->fresh([
                'equipment',
                'membership',
            ]);
        });
    }

    /**
     * Record the return of borrowed equipment.
     */
    public function returnEquipment(
        Loan $loan,
        User $actor
    ): Loan {

        return DB::transaction(function () use ($loan, $actor) {

            [$equipment, $lockedLoan] =
                $this->lockEquipmentAndLoan($loan);

            if ($lockedLoan->status !== Loan::STATUS_ISSUED) {
                throw ValidationException::withMessages([
                    'loan' => 'Only issued equipment can be returned.',
                ]);
            }

            $lockedLoan->update([
                'status' => Loan::STATUS_RETURNED,
                'returned_to' => $actor->id,
                'returned_at' => now(),
            ]);

            return $lockedLoan->fresh([
                'equipment',
                'membership',
            ]);
        });
    }

    /**
     * Cancel a pending or approved borrowing request.
     */
    public function cancel(
        Loan $loan,
        User $actor,
        ?string $reason = null
    ): Loan {

        return DB::transaction(function () use ($loan, $actor, $reason) {

            [$equipment, $lockedLoan] =
                $this->lockEquipmentAndLoan($loan);

            if (!in_array($lockedLoan->status, [
                Loan::STATUS_PENDING,
                Loan::STATUS_APPROVED,
            ], true)) {

                throw ValidationException::withMessages([
                    'loan' => 'This borrowing request cannot be cancelled.',
                ]);
            }

            $lockedLoan->update([
                'status' => Loan::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'decision_note' => $reason,
            ]);

            return $lockedLoan->fresh([
                'equipment',
                'membership',
            ]);
        });
    }

    /**
     * Calculate available equipment for a selected period.
     */
    public function availableQuantity(
        Equipment $equipment,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt
    ): int {

        if (!$endsAt->gt($startsAt)) {
            return 0;
        }

        $start = CarbonImmutable::instance($startsAt);

        $end = CarbonImmutable::instance($endsAt);

        $reserved = $this->maximumConcurrentReservedQuantity(
            $equipment,
            $start,
            $end
        );

        return max(
            0,
            (int) $equipment->quantity - $reserved
        );
    }

    /**
     * Verify that the borrowing period is valid.
     */
    private function validatePeriod(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        int $quantity
    ): void {

        if ($startsAt->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'starts_at' => 'The borrowing start time must be in the future.',
            ]);
        }

        if (!$endsAt->greaterThan($startsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => 'The return time must be after the borrowing start time.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'requested_quantity' => 'At least one unit must be requested.',
            ]);
        }
    }

    /**
     * Check student membership and club ownership.
     */
    private function assertStudentCanBorrow(
        Equipment $equipment,
        Membership $membership,
        User $actor
    ): void {

        if (!$actor->isStudent()) {
            throw ValidationException::withMessages([
                'loan' => 'Only students can submit borrowing requests.',
            ]);
        }

        if ((int) $membership->user_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'membership' => 'You cannot borrow using another student membership.',
            ]);
        }

        $this->assertMembershipActive(
            $membership,
            $equipment
        );
    }

    /**
     * Ensure membership is active and belongs to the equipment club.
     */
    private function assertMembershipActive(
        Membership $membership,
        Equipment $equipment
    ): void {

        if ($membership->status !== Membership::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'membership' => 'An active club membership is required.',
            ]);
        }

        if ((int) $membership->club_id !== (int) $equipment->club_id) {
            throw ValidationException::withMessages([
                'membership' => 'This membership does not belong to the equipment club.',
            ]);
        }
    }

    /**
     * Check equipment availability for the entire requested period.
     */
    private function assertCapacityAvailable(
        Equipment $equipment,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        int $requestedQuantity,
        ?int $excludeLoanId = null
    ): void {

        $alreadyReserved = $this->maximumConcurrentReservedQuantity(
            $equipment,
            $startsAt,
            $endsAt,
            0,
            $excludeLoanId
        );

        $available = max(
            0,
            (int) $equipment->quantity - $alreadyReserved
        );

        $maximumAfterRequest = $this->maximumConcurrentReservedQuantity(
            $equipment,
            $startsAt,
            $endsAt,
            $requestedQuantity,
            $excludeLoanId
        );

        if ($maximumAfterRequest > (int) $equipment->quantity) {
            throw ValidationException::withMessages([
                'requested_quantity' =>
                    "Only {$available} unit(s) are available for the entire selected period.",
            ]);
        }
    }

    /**
     * Calculate maximum simultaneous reserved equipment quantity.
     *
     * Pending, approved, issued and overdue loans reserve stock.
     * Returned, rejected and cancelled loans do not.
     */
    private function maximumConcurrentReservedQuantity(
        Equipment $equipment,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd,
        int $additionalQuantity = 0,
        ?int $excludeLoanId = null
    ): int {

        $query = Loan::query()
            ->where('equipment_id', $equipment->id)
            ->whereIn('status', self::RESERVED_STATUSES)
            ->where('starts_at', '<', $windowEnd)
            ->where('ends_at', '>', $windowStart);

        if ($excludeLoanId !== null) {
            $query->whereKeyNot($excludeLoanId);
        }

        $reservations = $query->get([
            'id',
            'starts_at',
            'ends_at',
            'requested_quantity',
        ]);

        $events = [];

        // Add the new request to the availability calculation.
        if ($additionalQuantity > 0) {

            $events[] = [
                'time' => $windowStart->getTimestamp(),
                'delta' => $additionalQuantity,
            ];

            $events[] = [
                'time' => $windowEnd->getTimestamp(),
                'delta' => -$additionalQuantity,
            ];
        }

        foreach ($reservations as $reservation) {

            $existingStart = CarbonImmutable::parse(
                $reservation->starts_at
            );

            $existingEnd = CarbonImmutable::parse(
                $reservation->ends_at
            );

            if (!$this->intervalService->overlaps(
                $windowStart,
                $windowEnd,
                $existingStart,
                $existingEnd
            )) {
                continue;
            }

            // Clip reservations to the requested period.
            $clippedStart = $existingStart->greaterThan($windowStart)
                ? $existingStart
                : $windowStart;

            $clippedEnd = $existingEnd->lessThan($windowEnd)
                ? $existingEnd
                : $windowEnd;

            $quantity = (int) $reservation->requested_quantity;

            $events[] = [
                'time' => $clippedStart->getTimestamp(),
                'delta' => $quantity,
            ];

            $events[] = [
                'time' => $clippedEnd->getTimestamp(),
                'delta' => -$quantity,
            ];
        }

        // End events are processed before start events at the same time.
        usort($events, function (array $a, array $b): int {

            return ($a['time'] <=> $b['time'])
                ?: ($a['delta'] <=> $b['delta']);
        });

        $currentQuantity = 0;

        $maximumQuantity = 0;

        foreach ($events as $event) {

            $currentQuantity += $event['delta'];

            $maximumQuantity = max(
                $maximumQuantity,
                $currentQuantity
            );
        }

        return $maximumQuantity;
    }

    /**
     * Lock equipment first, then lock the associated loan.
     */
    private function lockEquipmentAndLoan(
        Loan $loan
    ): array {

        $equipment = Equipment::query()
            ->whereKey($loan->equipment_id)
            ->lockForUpdate()
            ->firstOrFail();

        $lockedLoan = Loan::query()
            ->whereKey($loan->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ((int) $lockedLoan->equipment_id !== (int) $equipment->id) {
            throw ValidationException::withMessages([
                'loan' => 'The equipment associated with this loan has changed.',
            ]);
        }

        return [
            $equipment,
            $lockedLoan,
        ];
    }
}

