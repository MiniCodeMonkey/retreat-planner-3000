<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Venue extends Model
{
    use GeocodesCoordinates;

    protected $fillable = [
        'source',
        'external_id',
        'url',
        'name',
        'rooms',
        'floors',
        'latitude',
        'longitude',
        'address',
        'city',
        'state',
        'country',
        'accuracy',
        'accuracy_type',
        'nearest_airport_code',
        'nearest_airport_distance',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'nearest_airport_distance' => 'float',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }
    
    public function airports(): BelongsToMany
    {
        return $this->belongsToMany(Airport::class, 'venue_airport')
                    ->withPivot('distance_miles', 'is_nearest')
                    ->withTimestamps()
                    ->orderBy('pivot_distance_miles');
    }
    
    public function nearbyAirports()
    {
        return $this->airports()->wherePivot('distance_miles', '<=', 50);
    }
    
    public function nearestAirport()
    {
        return $this->airports()->wherePivot('is_nearest', true)->first();
    }
}
