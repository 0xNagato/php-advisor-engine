<?php

use App\Filament\Pages\CreatePassword;
use App\Http\Controllers\Booking\BookingCheckoutController;
use App\Http\Controllers\DemoAuthController;
use App\Http\Controllers\DownloadInvoiceController;
use App\Http\Controllers\DownloadVenueInvoiceController;
use App\Http\Controllers\ExceptionFormController;
use App\Livewire\Booking\CreateBooking;
use App\Livewire\Booking\CustomerInvoice;
use App\Livewire\Concierge\ConciergeInvitation;
use App\Livewire\Concierge\DirectConciergeInvitation;
use App\Livewire\Story;
use App\Livewire\Venue\VenueBookingConfirmation;
use App\Livewire\Venue\VenueContactLogin;
use App\Livewire\Venue\VenueContactRecentBookings;
use App\Livewire\Venue\VenueModificationRequestConfirmation;
use App\Livewire\Venue\VenueSpecialRequestConfirmation;
use App\Livewire\VenueOnboarding;
use App\Livewire\Vip\AvailabilityCalendar;
use AshAllenDesign\ShortURL\Controllers\ShortURLController;

Route::get('/privacy', function () {
    return view('privacy');
});

Route::get('/story', Story::class)->name('story');

Route::get('/onboarding', VenueOnboarding::class)->name('onboarding');

Route::redirect('/app', config('app.apple_app_store_url'));
Route::redirect('/iphone', config('app.apple_app_store_url'));
Route::redirect('/ipad', config('app.apple_app_store_url'));

/**
 * Short URL handling for both domains:
 * - Legacy: primavip.co/t/{code}
 * - New: ezjmp.com/{code}
 */
Route::domain(config('short-url.domain'))->group(function () {
    Route::get('/{shortURLKey}', ShortURLController::class)
        ->middleware(config('short-url.middleware', []));
});

Route::domain(config('app.domain'))->group(function () {
    Route::get('/t/{shortURLKey}', ShortURLController::class)
        ->middleware(config('short-url.middleware', []));
});

Route::get('/', static function () {
    return view('web.index');
});

Route::redirect('/login', '/platform/login');

Route::redirect('/platform/app/login', config('app.platform_url'));

Route::get('/invoice/download/{uuid}', DownloadInvoiceController::class)
    ->name('customer.invoice.download');

Route::get('/invoice/{token}', CustomerInvoice::class)->name('customer.invoice');

Route::get('/venues/confirm/{booking:uuid}', VenueBookingConfirmation::class)
    ->name('venues.confirm');

Route::get('/venues/confirm/special-request/{token}', VenueSpecialRequestConfirmation::class)
    ->name('venues.confirm-special-request');

// Old customer booking flow
Route::get('/bookings/create/{token}', CreateBooking::class)->name('bookings.create');

// New customer booking flow
Route::get('/checkout/{booking:uuid}', BookingCheckoutController::class)->name('booking.checkout');

Route::get('/demo/auth/{user_id}', [DemoAuthController::class, 'auth'])->name('demo.auth');
Route::get('/demo/redirect', [DemoAuthController::class, 'redirect'])->name('demo.redirect');

Route::get('/platform/login/venue', VenueContactLogin::class)->name('venue.login');
Route::get('/venues/contact-bookings', VenueContactRecentBookings::class)
    ->name('venue.contact.bookings')->middleware('signed');

Route::post('/exception-form', ExceptionFormController::class)->name('exception.form');

Route::get('vip/login/{code?}', fn ($code = null) => redirect($code ? "/v/{$code}" : '/'))->name('vip.login');

Route::get('v/{code}', AvailabilityCalendar::class)->name('v.booking');
Route::post('/role/switch/{profile}', [App\Http\Controllers\RoleSwitcherController::class, 'switch'])
    ->middleware(['web', 'auth'])
    ->name('role.switch');

Route::prefix('venue')->name('venue.')->group(function () {
    Route::get('modification-request/{modificationRequest}', VenueModificationRequestConfirmation::class)
        ->name('modification-request')
        ->middleware(['signed'])
        ->missing(function () {
            return response()->view('errors.modification-request-expired', [], 403);
        });
});

Route::get('/join/{type}/{id}', DirectConciergeInvitation::class)
    ->name('concierge.join.direct')
    ->middleware(['signed']);

Route::get('/invitation/{referral}', ConciergeInvitation::class)
    ->name('concierge.invitation');

Route::get('password/create/{token}', CreatePassword::class)
    ->name('password.create')
    ->middleware('signed');

Route::get('venue-manager/invitation/{referral}', App\Filament\Pages\VenueManager\AcceptInvitation::class)
    ->name('venue-manager.invitation')
    ->middleware(['signed']);

Route::get('venue-invoice/{user}/{startDate}/{endDate}', DownloadVenueInvoiceController::class)
    ->name('venue.invoice.download')
    ->middleware('auth');

require __DIR__.'/auth.php';
