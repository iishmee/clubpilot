<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'name',
        'category',
        'description',
        'quantity',
        'item_condition',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Equipment belongs to a club.
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    // Equipment can have multiple borrowing records.
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    // Check whether equipment is available for borrowing.
    public function isAvailable(): bool
    {
        return $this->is_active && $this->quantity > 0;
    }
}

