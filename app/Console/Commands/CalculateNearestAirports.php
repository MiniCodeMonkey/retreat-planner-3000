<?php

namespace App\Console\Commands;

use App\Models\Airport;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CalculateNearestAirports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-nearest-airports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the nearest airport and all airports within 50 miles of each venue';

    /**
     * Execute the console command.
     */
    /**
     * Maximum distance in miles to consider an airport as nearby
     */
    private const MAX_NEARBY_DISTANCE = 50;

    public function handle()
    {
        // First, import all airports
        $this->importAirports();
        
        // Get all venues with coordinates
        $venues = Venue::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
            
        // Get all airports
        $airports = Airport::all();

        $this->info("Processing {$venues->count()} venues to find nearest airports and all airports within " . 
            self::MAX_NEARBY_DISTANCE . " miles...");
        $progressBar = $this->output->createProgressBar($venues->count());
        $progressBar->start();

        $updatedCount = 0;
        $nearbyCount = 0;

        foreach ($venues as $venue) {
            // Calculate distances to all airports
            $airportsWithDistances = [];
            
            foreach ($airports as $airport) {
                $distance = $this->haversineDistance(
                    $venue->latitude,
                    $venue->longitude,
                    $airport->latitude,
                    $airport->longitude
                );
                
                $airportsWithDistances[] = [
                    'airport' => $airport,
                    'distance' => $distance
                ];
            }
            
            // Sort airports by distance
            usort($airportsWithDistances, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
            
            if (!empty($airportsWithDistances)) {
                // Remove existing relationships
                \DB::table('venue_airport')->where('venue_id', $venue->id)->delete();
                
                // Get the nearest airport
                $nearestAirport = $airportsWithDistances[0];
                
                // For backward compatibility, still set these fields
                $venue->nearest_airport_code = $nearestAirport['airport']->iata_code;
                $venue->nearest_airport_distance = $nearestAirport['distance'];
                $venue->save();
                $updatedCount++;
                
                // Create relationship with nearest airport and mark as nearest
                \DB::table('venue_airport')->insert([
                    'venue_id' => $venue->id,
                    'airport_id' => $nearestAirport['airport']->id,
                    'distance_miles' => $nearestAirport['distance'],
                    'is_nearest' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Get all airports within MAX_NEARBY_DISTANCE miles (exclude the nearest one that we already added)
                $nearbyAirports = array_filter($airportsWithDistances, function($airport) use ($nearestAirport, $venue) {
                    return $airport['distance'] <= self::MAX_NEARBY_DISTANCE && 
                           $airport['airport']->id !== $nearestAirport['airport']->id;
                });
                
                // Create relationships with nearby airports
                foreach ($nearbyAirports as $airport) {
                    \DB::table('venue_airport')->insert([
                        'venue_id' => $venue->id,
                        'airport_id' => $airport['airport']->id,
                        'distance_miles' => $airport['distance'],
                        'is_nearest' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $nearbyCount++;
                }
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Updated nearest airport for {$updatedCount} venues and recorded {$nearbyCount} nearby airports.");

        return Command::SUCCESS;
    }

    /**
     * Import airports from GeoJSON file to the database
     */
    private function importAirports(): void
    {
        $airportsPath = public_path('airports.geojson');
        $airportsJson = File::get($airportsPath);
        $airportsData = json_decode($airportsJson, true);

        $imported = 0;
        $this->info('Importing airports from GeoJSON...');
        
        // Clear any existing airports for a fresh import
        Airport::truncate();

        foreach ($airportsData['features'] as $feature) {
            if (isset($feature['properties']['iata_code']) &&
                isset($feature['properties']['latitude_deg']) &&
                isset($feature['properties']['longitude_deg'])) {
                
                Airport::create([
                    'iata_code' => $feature['properties']['iata_code'],
                    'latitude' => $feature['properties']['latitude_deg'],
                    'longitude' => $feature['properties']['longitude_deg'],
                    'municipality' => $feature['properties']['municipality'] ?? null,
                    'country' => $feature['properties']['iso_country'] ?? null,
                ]);
                
                $imported++;
            }
        }

        $this->info("Imported {$imported} airports.");
    }


    /**
     * Calculate the Haversine distance between two points in miles
     */
    private function haversineDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        // Convert latitude and longitude from degrees to radians
        $lat1 = deg2rad((float) $lat1);
        $lon1 = deg2rad((float) $lon1);
        $lat2 = deg2rad((float) $lat2);
        $lon2 = deg2rad((float) $lon2);

        // Haversine formula
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        // Radius of Earth in miles (3963 miles vs 6371 kilometers)
        $r = 3963;

        // Calculate the distance
        return $c * $r;
    }
}
