<?php

use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VenueController::class, 'index']);
Route::get('/api/venues', [VenueController::class, 'list']);
