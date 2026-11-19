<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Membership extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_INVITED = 'invited';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_GRADUATED = 'graduated';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'club_id',
        'user_id',
        'student_id',
        'department',
        'semester',
        'invited_by',
        'status',
        'application_message',
        'status_reason',
        'applied_at',
        'invited_at',
        'joined_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'invited_at' => 'datetime',
            'joined_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeGraduated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_GRADUATED);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isGraduated(): bool
    {
        return $this->status === self::STATUS_GRADUATED;
    }
}
