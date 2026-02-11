<?php

use App\Filament\Pages\CreatePassword;
use App\Http\Controllers\Admin\BookingCalculatorController;
use App\Http\Controllers\Booking\BookingCheckoutController;
use App\Http\Controllers\DemoAuthController;
use App\Http\Controllers\DownloadInvoiceController;
use App\Http\Controllers\DownloadVenueGroupInvoiceController;
use App\Http\Controllers\DownloadVenueInvoiceController;
use App\Http\Controllers\ExceptionFormController;
use App\Http\Controllers\PublicAnnouncementController;
use App\Http\Controllers\VenueAgreementController;
use App\Livewire\Booking\CreateBooking;
use App\Livewire\Booking\CustomerInvoice;
use App\Livewire\Booking\ModifyDetails;
use App\Livewire\Concierge\ConciergeInvitation;
use App\Livewire\Concierge\DirectConciergeInvitation;
use App\Livewire\Venue\VenueBookingConfirmation;
use App\Livewire\Venue\VenueContactLogin;
use App\Livewire\Venue\VenueContactRecentBookings;
use App\Livewire\Venue\VenueModificationRequestConfirmation;
use App\Livewire\Venue\VenueSpecialRequestConfirmation;
use App\Livewire\VenueOnboarding;
use App\Livewire\Vip\AvailabilityCalendar;
// use App\Models\User;
use AshAllenDesign\ShortURL\Controllers\ShortURLController;
// Removed temporary diagnostics: no longer need DB/Hash/Redis here
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Redis as RedisFacade;
use Illuminate\Support\Facades\Route;

Route::get('/privacy', function () {
    return view('privacy');
});

// Temporary diagnostics removed after verification

Route::get('/announcement/{message}', [PublicAnnouncementController::class, 'show'])
    ->name('public.announcement');

// Marketing site routes (promoted to root)
Route::get('/', static function () {
    if (app()->environment('production')) {
        return redirect('https://primaapp.com');
    }

    return view('site.index');
})->name('home');

Route::get('/hotels', static function () {
    return view('site.hotels');
})->name('hotels');

Route::get('/restaurants', static function () {
    return view('site.restaurants');
})->name('restaurants');

Route::get('/concierges', static function () {
    return view('site.concierges');
})->name('concierges');

Route::get('/influencers', static function () {
    return view('site.influencers');
})->name('influencers');

Route::get('/onboarding/{token?}', VenueOnboarding::class)->name('onboarding');
/**
 * @deprecated
 */
Route::get('/onboarding/token/{token?}', VenueOnboarding::class)->name('onboarding.token');

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

Route::redirect('/login', '/platform/login');

Route::redirect('/platform/app/login', config('app.platform_url'));

Route::get('/invoice/download/{uuid}', DownloadInvoiceController::class)
    ->name('customer.invoice.download');

Route::get('/invoice/{token}', CustomerInvoice::class)->name('customer.invoice');
Route::get('/modify/{token}', ModifyDetails::class)->name('modify.booking');

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

Route::get('vip/login/{code?}', fn ($code = null) => redirect($code ? "/v/$code" : '/'))->name('vip.login');

Route::get('vip/{code}', AvailabilityCalendar::class)->name('vip.booking');
Route::get('v/{code}', function ($code) {
    $queryParams = request()->query();
    $redirectUrl = config('app.booking_url')."/vip/{$code}";

    if (! empty($queryParams)) {
        $redirectUrl .= '?'.http_build_query($queryParams);
    }

    return redirect($redirectUrl);
})
    ->name('v.booking');
Route::get('v/calendar', fn () => redirect(config('app.booking_url')))->name('v.calendar');

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

    // Venue Agreement routes
    Route::get('agreement/{onboarding}', [VenueAgreementController::class, 'show'])
        ->name('agreement')
        ->where('onboarding', '.*'); // Allow any character in the encrypted ID

    // Create a public download endpoint
    Route::get(
        'public-agreement-download/{onboarding}',
        [VenueAgreementController::class, 'publicDownload']
    )
        ->name('agreement.public-download')
        ->where('onboarding', '.*'); // Allow any character in the encrypted ID

    // Email endpoint removed as it's now handled by the Livewire component
});

Route::get('/join/{type}/{id}', DirectConciergeInvitation::class)
    ->name('join.concierge')
    ->middleware(['signed'])->where('type', 'concierge|partner|venue_manager');

Route::get('/join/generic/{type}', DirectConciergeInvitation::class)
    ->name('join.generic');

Route::get('/qr/unassigned/{qrCode}', [App\Http\Controllers\QrCodeRedirectController::class, 'handleUnassigned'])
    ->name('qr.unassigned')
    ->missing(function () {
        return response()->view('errors.qr-not-found', [
            'message' => 'This QR code was not found.',
            'support_message' => 'Please verify the code or contact support.',
        ], 404);
    });

Route::get('/invitation/{referral}', ConciergeInvitation::class)
    ->name('concierge.invitation');

Route::get('password/create/{token}', CreatePassword::class)
    ->name('password.create')
    ->middleware('signed');

Route::get('venue-manager/invitation/{referral}', App\Filament\Pages\VenueManager\AcceptInvitation::class)
    ->name('venue-manager.invitation')
    ->middleware(['signed']);

Route::get('venue-invoice/{venue}/{startDate}/{endDate}', DownloadVenueInvoiceController::class)
    ->name('venue.invoice.download')
    ->middleware('auth');

Route::get('/venue-group-invoice/{venueGroup}/{startDate}/{endDate}', DownloadVenueGroupInvoiceController::class)
    ->name('venue-group.invoice.download')
    ->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    // Venue manager routes
    Route::get('/venue-manager/add-venue', VenueOnboarding::class)->name('venue-manager.add-venue');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/booking-calculator', [BookingCalculatorController::class, 'index'])
        ->name('admin.booking-calculator');
});

// Session keepalive heartbeat to prevent 419s while actively using Filament
Route::post('/heartbeat', function () {
    return response()->noContent();
})->middleware(['web', 'auth'])->name('heartbeat');

// VIP Code print route
Route::get('/vip-code/print', [App\Http\Controllers\VipCodeController::class, 'printQRCode'])->name('vip-code.print');

require __DIR__.'/auth.php';
