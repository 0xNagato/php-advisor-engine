<?php

use App\Actions\Booking\CreateBooking;
use App\Enums\BookingStatus;
use App\Enums\VenueType;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Notifications\Booking\VenueContactBookingConfirmed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Create a venue with a factory (includes contacts)
    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'timezone' => 'UTC',
        'region' => 'miami',
    ]);

    // Create a schedule template for the venue
    $this->scheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => Carbon::now('UTC')->addMinutes(40)->format('H:i:s'),
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

    $booking = Booking::factory()->create([
        'guest_count' => $bookingData['guest_count'],
        'booking_at' => $bookingData['date'].' '.$this->scheduleTemplate->start_time,
        'booking_at_utc' => Carbon::parse($bookingData['date'].' '.$this->scheduleTemplate->start_time, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
    ]);

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

    $booking = Booking::factory()->create([
        'guest_count' => $bookingData['guest_count'],
        'booking_at' => $bookingData['date'].' '.$this->scheduleTemplate->start_time,
        'booking_at_utc' => Carbon::parse($bookingData['date'].' '.$this->scheduleTemplate->start_time, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
    ]);

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

// Test group for Ibiza Hike Station time formatting
describe('Ibiza Hike Station time formatting', function () {
    beforeEach(function () {
        // Create a hike station venue
        $this->hikeVenue = Venue::factory()->create([
            'name' => 'Ibiza Hike Station',
            'venue_type' => VenueType::HIKE_STATION,
            'timezone' => 'Europe/Madrid',
            'region' => 'ibiza',
        ]);

        // Create a schedule template for the hike venue
        $this->hikeScheduleTemplate = ScheduleTemplate::factory()->create([
            'venue_id' => $this->hikeVenue->id,
            'start_time' => '10:00:00',
            'day_of_week' => Carbon::now('Europe/Madrid')->format('l'),
            'party_size' => 8,
        ]);

        // Get the first contact for testing
        $this->contact = $this->hikeVenue->contacts->first();
    });

    it('formats morning hike times correctly (6 AM - 1:59 PM)', function () {
        $morningTimes = [
            '06:00:00' => 'Jul 25th - Morning Hike',
            '10:00:00' => 'Jul 25th - Morning Hike',
            '13:59:00' => 'Jul 25th - Morning Hike',
        ];

        foreach ($morningTimes as $time => $expectedFormat) {
            // Create booking at specific time
            $booking = Booking::factory()->create([
                'booking_at' => '2024-07-25 '.$time,
                'booking_at_utc' => Carbon::parse('2024-07-25 '.$time, 'Europe/Madrid')->utc(),
                'status' => BookingStatus::CONFIRMED,
                'concierge_id' => $this->concierge->id,
                'schedule_template_id' => $this->hikeScheduleTemplate->id,
            ]);

            // Create notification
            $notification = new VenueContactBookingConfirmed(
                booking: $booking,
                confirmationUrl: 'https://primavip.co/t/test123',
                reminder: false
            );

            // Test SMS formatting
            $smsData = $notification->toSMS($this->contact);

            expect($smsData->templateKey)->toBe('venue_contact_booking_confirmed_hike')
                ->and($smsData->templateData['booking_date_time'])->toBe($expectedFormat);
        }
    });

    it('formats sunset hike times correctly (2 PM and later)', function () {
        $sunsetTimes = [
            '14:00:00' => 'Jul 25th - Sunset Hike',
            '16:30:00' => 'Jul 25th - Sunset Hike',
            '18:00:00' => 'Jul 25th - Sunset Hike',
            '20:00:00' => 'Jul 25th - Sunset Hike',
        ];

        foreach ($sunsetTimes as $time => $expectedFormat) {
            // Create booking at specific time
            $booking = Booking::factory()->create([
                'booking_at' => '2024-07-25 '.$time,
                'booking_at_utc' => Carbon::parse('2024-07-25 '.$time, 'Europe/Madrid')->utc(),
                'status' => BookingStatus::CONFIRMED,
                'concierge_id' => $this->concierge->id,
                'schedule_template_id' => $this->hikeScheduleTemplate->id,
            ]);

            // Create notification
            $notification = new VenueContactBookingConfirmed(
                booking: $booking,
                confirmationUrl: 'https://primavip.co/t/test123',
                reminder: false
            );

            // Test SMS formatting
            $smsData = $notification->toSMS($this->contact);

            expect($smsData->templateKey)->toBe('venue_contact_booking_confirmed_hike')
                ->and($smsData->templateData['booking_date_time'])->toBe($expectedFormat);
        }
    });

    it('uses special hike templates for SMS notifications', function () {
        $booking = Booking::factory()->create([
            'booking_at' => '2024-07-25 10:00:00',
            'booking_at_utc' => Carbon::parse('2024-07-25 10:00:00', 'Europe/Madrid')->utc(),
            'status' => BookingStatus::CONFIRMED,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => $this->hikeScheduleTemplate->id,
            'notes' => null,
        ]);

        // Test regular notification
        $notification = new VenueContactBookingConfirmed(
            booking: $booking,
            confirmationUrl: 'https://primavip.co/t/test123',
            reminder: false
        );

        $smsData = $notification->toSMS($this->contact);
        expect($smsData->templateKey)->toBe('venue_contact_booking_confirmed_hike');

        // Test reminder notification
        $reminderNotification = new VenueContactBookingConfirmed(
            booking: $booking,
            confirmationUrl: 'https://primavip.co/t/test123',
            reminder: true
        );

        $reminderSmsData = $reminderNotification->toSMS($this->contact);
        expect($reminderSmsData->templateKey)->toBe('venue_contact_booking_confirmed_reminder_hike');
    });

    it('uses special hike templates with notes', function () {
        $booking = Booking::factory()->create([
            'booking_at' => '2024-07-25 14:00:00',
            'booking_at_utc' => Carbon::parse('2024-07-25 14:00:00', 'Europe/Madrid')->utc(),
            'status' => BookingStatus::CONFIRMED,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => $this->hikeScheduleTemplate->id,
            'notes' => 'Dietary restrictions: vegetarian',
        ]);

        // Test regular notification with notes
        $notification = new VenueContactBookingConfirmed(
            booking: $booking,
            confirmationUrl: 'https://primavip.co/t/test123',
            reminder: false
        );

        $smsData = $notification->toSMS($this->contact);
        expect($smsData->templateKey)->toBe('venue_contact_booking_confirmed_notes_hike')
            ->and($smsData->templateData['notes'])->toBe('Dietary restrictions: vegetarian');

        // Test reminder notification with notes
        $reminderNotification = new VenueContactBookingConfirmed(
            booking: $booking,
            confirmationUrl: 'https://primavip.co/t/test123',
            reminder: true
        );

        $reminderSmsData = $reminderNotification->toSMS($this->contact);
        expect($reminderSmsData->templateKey)->toBe('venue_contact_booking_confirmed_reminder_notes_hike');
    });
});

// Test group for regular venue formatting
describe('Regular venue time formatting', function () {
    it('formats regular venue times with @ symbol', function () {
        $booking = Booking::factory()->create([
            'booking_at' => '2024-07-25 19:30:00',
            'booking_at_utc' => Carbon::parse('2024-07-25 19:30:00', 'UTC')->utc(),
            'status' => BookingStatus::CONFIRMED,
            'concierge_id' => $this->concierge->id,
            'schedule_template_id' => $this->scheduleTemplate->id,
        ]);

        $notification = new VenueContactBookingConfirmed(
            booking: $booking,
            confirmationUrl: 'https://primavip.co/t/test123',
            reminder: false
        );

        $smsData = $notification->toSMS($this->venue->contacts->first());

        expect($smsData->templateKey)->toBe('venue_contact_booking_confirmed')
            ->and($smsData->templateData['booking_date'])->toBe('Jul 25th')
            ->and($smsData->templateData['booking_time'])->toBe('7:30pm');
    });
});
