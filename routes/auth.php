<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::post('/app-login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login');

Route::post('/app-logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
