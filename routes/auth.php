<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Middleware\VerifyNativeRequests;

Route::prefix('platform/app')
    ->middleware(VerifyNativeRequests::class)
    ->group(function () {
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('guest')
            ->name('login');

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
            ->middleware('auth')
            ->name('logout');
    });
