<?php

use App\Http\Controllers\Api\AvailabilityCalendarController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\ReservationHubController;
use App\Http\Controllers\Api\TimeslotController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/regions', [RegionController::class, 'index']);
    Route::post('/regions', [RegionController::class, 'store']);
    Route::get('/timeslots', TimeslotController::class);
    Route::get('/venues', VenueController::class);
    Route::get('/calendar', AvailabilityCalendarController::class);
    Route::get('/hub', ReservationHubController::class);
});
