<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });
//
// Route::middleware([
//     'auth:sanctum',
//     config('jetstream.auth_session'),
//     'verified',
// ])->group(function () {
//     Route::get('/dashboard', function () {
//         return view('dashboard');
//     })->name('dashboard');
// });

use App\Http\Controllers\DownloadInvoiceController;
use App\Livewire\CreateBooking;
use App\Livewire\CustomerInvoice;
use App\Livewire\RestaurantBookingConfirmation;

Route::get('/invoice/download/{uuid}', DownloadInvoiceController::class)
    ->name('customer.invoice.download');
Route::get('/invoice/{token}', CustomerInvoice::class)->name('customer.invoice');
Route::get('/restaurants/confirm/{token}', RestaurantBookingConfirmation::class)
    ->name('restaurants.confirm');
Route::get('/bookings/create/{token}', CreateBooking::class)->name('bookings.create');
Route::get('/.well-known/apple-developer-merchantid-domain-association', function () {
    return response()->file(public_path('.well-known/apple-developer-merchantid-domain-association'));
});
