<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }
}
