<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(): View
    {
        return view('map');
    }

    public function list(): JsonResponse
    {
        $venues = Venue::with(['images', 'airports' => function ($query) {
            $query->select(['airports.id', 'airports.iata_code', 'airports.municipality', 'airports.country', 'airports.latitude', 'airports.longitude'])
                ->withPivot(['distance_miles', 'is_nearest']);
        }])->get();

        return response()->json($venues);
    }
}
