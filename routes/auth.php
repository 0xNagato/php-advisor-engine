<?php

use App\Http\Controllers\App\UpdateRegionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::prefix('app')->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest')
        ->name('login');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::post('/region', UpdateRegionController::class)
        ->middleware('auth')
        ->name('app.region.update');
});
