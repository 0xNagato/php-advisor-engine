<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingCustomerReminderLog;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Notifications\Booking\CustomerReminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'timezone' => 'UTC',
        'region' => 'miami',
    ]);
    $this->concierge = Concierge::factory()->create();
    $this->partner = Partner::factory()->create(['percentage' => 6]);

    $this->baseTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => Carbon::now('UTC')->addMinutes(40)->format('H:i:s'),
        'party_size' => 0,
    ]);

    $this->scheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => Carbon::now('UTC')->addMinutes(40)->format('H:i:s'),
        'day_of_week' => $this->baseTemplate->day_of_week,
        'party_size' => 2,
    ]);

    actingAs($this->concierge->user);

    Notification::fake();
});

it('sends a booking reminder notification for eligible bookings', function () {
    $nowUtc = Carbon::now('UTC');

    // Explicitly create a booking with start time that falls within the 30-minute notification window
    // The notification command sends reminders for bookings 30 minutes before they start
    $this->scheduleTemplate->update([
        'start_time' => $nowUtc->copy()->addMinutes(40)->format('H:i:s'),
    ]);

    $bookingData = [
        'date' => $nowUtc->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $booking = Booking::factory()->create([
        'guest_count' => $bookingData['guest_count'],
        'booking_at' => $bookingData['date'] . ' ' . $this->scheduleTemplate->start_time,
        'booking_at_utc' => Carbon::parse($bookingData['date'] . ' ' . $this->scheduleTemplate->start_time, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
    ]);

    $booking->update([
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Ensure no reminder log exists initially
    $this->assertDatabaseMissing('booking_customer_reminder_logs', [
        'booking_id' => $booking->id,
    ]);

    // Force the booking time to be exactly 30 minutes from now to trigger the notification
    $booking->update([
        'booking_at_utc' => Carbon::now('UTC')->addMinutes(30),
    ]);

    Artisan::call('prima:bookings-send-customer-reminder');

    Notification::assertSentTo(
        [$booking],
        CustomerReminder::class,
        function ($notification) use ($booking) {
            return $notification->booking->id === $booking->id;
        }
    );

    // Assert that a reminder log was created
    $this->assertDatabaseHas('booking_customer_reminder_logs', [
        'booking_id' => $booking->id,
        'guest_phone' => $booking->guest_phone,
    ]);
});

it('does not send notifications for past or non-eligible bookings', function () {
    $nowUtc = Carbon::now('UTC');
    $yesterday = $nowUtc->copy()->subDay();

    // Create a past booking directly using the factory
    $yesterdayDateTime = $yesterday->format('Y-m-d').' '.$this->scheduleTemplate->start_time;
    $pastBooking = Booking::factory()->create([
        'guest_count' => 2,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Fixed booking data for non-eligible booking
    $nonEligibleBookingData = [
        'date' => $nowUtc->format('Y-m-d'),
        'booking_at' => $nowUtc->format('Y-m-d').' '.$this->scheduleTemplate->start_time,
        'guest_count' => 2,
    ];

    $nonEligibleBooking = Booking::factory()->create([
        'guest_count' => $nonEligibleBookingData['guest_count'],
        'booking_at' => $nonEligibleBookingData['booking_at'],
        'booking_at_utc' => Carbon::parse($nonEligibleBookingData['booking_at'], 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
    ]);

    $nonEligibleBooking->update([
        'guest_phone' => null,
        'status' => BookingStatus::PENDING,
    ]);

    Artisan::call('prima:bookings-send-customer-reminder');

    Notification::assertNotSentTo(
        [$pastBooking, $nonEligibleBooking],
        CustomerReminder::class
    );
});

it('does not send notifications when booking does not match the 40-minute threshold', function () {
    $nowUtc = Carbon::now('UTC');

    // Update the schedule template start time to be a few minutes outside the threshold
    $this->scheduleTemplate->update([
        'start_time' => $nowUtc->addMinutes(45)->format('H:i:s'), // Start time is 45 minutes from now
    ]);

    // Create a booking that would usually qualify
    $bookingData = [
        'date' => $nowUtc->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $booking = Booking::factory()->create([
        'guest_count' => $bookingData['guest_count'],
        'booking_at' => $bookingData['date'] . ' ' . $this->scheduleTemplate->start_time,
        'booking_at_utc' => Carbon::parse($bookingData['date'] . ' ' . $this->scheduleTemplate->start_time, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
    ]);

    // Update the booking with necessary valid fields
    $booking->update([
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Assert that the booking_at_utc is not within the 30-minute threshold
    // Assert that the booking_at_utc is not within the 40-minute threshold
    // (Note: Add 40 minutes to current UTC time to calculate the threshold)
    $notificationThreshold = $nowUtc->addMinutes(40);

    // Trigger the command to send reminders
    Artisan::call('prima:bookings-send-customer-reminder');

    // Verify no notification is sent because the start time does not align
    Notification::assertNotSentTo(
        [$booking],
        CustomerReminder::class
    );
});

it('does not send a reminder notification if a reminder log already exists', function () {
    $nowUtc = Carbon::now('UTC');

    // Create a booking
    $bookingData = [
        'date' => $nowUtc->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $booking = Booking::factory()->create([
        'guest_count' => $bookingData['guest_count'],
        'booking_at' => $bookingData['date'] . ' ' . $this->scheduleTemplate->start_time,
        'booking_at_utc' => Carbon::parse($bookingData['date'] . ' ' . $this->scheduleTemplate->start_time, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
    ]);

    // Update booking to make it eligible
    $booking->update([
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Create a reminder log for the booking
    BookingCustomerReminderLog::create([
        'booking_id' => $booking->id,
        'guest_phone' => $booking->guest_phone,
        'sent_at' => now(),
    ]);

    // Run the command to handle reminders
    Artisan::call('prima:bookings-send-customer-reminder');

    // Ensure no notification was sent
    Notification::assertNotSentTo(
        [$booking],
        CustomerReminder::class
    );

    // Assert no duplicate reminder log was created
    $this->assertEquals(
        1,
        BookingCustomerReminderLog::where('booking_id', $booking->id)->count()
    );
});
