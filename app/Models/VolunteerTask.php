<?php

namespace App\Models;

use App\Policies\VolunteerTaskPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(VolunteerTaskPolicy::class)]
class VolunteerTask extends Model
{
    /** @use HasFactory<\Database\Factories\VolunteerTaskFactory> */
    use HasFactory;

    public const STATUS_TODO = 'todo';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    public const STATUSES = [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_DONE];

    protected $fillable = ['volunteer_opportunity_id', 'title', 'description', 'assigned_to', 'status', 'due_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(VolunteerOpportunity::class, 'volunteer_opportunity_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
