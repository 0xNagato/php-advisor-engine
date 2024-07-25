<?php

use App\Http\Controllers\Api\AvailabilityCalendarController;
use App\Http\Controllers\Api\AvailableRestaurantController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\TimeslotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/regions', RegionController::class);

    Route::get('/{region}/timeslots', TimeslotController::class);

    Route::get('/{region}/available-restaurants', AvailableRestaurantController::class);

    Route::get('/{region}/availability-calendar', AvailabilityCalendarController::class);
});
