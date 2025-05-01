<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class VenueAirportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command can successfully import airports and calculate distances.
     */
    public function test_calculate_nearest_airports_command(): void
    {
        // Create a test venue
        $venue = Venue::create([
            'source' => 'test',
            'external_id' => 'test-venue-1',
            'url' => 'https://example.com/test-venue',
            'name' => 'Test Venue',
            'latitude' => 34.052235, // Los Angeles
            'longitude' => -118.243683,
        ]);

        // Run the command
        Artisan::call('app:calculate-nearest-airports');

        // Verify that airports were imported
        $this->assertGreaterThan(0, Airport::count());

        // Refresh the venue model
        $venue->refresh();

        // Verify the venue has nearest airport data
        $this->assertNotNull($venue->nearest_airport_code);
        $this->assertNotNull($venue->nearest_airport_distance);

        // Verify relationships were created
        $this->assertGreaterThan(0, $venue->airports()->count());
        
        // Verify the nearest airport relationship is marked
        $this->assertEquals(1, $venue->airports()->wherePivot('is_nearest', true)->count());
        
        // Verify nearby airports are within the distance limit
        $nearbyCount = $venue->nearbyAirports()->count();
        if ($nearbyCount > 0) {
            $this->assertLessThanOrEqual(50, $venue->nearbyAirports()->orderBy('pivot_distance_miles', 'desc')->first()->pivot->distance_miles);
        }
    }
}
