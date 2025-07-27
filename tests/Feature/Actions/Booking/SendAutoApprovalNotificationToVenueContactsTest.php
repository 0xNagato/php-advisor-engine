<?php

use App\Actions\Booking\SendAutoApprovalNotificationToVenueContacts;
use App\Models\Booking;
use App\Models\Venue;
use App\Notifications\Booking\VenueContactBookingAutoApproved;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->venue = Venue::factory()->create(['name' => 'Test Restaurant']);
    $this->scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
    ]);
    $this->booking = Booking::factory()->create([
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => 5,
        'venue_confirmed_at' => now(),
        'booking_at' => now()->addDay(),
    ]);
});

it('sends notifications to all venue contacts with use_for_reservations enabled', function () {
    Notification::fake();

    // Venue factory already creates contacts with use_for_reservations = true
    $contacts = $this->venue->contacts;

    SendAutoApprovalNotificationToVenueContacts::run($this->booking);

    foreach ($contacts as $contact) {
        if ($contact->use_for_reservations) {
            Notification::assertSentTo($contact, VenueContactBookingAutoApproved::class);
        }
    }
});

it('sends notifications without verbose logging', function () {
    // Fake notifications to prevent actual sending
    Notification::fake();

    // We removed the verbose success logging, so only allow error logs if notifications fail
    Log::shouldReceive('error')->zeroOrMoreTimes();

    SendAutoApprovalNotificationToVenueContacts::run($this->booking);

    // Verify notifications were sent (this is the important behavior)
    $contacts = $this->venue->contacts->filter(fn ($c) => $c->use_for_reservations);
    foreach ($contacts as $contact) {
        Notification::assertSentTo($contact, VenueContactBookingAutoApproved::class);
    }
});

it('logs warning when no venue contacts found', function () {
    // Temporarily disable additional venue notification phones
    config(['app.venue_booking_notification_phones' => '']);

    // Create venue with no contacts
    $emptyVenue = Venue::factory()->create([
        'name' => 'Empty Restaurant',
        'contacts' => [],
    ]);

    $scheduleTemplate = \App\Models\ScheduleTemplate::factory()->create([
        'venue_id' => $emptyVenue->id,
    ]);

    $emptyBooking = Booking::factory()->create([
        'schedule_template_id' => $scheduleTemplate->id,
        'guest_count' => 3,
        'venue_confirmed_at' => now(),
        'booking_at' => now()->addDay(),
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->with("No venue contacts found for auto-approval notification for booking {$emptyBooking->id} at {$emptyVenue->name}");

    // Allow for potential error logs if notifications fail
    Log::shouldReceive('error')->zeroOrMoreTimes();

    SendAutoApprovalNotificationToVenueContacts::run($emptyBooking);
});
