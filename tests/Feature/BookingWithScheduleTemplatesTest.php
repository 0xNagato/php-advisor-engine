<?php

use App\Actions\Booking\CreateBooking;
use App\Constants\BookingPercentages;
use App\Models\Concierge;
use App\Models\Partner;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VenueTimeSlot;
use App\Services\Booking\BookingCalculationService;
use App\Services\Booking\EarningCreationService;
use App\Services\Booking\NonPrimeEarningsCalculationService;
use App\Services\Booking\PrimeEarningsCalculationService;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $earningCreationService = new EarningCreationService;
    $primeEarningsCalculationService = new PrimeEarningsCalculationService($earningCreationService);
    $nonPrimeEarningsCalculationService = new NonPrimeEarningsCalculationService($earningCreationService);

    $this->service = new BookingCalculationService(
        $primeEarningsCalculationService,
        $nonPrimeEarningsCalculationService
    );

    $this->venue = Venue::factory()->create([
        'payout_venue' => 60,
        'non_prime_fee_per_head' => 10,
    ]);
    $this->concierge = Concierge::factory()->create();
    $this->partner = Partner::factory()->create(['percentage' => 6]);

    $this->partialMock(Concierge::class, function ($mock) {
        $mock->shouldReceive('getAttribute')->with('payout_percentage')->andReturn(10);
    });

    // Create base template (party_size = 0)
    $baseTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => '14:00:00',
        'party_size' => 0,
    ]);

    // Create guest count template
    $this->scheduleTemplate = ScheduleTemplate::factory()->create([
        'venue_id' => $this->venue->id,
        'start_time' => '14:00:00',
        'day_of_week' => $baseTemplate->day_of_week,
        'party_size' => 2,
    ]);

    actingAs($this->concierge->user);
    $this->action = new CreateBooking;
});

test('Non-prime booking with VenueTimeSlot override calculates earnings correctly', function () {
    $guestCount = 3;

    // Update both templates to non-prime
    ScheduleTemplate::where('venue_id', $this->venue->id)
        ->where('start_time', $this->scheduleTemplate->start_time)
        ->where('day_of_week', $this->scheduleTemplate->day_of_week)
        ->update(['prime_time' => 0]);

    // Create a VenueTimeSlot as an override for the schedule template
    $override = VenueTimeSlot::factory()->create([
        'schedule_template_id' => $this->scheduleTemplate->id,
        'booking_date' => now()->addDay()->toDateString(),
        'prime_time' => false,
        'price_per_head' => 25, // Override price
    ]);

    $result = $this->action::run(
        $this->scheduleTemplate->id,
        [
            'date' => now()->addDay()->format('Y-m-d'),
            'guest_count' => $guestCount,
        ],
        'UTC',
        'USD'
    );

    $pricePerHead = $override->price_per_head;
    $fee = $pricePerHead * $guestCount;
    $concierge_earnings = $fee - ($fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100));
    $platform_concierge = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE / 100);
    $platform_venue = $fee * (BookingPercentages::PLATFORM_PERCENTAGE_VENUE / 100);
    $platform_earnings = $platform_concierge + $platform_venue;
    $venue_earnings = ($concierge_earnings + $platform_earnings) * -1;

    expect($result->booking->total_fee)->toBe(0)
        ->and((int) $result->booking->concierge_earnings)->toBe(intval($concierge_earnings * 100))
        ->and((int) $result->booking->venue_earnings)->toBe(intval($venue_earnings * 100))
        ->and((int) $result->booking->platform_earnings)->toBe(intval($platform_earnings * 100));
});
