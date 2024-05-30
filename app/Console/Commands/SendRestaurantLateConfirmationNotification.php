<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\RestaurantBookingLateConfirmationService;
use Illuminate\Console\Command;

class SendRestaurantLateConfirmationNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-restaurant-late-confirmation-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify admins about restaurant bookings that have not been confirmed by the restaurant';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Booking::query()->whereNull('restaurant_confirmed_at')
            ->whereNull('resent_restaurant_confirmation_at')
            ->where('confirmed_at', '<=', now()->subMinutes(15))
            ->each(function ($booking) {
                app(RestaurantBookingLateConfirmationService::class)->sendNotification($booking);
            });
    }
}
