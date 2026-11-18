<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerOpportunity extends Model
{
    /** @use HasFactory<\Database\Factories\VolunteerOpportunityFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = ['event_id', 'title', 'description', 'slots_needed', 'status'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class);
    }

    public function acceptedCount(): int
    {
        return $this->applications()->where('status', VolunteerApplication::STATUS_ACCEPTED)->count();
    }

    public function isFull(): bool
    {
        return $this->acceptedCount() >= $this->slots_needed;
    }
}
