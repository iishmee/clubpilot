<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_proposal_id',
        'name',
        'details',
        'objectives',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ClubProposal::class, 'club_proposal_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
