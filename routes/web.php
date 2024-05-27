<?php

use App\Http\Controllers\DemoAuthController;
use App\Http\Controllers\DownloadInvoiceController;
use App\Http\Controllers\ExceptionFormController;
use App\Livewire\Booking\CreateBooking;
use App\Livewire\Booking\CustomerInvoice;
use App\Livewire\Concierge\ConciergeInvitation;
use App\Livewire\Restaurant\RestaurantBookingConfirmation;
use App\Livewire\Restaurant\RestaurantSpecialRequestConfirmation;

Route::get('/invoice/download/{uuid}', DownloadInvoiceController::class)
    ->name('customer.invoice.download');

Route::get('/invoice/{token}', CustomerInvoice::class)->name('customer.invoice');

Route::get('/restaurants/confirm/{token}', RestaurantBookingConfirmation::class)
    ->name('restaurants.confirm');

Route::get('/restaurants/confirm/special-request/{token}', RestaurantSpecialRequestConfirmation::class)
    ->name('restaurants.confirm-special-request');

Route::get('/bookings/create/{token}', CreateBooking::class)->name('bookings.create');

Route::get('/invitation/{referral}', ConciergeInvitation::class)
    ->name('concierge.invitation')
    ->middleware('signed');

Route::get('/demo/auth/{user_id}', [DemoAuthController::class, 'auth'])->name('demo.auth');
Route::get('/demo/redirect', [DemoAuthController::class, 'redirect'])->name('demo.redirect');

Route::post('/exception-form', ExceptionFormController::class)->name('exception.form');
