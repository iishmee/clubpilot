<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Equipment;
use App\Models\Loan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EquipmentService
{
    /**
     * Create new equipment for a club.
     */
    public function create(Club $club, array $data): Equipment
    {
        return DB::transaction(function () use ($club, $data) {

            $lockedClub = Club::query()
                ->whereKey($club->id)
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) ($data['quantity'] ?? 1);

            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Equipment quantity must be at least one.',
                ]);
            }

            return Equipment::create([
                'club_id' => $lockedClub->id,
                'name' => $data['name'],
                'category' => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
                'quantity' => $quantity,
                'item_condition' => $data['item_condition'] ?? 'good',
                'is_active' => true,
            ]);
        });
    }

    /**
     * Update existing equipment information.
     */
    public function update(
        Equipment $equipment,
        array $data
    ): Equipment {

        return DB::transaction(function () use ($equipment, $data) {

            $lockedEquipment = Equipment::query()
                ->whereKey($equipment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newQuantity = (int) (
                $data['quantity'] ?? $lockedEquipment->quantity
            );

            if ($newQuantity < 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Equipment quantity must be at least one.',
                ]);
            }

            // Do not reduce inventory below existing reservations.
            $reservedQuantity = $this->maximumReservedQuantity(
                $lockedEquipment
            );

            if ($newQuantity < $reservedQuantity) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        "You cannot reduce the quantity below {$reservedQuantity} because existing loan reservations require it.",
                ]);
            }

            $lockedEquipment->update([
                'name' => $data['name'] ?? $lockedEquipment->name,
                'category' => $data['category'] ?? $lockedEquipment->category,
                'description' => $data['description'] ?? $lockedEquipment->description,
                'quantity' => $newQuantity,
                'item_condition' => $data['item_condition']
                    ?? $lockedEquipment->item_condition,
            ]);

            return $lockedEquipment->fresh('club');
        });
    }

    /**
     * Deactivate equipment without deleting its history.
     */
    public function deactivate(Equipment $equipment): Equipment
    {
        return DB::transaction(function () use ($equipment) {

            $lockedEquipment = Equipment::query()
                ->whereKey($equipment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedEquipment->update([
                'is_active' => false,
            ]);

            return $lockedEquipment->fresh('club');
        });
    }

    /**
     * Reactivate previously deactivated equipment.
     */
    public function activate(Equipment $equipment): Equipment
    {
        return DB::transaction(function () use ($equipment) {

            $lockedEquipment = Equipment::query()
                ->whereKey($equipment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedEquipment->update([
                'is_active' => true,
            ]);

            return $lockedEquipment->fresh('club');
        });
    }

    /**
     * Calculate the maximum number of units reserved
     * simultaneously across all existing loan periods.
     */
    private function maximumReservedQuantity(
        Equipment $equipment
    ): int {

        $reservations = Loan::query()
            ->where('equipment_id', $equipment->id)
            ->whereIn('status', [
                Loan::STATUS_PENDING,
                Loan::STATUS_APPROVED,
                Loan::STATUS_ISSUED,
                Loan::STATUS_OVERDUE,
            ])
            ->get([
                'starts_at',
                'ends_at',
                'requested_quantity',
            ]);

        $events = [];

        foreach ($reservations as $reservation) {

            $start = CarbonImmutable::parse(
                $reservation->starts_at
            )->getTimestamp();

            $end = CarbonImmutable::parse(
                $reservation->ends_at
            )->getTimestamp();

            if ($end <= $start) {
                continue;
            }

            $quantity = (int) $reservation->requested_quantity;

            $events[] = [
                'time' => $start,
                'delta' => $quantity,
            ];

            $events[] = [
                'time' => $end,
                'delta' => -$quantity,
            ];
        }

        // Process ending reservations before new reservations
        // at the same timestamp.
        usort($events, function ($a, $b) {

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
}
