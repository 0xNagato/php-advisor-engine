<?php

use App\Http\Controllers\Booking\BookingCheckoutController;
use App\Http\Controllers\DemoAuthController;
use App\Http\Controllers\DownloadInvoiceController;
use App\Http\Controllers\ExceptionFormController;
use App\Livewire\Booking\CreateBooking;
use App\Livewire\Booking\CustomerInvoice;
use App\Livewire\Concierge\ConciergeInvitation;
use App\Livewire\Venue\VenueBookingConfirmation;
use App\Livewire\Venue\VenueContactLogin;
use App\Livewire\Venue\VenueContactRecentBookings;
use App\Livewire\Venue\VenueSpecialRequestConfirmation;

Route::get('/', static function () {
    return view('web.index');
});

Route::redirect('/login', '/platform/login');

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
Route::get('/venues/contact-bookings', VenueContactRecentBookings::class)->name('venue.contact.bookings')->middleware('signed');

Route::post('/exception-form', ExceptionFormController::class)->name('exception.form');

require __DIR__.'/auth.php';
