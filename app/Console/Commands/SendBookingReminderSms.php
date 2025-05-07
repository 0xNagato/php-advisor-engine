<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingCustomerReminderLog;
use App\Notifications\Booking\CustomerReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

class SendBookingReminderSms extends Command
{
    protected $signature = 'prima:bookings-send-customer-reminder';

    protected $description = 'Send SMS reminders to customers 30 minutes before their booking time';

    public function handle(): void
    {
        // Get the current UTC time
        $nowUtc = Carbon::now('UTC');

        // Find bookings that are 30 minutes from now
        $bookings = Booking::query()
            ->with('venue')
            ->whereNotNull('guest_phone')
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->whereBetween('booking_at_utc', [$nowUtc->copy()->addMinutes(29), $nowUtc->copy()->addMinutes(34)])
            ->whereDoesntHave('reminderLogs', function (Builder $query) {
                $query->whereColumn('booking_id', 'bookings.id');
            })
            ->whereHas('venue', function (Builder $query) {
                // Exclude Ibiza Hike Station venues (venue_type = 'hike_station')
                $query->where('venue_type', '!=', \App\Enums\VenueType::HIKE_STATION->value);
            })
            ->get();

        $count = $bookings->count();

        if ($count === 0) {
            $this->info('No bookings found for reminders.');

            return;
        }

        foreach ($bookings as $booking) {
            $venueTimezone = $booking->venue->timezone;
            $bookingTimeInVenueTimezone = Carbon::parse($booking->booking_at_utc)->setTimezone($venueTimezone);

            // Log activity for sending reminder
            activity()
                ->performedOn($booking)
                ->withProperties([
                    'action' => 'send_sms_reminder',
                    'booking_id' => $booking->id,
                    'guest_phone' => $booking->guest_phone,
                    'venue_name' => $booking->venue->name,
                    'booking_time' => $bookingTimeInVenueTimezone->format('Y-m-d H:i:s T'),
                ])
                ->log('Sent SMS reminder to customer 30 minutes before booking time.');

            // Send SMS notification
            $booking->notify(new CustomerReminder($booking));

            // Log the reminder in the database
            BookingCustomerReminderLog::query()->create([
                'booking_id' => $booking->id,
                'guest_phone' => $booking->guest_phone,
                'sent_at' => now(),
            ]);

            $this->info("SMS reminder sent to {$booking->guest_phone} for booking ID {$booking->id}.");
        }

        $this->info("Successfully processed {$count} booking reminders.");
    }
}
