<?php

use App\Actions\Booking\SendModificationRequestToVenueContacts;
use App\Data\SmsData;
use App\Models\Booking;
use App\Models\BookingModificationRequest;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Notifications\Booking\VenueContactModificationRequested;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $nowUtc = Carbon::now('UTC');

    // Create a venue with a contact that will receive notifications
    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'timezone' => 'UTC',
        'region' => 'miami',
    ]);

    $this->concierge = Concierge::factory()->create();

    $this->baseTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => Carbon::now('UTC')->addMinutes(40)->format('H:i:s'),
        'party_size' => 0,
    ]);

    // Create a schedule template for the booking
    $this->scheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => $nowUtc->addMinutes(40)->format('H:i:s'),
        'day_of_week' => $this->baseTemplate->day_of_week,
        'party_size' => 2,
    ]);

    $bookingData = [
        'date' => $nowUtc->format('Y-m-d'),
        'guest_count' => 2,
    ];

    // Create the initial booking
    $this->booking = Booking::factory()->create([
        'guest_count' => $bookingData['guest_count'],
        'booking_at' => $bookingData['date'].' '.$this->scheduleTemplate->start_time,
        'booking_at_utc' => Carbon::parse($bookingData['date'].' '.$this->scheduleTemplate->start_time, 'UTC'),
        'concierge_id' => $this->concierge->id,
        'schedule_template_id' => $this->scheduleTemplate->id,
    ]);

    Notification::fake();
    actingAs($this->concierge->user);
});

it('sends notification with correct message when only party size is changed', function () {
    // 1. Create Modification Request
    $modificationRequest = BookingModificationRequest::factory()->create([
        'booking_id' => $this->booking->id,
        'original_guest_count' => $this->booking->guest_count,
        'requested_guest_count' => 4, // Changed from 2 to 4
        'original_time' => $this->booking->booking_at->format('H:i:s'),
        'requested_time' => $this->booking->booking_at->format('H:i:s'), // Same time
        'original_booking_at' => $this->booking->booking_at,
        'request_booking_at' => $this->booking->booking_at, // Same date
    ]);

    $contact = $this->venue->contacts->first();

    // 2. Act: Get SMS data from notification
    // Use SendModificationRequestToVenueContacts action to generate the short URL
    SendModificationRequestToVenueContacts::run($modificationRequest);

    // Get the short URL from the database
    $shortUrl = getLastShortUrl('modification-request');

    $notification = new VenueContactModificationRequested($modificationRequest, $shortUrl->default_short_url);
    $smsData = $notification->toSMS($contact);

    // 3. Assert
    $expectedChanges = 'Party size: 4';

    expect($smsData)->toBeInstanceOf(SmsData::class)
        ->and($smsData->phone)->toBe($contact->contact_phone)
        ->and($smsData->templateKey)->toBe('venue_modification_request')
        ->and($smsData->templateData['changes_requested'])->toBe($expectedChanges);
});

it('sends notification with correct message when only time is changed', function () {
    $requestedTime = '20:00:00'; // 8:00 PM

    $modificationRequest = BookingModificationRequest::factory()->create([
        'booking_id' => $this->booking->id,
        'original_guest_count' => $this->booking->guest_count,
        'requested_guest_count' => $this->booking->guest_count, // Same guest count
        'original_time' => $this->booking->booking_at->format('H:i:s'),
        'requested_time' => $requestedTime, // Changed time
        'original_booking_at' => $this->booking->booking_at,
        'request_booking_at' => $this->booking->booking_at, // Same date
    ]);

    $contact = $this->venue->contacts->first();

    // Use SendModificationRequestToVenueContacts action to generate the short URL
    SendModificationRequestToVenueContacts::run($modificationRequest);

    // Get the short URL from the database
    $shortUrl = getLastShortUrl('modification-request');

    $notification = new VenueContactModificationRequested($modificationRequest, $shortUrl->default_short_url);
    $smsData = $notification->toSMS($contact);

    $expectedChanges = 'Time: '.date('g:ia', strtotime($requestedTime));

    expect($smsData->templateData['changes_requested'])->toBe($expectedChanges);
});

it('sends notification with correct message when only date is changed', function () {
    $requestedDate = Carbon::parse($this->booking->booking_at)->addDay();

    $modificationRequest = BookingModificationRequest::factory()->create([
        'booking_id' => $this->booking->id,
        'original_guest_count' => $this->booking->guest_count,
        'requested_guest_count' => $this->booking->guest_count, // Same guest count
        'original_time' => $this->booking->booking_at->format('H:i:s'),
        'requested_time' => $this->booking->booking_at->format('H:i:s'), // Same time
        'original_booking_at' => $this->booking->booking_at,
        'request_booking_at' => $requestedDate, // Changed date
    ]);

    $contact = $this->venue->contacts->first();

    // Use SendModificationRequestToVenueContacts action to generate the short URL
    SendModificationRequestToVenueContacts::run($modificationRequest);

    // Get the short URL from the database
    $shortUrl = getLastShortUrl('modification-request');

    $notification = new VenueContactModificationRequested($modificationRequest, $shortUrl->default_short_url);
    $smsData = $notification->toSMS($contact);

    $expectedChanges = 'Date: '.$requestedDate->format('M j, Y');

    expect($smsData->templateData['changes_requested'])->toBe($expectedChanges);
});

it('sends notification with correct message when all details are changed', function () {
    $requestedGuestCount = 6;
    $requestedTime = '21:30:00'; // 9:30 PM
    $requestedDate = Carbon::parse($this->booking->booking_at)->addDays(2);

    // 1. Create Modification Request
    $modificationRequest = BookingModificationRequest::factory()->create([
        'booking_id' => $this->booking->id,
        'requested_guest_count' => $requestedGuestCount,
        'requested_time' => $requestedTime,
        'request_booking_at' => $requestedDate,
    ]);

    $contact = $this->venue->contacts->first();

    // Use SendModificationRequestToVenueContacts action to generate the short URL
    SendModificationRequestToVenueContacts::run($modificationRequest);

    // Get the short URL from the database
    $shortUrl = getLastShortUrl('modification-request');

    // 2. Act
    $notification = new VenueContactModificationRequested($modificationRequest, $shortUrl->default_short_url);
    $smsData = $notification->toSMS($contact);

    // 3. Assert
    $expectedChanges = implode(', ', [
        "Party size: {$requestedGuestCount}",
        'Date: '.$requestedDate->format('M j, Y'),
        'Time: '.date('g:ia', strtotime($requestedTime)),
    ]);

    expect($smsData)->toBeInstanceOf(SmsData::class)
        ->and($smsData->phone)->toBe($contact->contact_phone)
        ->and($smsData->templateKey)->toBe('venue_modification_request')
        ->and($smsData->templateData['changes_requested'])->toBe($expectedChanges);
});
