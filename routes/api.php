<?php

use App\Http\Controllers\Api\AvailableRestaurantController;
use App\Http\Controllers\Api\TimeslotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/timeslots', TimeslotController::class)
        ->name('timeslots.index');

    Route::get('/available-restaurants', AvailableRestaurantController::class)
        ->name('available-restaurants.index');
});
