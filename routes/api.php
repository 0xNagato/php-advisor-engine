<?php

use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AvailabilityCalendarController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ContactFormController;
use App\Http\Controllers\Api\CuisineController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\NeighborhoodController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\ReservationHubController;
use App\Http\Controllers\Api\RoleProfileController;
use App\Http\Controllers\Api\SpecialtyController;
use App\Http\Controllers\Api\TimeslotController;
use App\Http\Controllers\Api\UpdatePushTokenController;
use App\Http\Controllers\Api\VenueController;
use App\Http\Controllers\Api\VipSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// VIP Session endpoints (public - no authentication required)
Route::post('/vip/sessions', [VipSessionController::class, 'createSession']);
Route::post('/vip/sessions/validate', [VipSessionController::class, 'validateSession']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/neighborhoods', NeighborhoodController::class);
    Route::get('/cuisines', CuisineController::class);
    Route::get('/specialties', SpecialtyController::class);
    Route::post('/regions', [RegionController::class, 'store']);
    Route::get('/timeslots', TimeslotController::class);
    Route::get('/venues', VenueController::class);
    Route::get('/calendar', AvailabilityCalendarController::class);
    Route::get('/hub', ReservationHubController::class);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{booking}', [BookingController::class, 'update']);
    Route::post('/bookings/{booking}/complete', [BookingController::class, 'complete']);
    Route::get('/bookings/{booking}/invoice-status', [BookingController::class, 'invoiceStatus']);
    Route::post('/bookings/{booking}/email-invoice', [BookingController::class, 'emailInvoice']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);
    Route::post('/contact', [ContactFormController::class, 'submit']);
    Route::get('/profiles', [RoleProfileController::class, 'index']);
    Route::post('/profiles/{profile}/switch', [RoleProfileController::class, 'switch']);
    Route::get('/me', MeController::class);
    Route::post('/update-push-token', UpdatePushTokenController::class);

    // VIP Session analytics (authenticated)
    Route::get('/vip/sessions/analytics', [VipSessionController::class, 'getSessionAnalytics']);
});

Route::get('/app-config', AppConfigController::class)->name('app-config');
