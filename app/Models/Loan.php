<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory;

    // Loan statuses
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'equipment_id',
        'membership_id',
        'requested_quantity',
        'starts_at',
        'ends_at',
        'status',
        'request_note',
        'decision_note',
        'approved_by',
        'approved_at',
        'issued_by',
        'issued_at',
        'returned_to',
        'returned_at',
        'rejected_by',
        'rejected_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'integer',

            'starts_at' => 'datetime',
            'ends_at' => 'datetime',

            'approved_at' => 'datetime',
            'issued_at' => 'datetime',
            'returned_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // Equipment associated with this loan.
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    // Membership associated with this loan.
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    // Administrator who approved the request.
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Person who issued the equipment.
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    // Person who received the returned equipment.
    public function returnedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to');
    }

    // Administrator who rejected the request.
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Check whether the equipment is overdue.
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE
            || (
                $this->status === self::STATUS_ISSUED
                && $this->ends_at !== null
                && $this->ends_at->isPast()
            );
    }
}
