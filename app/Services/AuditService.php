<?php

namespace App\Services;

use App\Models\AuditEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(
        User $actor,
        Model $subject,
        ?string $fromState,
        string $toState,
        ?string $reason = null,
    ): AuditEntry {
        return AuditEntry::query()->create([
            'actor_id' => $actor->getKey(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'from_state' => $fromState,
            'to_state' => $toState,
            'reason' => $reason,
        ]);
    }
}

