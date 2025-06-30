<?php

use App\Actions\Booking\CreateBooking;
use App\Constants\SmsTemplates;
use App\Data\SmsData;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Notifications\Booking\CustomerBookingConfirmed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->venue = Venue::factory()->create([
        'name' => 'The Fancy Restaurant',
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
        'timezone' => 'UTC',
        'region' => 'miami',
    ]);
    $this->concierge = Concierge::factory()->create();
    $this->partner = Partner::factory()->create(['percentage' => 6]);

    $this->partialMock(Concierge::class, function ($mock) {
        $mock->shouldReceive('getAttribute')->with('payout_percentage')->andReturn(10);
    });

    // Get a base template (party_size = 0)
    $baseTemplate = ScheduleTemplate::where([
        'venue_id' => $this->venue->id,
        'start_time' => '14:00:00',
        'party_size' => 0,
    ])->get()->first();

    // Get a guest count template
    $this->scheduleTemplate = ScheduleTemplate::where([
        'venue_id' => $this->venue->id,
        'start_time' => '14:00:00',
        'day_of_week' => $baseTemplate->day_of_week,
        'party_size' => 2,
    ])->get()->first();

    $this->action = new CreateBooking;
    actingAs($this->concierge->user);
});

it('ensures the customer_booking_confirmed_non_prime SMS content is correct', function () {
    $guestCount = 3;

    // Update both templates to non-prime
    ScheduleTemplate::where('venue_id', $this->venue->id)
        ->where('start_time', $this->scheduleTemplate->start_time)
        ->where('day_of_week', $this->scheduleTemplate->day_of_week)
        ->update(['prime_time' => 0]);

    $result = $this->action::run(
        $this->scheduleTemplate->id,
        [
            'date' => now()->addDay()->format('Y-m-d'),
            'guest_count' => $guestCount,
        ]
    );
    $booking = $result->booking;
    $booking->update(['guest_phone' => '+12015557894']);

    Notification::fake();

    // Act
    $notification = new CustomerBookingConfirmed;
    $smsData = $notification->toSMS($booking);

    // Parse template manually
    $templateKey = 'customer_booking_confirmed_non_prime';
    $templateContent = SmsTemplates::TEMPLATES[$templateKey];
    $parsedMessage = preg_replace_callback(
        '/\{(\w+)}/',
        fn ($matches) => $smsData->templateData[$matches[1]] ?? $matches[0],
        $templateContent
    );

    // Generate the expected message dynamically
    $expectedMessage = sprintf(
        '👋 Hello from PRIMA VIP! Your reservation at %s on %s at %s has been booked by %s. Please arrive within 15 minutes of your reservation and mention PRIMA VIP when checking in! Thank you for booking with us. (https://primaapp.com)',
        $booking->venue->name,
        $booking->booking_at->format('D M jS'),
        Carbon::parse($this->scheduleTemplate->start_time)->format('g:ia'),
        $booking->concierge->user->name
    );

    // Assert
    expect($smsData)->toBeInstanceOf(SmsData::class)
        ->and($smsData->phone)->toBe($booking->guest_phone)
        ->and($smsData->templateKey)->toBe('customer_booking_confirmed_non_prime')
        ->and($parsedMessage)->toBe($expectedMessage);
});
