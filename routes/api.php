<?php

use App\Http\Controllers\Api\TimeslotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/{region}/test', function ($region) {
        $data = [
            'region' => $region,
            'bookings' => [
                [
                    'id' => 1,
                    'name' => 'Booking 1',
                    'date' => '2021-01-01',
                ],
                [
                    'id' => 2,
                    'name' => 'Booking 2',
                    'date' => '2021-01-02',
                ],
            ],
        ];

        return response()->json($data);
    });

    Route::get('/timeslots', TimeslotController::class)
        ->name('timeslots.index');
});
