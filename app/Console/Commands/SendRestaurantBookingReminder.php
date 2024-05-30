<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\RestaurantContactBookingConfirmationService;
use AshAllenDesign\ShortURL\Exceptions\ShortURLException;
use Illuminate\Console\Command;

class SendRestaurantBookingReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-restaurant-booking-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a reminder SMS for bookings that have not been confirmed by the restaurant';

    /**
     * Execute the console command.
     *
     * @throws ShortURLException
     */
    public function handle(): void
    {
        Booking::query()->whereNull('restaurant_confirmed_at')
            ->whereNull('resent_restaurant_confirmation_at')
            ->where('confirmed_at', '<=', now()->subMinutes(30))
            ->each(function ($booking) {
                app(RestaurantContactBookingConfirmationService::class)->sendConfirmation($booking);
                $booking->update(['resent_restaurant_confirmation_at' => now()]);
            });
    }
}
