<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ClubProposal extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'name',
        'details',
        'objectives',
        'constitution_path',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function club(): HasOne
    {
        return $this->hasOne(Club::class, 'club_proposal_id');
    }

    public function auditEntries(): MorphMany
    {
        return $this->morphMany(AuditEntry::class, 'subject')->latest();
    }
}

