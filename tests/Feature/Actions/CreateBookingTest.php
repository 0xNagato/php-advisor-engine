<?php

use App\Actions\Booking\CreateBooking;
use App\Data\Booking\CreateBookingReturnData;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VipCode;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->venue = Venue::factory()->create([
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

it('it can create a booking successfully', function () {
    $bookingData = [
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $this->action::run(
        $this->scheduleTemplate->id,
        $bookingData
    );

    assertDatabaseHas('bookings', [
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => $bookingData['guest_count'],
    ]);
});

test('it creates a prime booking successfully with all earnings', function () {
    $this->concierge->user->update(['partner_referral_id' => $this->partner->id]);
    $this->venue->user->update(['partner_referral_id' => $this->partner->id]);
    $this->scheduleTemplate->update(['prime_time' => 1]);

    $data = [
        'date' => now()->addDays(2)->format('Y-m-d'),
        'guest_count' => 2,
    ];

    // Act
    $result = $this->action->handle(
        $this->scheduleTemplate->id,
        $data
    );

    // Assert
    expect($result)->toBeInstanceOf(CreateBookingReturnData::class);
    $this->assertDatabaseCount('earnings', 4);
});

it('throws exception when booking is more than 30 days in advance', function () {
    $futureDate = now()->addDays(31)->format('Y-m-d');

    $bookingData = [
        'date' => $futureDate,
        'guest_count' => 2,
    ];

    expect(fn () => CreateBooking::run(
        $this->scheduleTemplate->id,
        $bookingData
    ))
        ->toThrow(RuntimeException::class, 'Booking cannot be created more than 30 days in advance.');
});

it('creates booking with correct timezone', function () {
    $timezone = $this->venue->inRegion->timezone;
    $bookingData = [
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    CreateBooking::run(
        $this->scheduleTemplate->id,
        $bookingData
    );

    $booking = Booking::first();
    $expectedDateTime = Carbon::createFromFormat(
        'Y-m-d H:i:s',
        $bookingData['date'].' '.$this->scheduleTemplate->start_time,
        $timezone
    );

    expect($booking->booking_at->format('Y-m-d H:i:s'))
        ->toBe($expectedDateTime->format('Y-m-d H:i:s'));
});

it('creates booking with vip code when provided', function () {
    $vipCode = VipCode::factory()->create();
    $bookingData = [
        'date' => now()->addDay()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    CreateBooking::run(
        $this->scheduleTemplate->id,
        $bookingData,
        $vipCode
    );

    assertDatabaseHas('bookings', [
        'schedule_template_id' => $this->scheduleTemplate->id,
        'guest_count' => $bookingData['guest_count'],
        'concierge_id' => $vipCode->concierge_id,
        'vip_code_id' => $vipCode->id,
    ]);
});

it('it calculates correct total amount based on guest count for non prime', function () {
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

    $bookingEarnings = getNonPrimeBookingEarnings($guestCount, $this->venue);

    expect($result->booking->total_fee)->toBe(0)
        ->and((int) $result->booking->concierge_earnings)->toBe($bookingEarnings['concierge_earnings'])
        ->and((int) $result->booking->venue_earnings)->toBe($bookingEarnings['venue_earnings'])
        ->and((int) $result->booking->platform_earnings)->toBe($bookingEarnings['platform_earnings']);
});
