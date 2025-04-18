<?php

use App\Actions\Booking\CreateBooking;
use App\Enums\BookingStatus;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Notifications\Booking\VenueContactBookingConfirmed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Create a venue with factory (includes contacts)
    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'timezone' => 'UTC',
    ]);

    // Create a schedule template for the venue
    $this->scheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => Carbon::now('UTC')->addMinutes(30)->format('H:i:s'),
        'day_of_week' => Carbon::now('UTC')->format('l'),
        'party_size' => 4,
    ]);

    // Create a concierge user
    $this->concierge = Concierge::factory()->create();

    // Act as the concierge
    actingAs($this->concierge->user);

    // Instantiate the CreateBooking action
    $this->action = new CreateBooking;

    // Fake notifications
    Notification::fake();
});

it('sends notification with a 5-minute delay to venue contacts if booking is confirmed', function () {
    // Arrange: Use the CreateBooking action to create a booking
    $nowUtc = Carbon::now('UTC');
    $bookingData = [
        'date' => $nowUtc->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $result = $this->action::run(
        $this->scheduleTemplate->id,
        $bookingData,
        'UTC',
        'USD'
    );

    $booking = $result->booking;

    // Update the booking status to "confirmed"
    $booking->update(['status' => BookingStatus::CONFIRMED]);

    // Act: Send notification to the venue contacts
    $delay = now()->addMinutes(5);
    $contacts = $this->venue->contacts ?? collect();

    $contacts
        ->filter(fn ($contact) => $contact->use_for_reservations)
        ->each(fn ($contact) => $contact->notify((new VenueContactBookingConfirmed(
            booking: $booking,
            confirmationUrl: 'https://example.com/confirm',
            reminder: false
        ))->delay($delay)));

    // Assert: Verify notification was sent to the correct contact(s)
    $contacts
        ->filter(fn ($contact) => $contact->use_for_reservations)
        ->each(fn ($contact) => Notification::assertSentTo($contact, VenueContactBookingConfirmed::class,
            function ($notification) use ($delay, $booking) {
                return $notification->booking->id === $booking->id &&
                    $notification->delay->equalTo($delay);
            }));

});

it('does not send notification to venue contacts if booking is canceled', function () {
    // Arrange: Use the CreateBooking action to create a booking
    $nowUtc = Carbon::now('UTC');
    $bookingData = [
        'date' => $nowUtc->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $result = $this->action::run(
        $this->scheduleTemplate->id,
        $bookingData,
        'UTC',
        'USD'
    );

    $booking = $result->booking;

    // Update the booking status to "canceled"
    $booking->update(['status' => BookingStatus::CANCELLED]);

    // Act: Attempt to send notification to the venue contacts
    $this->venue->contacts
        ->filter(fn ($contact) => $contact->use_for_reservations)
        ->each(function ($contact) use ($booking) {
            $contact->notify(new VenueContactBookingConfirmed(
                booking: $booking,
                confirmationUrl: 'https://example.com/confirm',
                reminder: false
            ));
        });

    // Assert: No notifications were sent
    Notification::assertNothingSent();
});
