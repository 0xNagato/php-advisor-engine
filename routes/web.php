<?php

use App\Http\Controllers\DownloadInvoiceController;
use App\Livewire\ConciergeInvitation;
use App\Livewire\CreateBooking;
use App\Livewire\CustomerInvoice;
use App\Livewire\RestaurantBookingConfirmation;

Route::get('/invoice/download/{uuid}', DownloadInvoiceController::class)
    ->name('customer.invoice.download');

Route::get('/invoice/{token}', CustomerInvoice::class)->name('customer.invoice');

Route::get('/restaurants/confirm/{token}', RestaurantBookingConfirmation::class)
    ->name('restaurants.confirm');

Route::get('/bookings/create/{token}', CreateBooking::class)->name('bookings.create');

Route::get('/invitation/{conciergeReferral}', ConciergeInvitation::class)
    ->name('concierge.invitation')
    ->middleware('signed');
