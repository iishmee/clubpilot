<?php

namespace App\Models;

use App\Policies\ServiceHoursPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(ServiceHoursPolicy::class)]
class ServiceEntry extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceEntryFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'volunteer_opportunity_id',
        'hours',
        'worked_on',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'worked_on' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    /** The volunteer who did the work. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(VolunteerOpportunity::class, 'volunteer_opportunity_id');
    }

    /** The organizer who approved or rejected the hours. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
