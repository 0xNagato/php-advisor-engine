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
    ]);
    $this->concierge = Concierge::factory()->create();
    $this->partner = Partner::factory()->create(['percentage' => 6]);

    $this->partialMock(Concierge::class, function ($mock) {
        $mock->shouldReceive('getAttribute')->with('payout_percentage')->andReturn(10);
    });

    $this->scheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => '14:00:00',
    ]);

    $this->action = new CreateBooking;
    actingAs($this->concierge->user);
});

it('it can create a booking successfully', function () {
    $bookingData = [
        'date' => now()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    $this->action::run(
        $this->scheduleTemplate->id,
        $bookingData,
        'UTC',
        'USD'
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

    $timezone = 'UTC';
    $currency = 'USD';
    $data = [
        'date' => now()->addDays(2)->format('Y-m-d'),
        'guest_count' => 2,
    ];

    // Act
    $result = $this->action->handle(
        $this->scheduleTemplate->id,
        $data,
        $timezone,
        $currency
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
        $bookingData,
        'UTC',
        'USD'
    ))
        ->toThrow(RuntimeException::class, 'Booking cannot be created more than 30 days in advance.');
});

it('creates booking with correct timezone', function () {
    $timezone = 'America/New_York';
    $bookingData = [
        'date' => now()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    CreateBooking::run(
        $this->scheduleTemplate->id,
        $bookingData,
        $timezone,
        'USD'
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
        'date' => now()->format('Y-m-d'),
        'guest_count' => 2,
    ];

    CreateBooking::run(
        $this->scheduleTemplate->id,
        $bookingData,
        'UTC',
        'USD',
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
    $this->scheduleTemplate->update(['prime_time' => 0]);
    $result = $this->action::run(
        $this->scheduleTemplate->id,
        [
            'date' => now()->format('Y-m-d'),
            'guest_count' => $guestCount,
        ],
        'UTC',
        'USD'
    );

    $bookingEarnings = getNonPrimeBookingEarnings($guestCount, $this->venue);

    expect($result->booking->total_fee)->toBe(0)
        ->and((int) $result->booking->concierge_earnings)->toBe($bookingEarnings['concierge_earnings'])
        ->and((int) $result->booking->venue_earnings)->toBe($bookingEarnings['venue_earnings'])
        ->and((int) $result->booking->platform_earnings)->toBe($bookingEarnings['platform_earnings']);
});
