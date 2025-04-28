<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VenueController;

Route::get('/', [VenueController::class, 'index']);
Route::get('/api/venues', [VenueController::class, 'list']);
