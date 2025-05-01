<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Airport extends Model
{
    protected $fillable = [
        'iata_code',
        'municipality',
        'country',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'venue_airport')
                    ->withPivot('distance_miles', 'is_nearest')
                    ->withTimestamps();
    }
}
