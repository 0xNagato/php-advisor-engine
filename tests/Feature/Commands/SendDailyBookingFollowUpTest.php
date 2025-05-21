<?php

use App\Actions\Booking\CreateBooking;
use App\Enums\BookingStatus;
use App\Models\BookingCustomerReminderLog;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Notifications\Booking\CustomerFollowUp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Set up test prerequisites
    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'region' => 'miami',
        'timezone' => 'UTC',
    ]);
    $this->concierge = Concierge::factory()->create();
    $this->partner = Partner::factory()->create(['percentage' => 6]);

    $this->baseTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => Carbon::now('UTC')->addMinutes(60)->format('H:i:s'),
        'party_size' => 0,
    ]);

    $this->scheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => Carbon::now('UTC')->addMinutes(60)->format('H:i:s'),
        'day_of_week' => $this->baseTemplate->day_of_week,
        'party_size' => 2,
    ]);

    $this->action = new CreateBooking;
    actingAs($this->concierge->user);

    // Fake the job dispatcher
    Notification::fake();
});

it('sends follow-up SMS for eligible bookings from the previous day', function () {
    $nowUtc = Carbon::now('UTC');
    $yesterday = $nowUtc->copy()->subDay();

    // Create a booking directly in the database for yesterday
    $yesterdayDateTime = $yesterday->format('Y-m-d').' '.$this->scheduleTemplate->start_time;
    $pastBooking = \App\Models\Booking::factory()->create([
        'guest_count' => 2,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
    ]);

    $pastBooking->update([
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Ensure no reminder log exists initially
    $this->assertDatabaseMissing('booking_customer_reminder_logs', [
        'booking_id' => $pastBooking->id,
    ]);

    // Carbon set noon
    Carbon::setTestNow(now()->setTime(12, 0));

    // Simulate running the artisan command
    Artisan::call('prima:bookings-send-daily-customer-follow-up');

    // Assert that the notification was sent
    Notification::assertSentTo(
        [$pastBooking],
        CustomerFollowUp::class,
        function ($notification) use ($pastBooking) {
            return $notification->booking->id === $pastBooking->id;
        }
    );

    // Assert that a reminder log was created
    $this->assertDatabaseHas('booking_customer_reminder_logs', [
        'booking_id' => $pastBooking->id,
        'guest_phone' => $pastBooking->guest_phone,
    ]);

    // Clean up Carbon test now
    Carbon::setTestNow();
});

it('does not send follow-up notification for ineligible bookings from the previous day', function () {
    $nowUtc = Carbon::now('UTC');
    $yesterday = $nowUtc->copy()->subDay();

    // Create a booking with no guest phone (non-eligible)
    $yesterdayDateTime = $yesterday->format('Y-m-d').' '.$this->scheduleTemplate->start_time;
    $noPhoneBooking = \App\Models\Booking::factory()->create([
        'guest_count' => 2,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_phone' => null,
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Create a booking with an invalid status (non-eligible)
    $invalidStatusBooking = \App\Models\Booking::factory()->create([
        'guest_count' => 2,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::PENDING, // Invalid status
    ]);

    // Carbon set noon
    Carbon::setTestNow(now()->setTime(12, 0));

    Artisan::call('prima:bookings-send-daily-customer-follow-up');

    // Assert that no notifications were sent to ineligible bookings
    Notification::assertNotSentTo(
        [$noPhoneBooking, $invalidStatusBooking],
        CustomerFollowUp::class
    );

    // Assert that no reminder logs were created for ineligible bookings
    $this->assertDatabaseMissing('booking_customer_reminder_logs', [
        'booking_id' => $noPhoneBooking->id,
    ]);
    $this->assertDatabaseMissing('booking_customer_reminder_logs', [
        'booking_id' => $invalidStatusBooking->id,
    ]);

    // Clean up Carbon test now
    Carbon::setTestNow();
});

it('does not send follow-up SMS if a reminder log already exists', function () {
    $nowUtc = Carbon::now('UTC');
    $yesterday = $nowUtc->copy()->subDay();

    // Create a booking directly in the database for yesterday
    $yesterdayDateTime = $yesterday->format('Y-m-d').' '.$this->scheduleTemplate->start_time;
    $pastBooking = \App\Models\Booking::factory()->create([
        'guest_count' => 2,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Add a reminder log for this booking
    BookingCustomerReminderLog::factory()->create([
        'booking_id' => $pastBooking->id,
        'guest_phone' => $pastBooking->guest_phone,
        'sent_at' => now()->subHour(),
    ]);

    // Simulate running the artisan command
    Artisan::call('prima:bookings-send-daily-customer-follow-up');

    // Verify no notification is sent because the start time does not align
    Notification::assertNotSentTo(
        [$pastBooking],
        CustomerFollowUp::class
    );

    // Assert no duplicate reminder log was created
    $this->assertEquals(
        1,
        BookingCustomerReminderLog::query()->count()
    );
});

it('sends follow-up SMS to multiple eligible bookings', function () {
    $yesterday = Carbon::yesterday();

    // Create two eligible bookings directly
    $yesterdayDateTime = $yesterday->format('Y-m-d').' '.$this->scheduleTemplate->start_time;
    $pastBooking1 = \App\Models\Booking::factory()->create([
        'guest_count' => 2,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    $pastBooking2 = \App\Models\Booking::factory()->create([
        'guest_count' => 4,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_phone' => '+9876543210',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Ensure no reminder logs exist initially
    $this->assertDatabaseMissing('booking_customer_reminder_logs', [
        'booking_id' => $pastBooking1->id,
    ]);
    $this->assertDatabaseMissing('booking_customer_reminder_logs', [
        'booking_id' => $pastBooking2->id,
    ]);

    // Carbon set noon
    Carbon::setTestNow(now()->setTime(12, 0));

    // Simulate running the artisan command
    Artisan::call('prima:bookings-send-daily-customer-follow-up');

    // Assert that the notification was sent
    Notification::assertSentTo(
        [$pastBooking1],
        CustomerFollowUp::class,
        function ($notification) use ($pastBooking1) {
            return $notification->booking->id === $pastBooking1->id;
        }
    );
    Notification::assertSentTo(
        [$pastBooking2],
        CustomerFollowUp::class,
        function ($notification) use ($pastBooking2) {
            return $notification->booking->id === $pastBooking2->id;
        }
    );

    // Assert that a reminder log was created
    $this->assertDatabaseHas('booking_customer_reminder_logs', [
        'booking_id' => $pastBooking1->id,
        'guest_phone' => $pastBooking1->guest_phone,
    ]);
    $this->assertDatabaseHas('booking_customer_reminder_logs', [
        'booking_id' => $pastBooking2->id,
        'guest_phone' => $pastBooking2->guest_phone,
    ]);

    // Assert all reminder logs were created
    $this->assertEquals(
        2,
        BookingCustomerReminderLog::query()->count()
    );

    // Clean up Carbon test now
    Carbon::setTestNow();
});

it('sends SMS only if customer has a booking yesterday and not today', function () {
    $nowUtc = Carbon::now('UTC');
    $yesterday = $nowUtc->copy()->subDay(); // Explicit date for yesterday
    $today = $nowUtc->copy(); // Explicit date for today

    // Create a booking for yesterday directly
    $yesterdayDateTime = $yesterday->format('Y-m-d').' '.$this->scheduleTemplate->start_time;
    $yesterdayBooking = \App\Models\Booking::factory()->create([
        'guest_count' => 2,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Create a booking for today with the same guest_phone
    $todayBookingTime = $today->copy()->addHour()->format('H:i:s'); // Ensure it meets the 35-minute rule
    $todayBookingData = [
        'booking_at' => $today->format('Y-m-d').' '.$todayBookingTime, // Today
        'guest_count' => 2,
    ];

    $this->scheduleTemplate->update(['start_time' => $todayBookingTime]); // Adjust schedule start time for today

    // Make sure to include date in the booking data
    $todayResult = $this->action::run(
        $this->scheduleTemplate->id,
        array_merge($todayBookingData, ['date' => $today->format('Y-m-d')])
    );

    $todayBooking = $todayResult->booking;

    $todayBooking->update([
        'guest_phone' => '+1234567890', // Same guest phone as yesterday's booking
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Carbon set noon for testing
    Carbon::setTestNow(now()->setTime(12, 0));

    // Simulate running the artisan command
    Artisan::call('prima:bookings-send-daily-customer-follow-up');

    // Assert that SMS is not sent for the customer because they have a booking today
    Notification::assertNotSentTo(
        [$yesterdayBooking],
        CustomerFollowUp::class
    );

    // Assert no reminder log was created for yesterday's booking
    $this->assertDatabaseMissing('booking_customer_reminder_logs', [
        'booking_id' => $yesterdayBooking->id,
        'guest_phone' => $yesterdayBooking->guest_phone,
    ]);

    // Now let's simulate a case where only yesterday's booking exists
    $todayBooking->delete(); // Simulate no booking for today

    // Carbon set noon for testing
    Carbon::setTestNow(now()->setTime(12, 0));

    Artisan::call('prima:bookings-send-daily-customer-follow-up');

    // Assert that SMS is sent to the customer as they had a booking yesterday and no booking today
    Notification::assertSentTo(
        [$yesterdayBooking],
        CustomerFollowUp::class,
        function ($notification) use ($yesterdayBooking) {
            return $notification->booking->id === $yesterdayBooking->id;
        }
    );

    // Confirm that the reminder log is created in this case
    $this->assertDatabaseHas('booking_customer_reminder_logs', [
        'booking_id' => $yesterdayBooking->id,
        'guest_phone' => $yesterdayBooking->guest_phone,
    ]);

    // Carbon set noon for testing
    Carbon::setTestNow();
});

it('sends SMS only within the allowed time range in the venue timezone', function () {
    $nowUtc = Carbon::now('UTC');
    $yesterday = $nowUtc->copy()->subDay(); // Booking created for yesterday

    // Create a booking for yesterday directly
    $yesterdayDateTime = $yesterday->format('Y-m-d').' '.$this->scheduleTemplate->start_time;
    $yesterdayBooking = \App\Models\Booking::factory()->create([
        'guest_count' => 2,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Carbon set noon for testing
    Carbon::setTestNow(now()->setTime(12, 0));

    // Run the command
    Artisan::call('prima:bookings-send-daily-customer-follow-up');

    // Assert that the SMS is sent
    Notification::assertSentTo(
        [$yesterdayBooking],
        CustomerFollowUp::class,
        function ($notification) use ($yesterdayBooking) {
            return $notification->booking->id === $yesterdayBooking->id;
        }
    );

    // Clean up Carbon test now
    Carbon::setTestNow();
});

it('not sends SMS because not within the allowed time range in the venue timezone', function () {
    $nowUtc = Carbon::now('UTC');
    $yesterday = $nowUtc->copy()->subDay(); // Booking created for yesterday

    // Create a booking for yesterday directly
    $yesterdayDateTime = $yesterday->format('Y-m-d').' '.$this->scheduleTemplate->start_time;
    $yesterdayBooking = \App\Models\Booking::factory()->create([
        'guest_count' => 2,
        'booking_at' => $yesterdayDateTime,
        'booking_at_utc' => Carbon::parse($yesterdayDateTime, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_phone' => '+1234567890',
        'status' => BookingStatus::CONFIRMED,
    ]);

    // Carbon set noon for testing
    Carbon::setTestNow(now()->setTime(11, 30));

    // Run the command
    Artisan::call('prima:bookings-send-daily-customer-follow-up');

    // Assert that no SMS is sent
    Notification::assertNotSentTo(
        [$yesterdayBooking],
        CustomerFollowUp::class
    );

    // Assert no reminder logs were created
    $this->assertDatabaseMissing('booking_customer_reminder_logs', [
        'booking_id' => $yesterdayBooking->id,
    ]);

    // Clean up Carbon test now
    Carbon::setTestNow();
});
