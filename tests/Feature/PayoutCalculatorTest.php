<?php

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates payouts correctly', function () {
    // Guest count means the total fee will be $200 or 20000 cents.
    $booking = Booking::factory()->create([
        'guest_count' => 2,
    ]);

    // dd($booking->partnerRestaurant);

    $payoutCalculator = new \App\Services\PayoutCalculator($booking);
    $calculationData = $payoutCalculator->calculate();

    dump($calculationData->calculateTotalEarnings());
    dd($calculationData);

    expect($calculationData->totalFee)->toBe(1000)
        ->and($calculationData->restaurantPayoutPercentage)->toBe(70)
        ->and($calculationData->restaurantCharityPercentage)->toBe(5)
        ->and($calculationData->restaurantEarned)->toBe(700)
        ->and($calculationData->restaurantCharityEarned)->toBe(35)
        ->and($calculationData->conciergePayoutPercentage)->toBe(15)
        ->and($calculationData->conciergeCharityPercentage)->toBe(5)
        ->and($calculationData->conciergeEarned)->toBe(150)
        ->and($calculationData->conciergeCharityEarned)->toBe(5)
        ->and($calculationData->partnerRestaurantPayoutPercentage)->toBe(5)
        ->and($calculationData->partnerRestaurantEarned)->toBe(50)
        ->and($calculationData->partnerConciergePayoutPercentage)->toBe(5)
        ->and($calculationData->partnerConciergeEarned)->toBe(50)
        ->and($calculationData->platformPayoutPercentage)->toBe(5)
        ->and($calculationData->platformEarned)->toBe(50)
        ->and($calculationData->platformCharityEarned)->toBe(5)
        ->and($calculationData->charityTotalEarned)->toBe(95);
});
