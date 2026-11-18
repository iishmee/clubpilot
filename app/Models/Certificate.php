<?php

namespace App\Models;

use App\Policies\CertificatePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(CertificatePolicy::class)]
class Certificate extends Model
{
    /** @use HasFactory<\Database\Factories\CertificateFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'volunteer_opportunity_id',
        'issued_by',
        'certificate_number',
        'hours',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    /** The volunteer the certificate belongs to. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(VolunteerOpportunity::class, 'volunteer_opportunity_id');
    }

    /** The organizer who issued it. */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
