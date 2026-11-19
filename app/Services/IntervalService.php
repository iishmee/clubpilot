<?php

namespace App\Services;

use Carbon\CarbonInterface;

class IntervalService
{
    /**
     * Check whether two time intervals overlap.
     *
     * Uses half-open intervals [start, end).
     */
    public function overlaps(
        CarbonInterface $newStart,
        CarbonInterface $newEnd,
        CarbonInterface $existingStart,
        CarbonInterface $existingEnd
    ): bool {

        return $newStart->lt($existingEnd)
            && $newEnd->gt($existingStart);
    }
}

