<?php

namespace App\Listeners;

use App\Events\BookingPaid;
use App\Services\SmsService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use ShortURL;

class SendCustomerBookingNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BookingPaid $event): void
    {
        $bookingDate = $this->getFormattedDate($event->booking->booking_at);

        $bookingTime = $event->booking->booking_at->format('g:ia');

        $invoiceUrl = ShortURL::destinationUrl(route('customer.invoice', $event->booking->uuid))->make()->default_short_url;

        if ($event->booking->is_prime) {
            $message = "PRIMA reservation at {$event->booking->restaurant->restaurant_name} $bookingDate at $bookingTime with {$event->booking->guest_count} guests. View your invoice at $invoiceUrl.";
        } else {
            $message = "Hello from PRIMA VIP! Your reservation at {$event->booking->restaurant->restaurant_name} $bookingDate at $bookingTime has been booked by {$event->booking->concierge->user->name} and is now confirmed. Please arrive within 15 minutes of your reservation or your table may be released. Thank you for booking with us!";
        }

        app(SmsService::class)->sendMessage($event->booking->guest_phone, $message);
    }

    private function getFormattedDate(CarbonInterface $date): string
    {
        $today = now();
        $tomorrow = now()->addDay();

        if ($date->isSameDay($today)) {
            return 'today';
        }

        if ($date->isSameDay($tomorrow)) {
            return 'tomorrow';
        }

        return $date->format('l \\t\\h\\e jS');
    }
}
