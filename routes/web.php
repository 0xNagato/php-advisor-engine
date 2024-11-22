<?php

use App\Http\Controllers\Booking\BookingCheckoutController;
use App\Http\Controllers\DemoAuthController;
use App\Http\Controllers\DownloadInvoiceController;
use App\Http\Controllers\ExceptionFormController;
use App\Http\Controllers\Vip\LogoutController;
use App\Livewire\Booking\CreateBooking;
use App\Livewire\Booking\CustomerInvoice;
use App\Livewire\Concierge\ConciergeInvitation;
use App\Livewire\Venue\VenueBookingConfirmation;
use App\Livewire\Venue\VenueContactLogin;
use App\Livewire\Venue\VenueContactRecentBookings;
use App\Livewire\Venue\VenueSpecialRequestConfirmation;
use App\Livewire\VenueOnboarding;
use App\Livewire\Vip\AvailabilityCalendar;
use App\Livewire\Vip\Login;
use AshAllenDesign\ShortURL\Controllers\ShortURLController;

Route::get('/privacy', function () {
    return view('privacy');
});

Route::get('/onboarding', VenueOnboarding::class)->name('onboarding');

Route::redirect('/app', 'https://apps.apple.com/us/app/prima-vip/id6504947227');

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

Route::get('/venues/confirm/{token}', VenueBookingConfirmation::class)
    ->name('venues.confirm');

Route::get('/venues/confirm/special-request/{token}', VenueSpecialRequestConfirmation::class)
    ->name('venues.confirm-special-request');

// Old customer booking flow
Route::get('/bookings/create/{token}', CreateBooking::class)->name('bookings.create');

// New customer booking flow
Route::get('/checkout/{booking:uuid}', BookingCheckoutController::class)->name('booking.checkout');

Route::get('/invitation/{referral}', ConciergeInvitation::class)
    ->name('concierge.invitation')
    ->middleware('signed');

Route::get('/demo/auth/{user_id}', [DemoAuthController::class, 'auth'])->name('demo.auth');
Route::get('/demo/redirect', [DemoAuthController::class, 'redirect'])->name('demo.redirect');

Route::get('/platform/login/venue', VenueContactLogin::class)->name('venue.login');
Route::get('/venues/contact-bookings', VenueContactRecentBookings::class)
    ->name('venue.contact.bookings')->middleware('signed');

Route::post('/exception-form', ExceptionFormController::class)->name('exception.form');

Route::group(['prefix' => 'vip', 'as' => 'vip.'], function () {
    Route::get('login/{code?}', Login::class)->name('login');
    Route::group(['middleware' => 'vip'], function () {
        Route::get('booking', AvailabilityCalendar::class)->name('booking');
        Route::get('logout', LogoutController::class)->name('logout');
    });
});

require __DIR__.'/auth.php';
