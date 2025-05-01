<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(): View
    {
        return view('welcome');
    }

    public function list(): JsonResponse
    {
        $venues = Venue::with('images')->get();

        return response()->json($venues);
    }
}
