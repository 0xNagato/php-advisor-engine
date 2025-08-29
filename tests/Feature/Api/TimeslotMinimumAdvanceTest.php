<?php

use App\Models\Region;
use App\Services\ReservationService;
use Carbon\Carbon;

use function Pest\Laravel\getJson;

test('timeslots respect the minimum advance booking rule for current day', function () {
    $region = Region::first();
    
    // Set a specific time for testing (2:25 PM in the region's timezone)
    $testTime = Carbon::parse('2024-01-10 14:25:00', $region->timezone);
    Carbon::setTestNow($testTime);
    
    $currentDate = now($region->timezone)->format('Y-m-d');
    
    $response = getJson("/api/timeslots?date={$currentDate}")
        ->assertSuccessful();
    
    $timeslots = $response->json('data');
    
    // Find specific timeslots to check
    $timeslot230 = collect($timeslots)->firstWhere('value', '14:30:00');
    $timeslot300 = collect($timeslots)->firstWhere('value', '15:00:00');
    $timeslot330 = collect($timeslots)->firstWhere('value', '15:30:00');
    $timeslot400 = collect($timeslots)->firstWhere('value', '16:00:00');
    
    // At 2:25 PM with 35-minute rule:
    // - 2:30 PM should be unavailable (only 5 minutes away)
    // - 3:00 PM should be unavailable (only 35 minutes away)
    // - 3:30 PM should be available (65 minutes away)
    // - 4:00 PM should be available (95 minutes away)
    
    expect($timeslot230)->not->toBeNull('2:30 PM timeslot should exist');
    expect($timeslot230['available'])->toBeFalse('2:30 PM should be unavailable (less than 35 minutes away)');
    
    expect($timeslot300)->not->toBeNull('3:00 PM timeslot should exist');
    expect($timeslot300['available'])->toBeFalse('3:00 PM should be unavailable (exactly 35 minutes away)');
    
    // The first available slot should be 3:01 PM or later, but since we use 30-minute slots, it should be 3:30 PM
    expect($timeslot330)->not->toBeNull('3:30 PM timeslot should exist');
    expect($timeslot330['available'])->toBeTrue('3:30 PM should be available (65 minutes away)');
    
    expect($timeslot400)->not->toBeNull('4:00 PM timeslot should exist');
    expect($timeslot400['available'])->toBeTrue('4:00 PM should be available (95 minutes away)');
    
    Carbon::setTestNow(); // Reset time
});

test('timeslots enforce strict 35-minute rule - edge case at exactly 35 minutes', function () {
    $region = Region::first();
    
    // Set time to 2:25:01 PM - exactly 34:59 before 3:00 PM
    $testTime = Carbon::parse('2024-01-10 14:25:01', $region->timezone);
    Carbon::setTestNow($testTime);
    
    $currentDate = now($region->timezone)->format('Y-m-d');
    
    $response = getJson("/api/timeslots?date={$currentDate}")
        ->assertSuccessful();
    
    $timeslots = $response->json('data');
    
    $timeslot300 = collect($timeslots)->firstWhere('value', '15:00:00');
    $timeslot330 = collect($timeslots)->firstWhere('value', '15:30:00');
    
    // At 2:25:01 PM, 3:00 PM is only 34:59 away, so it should be unavailable
    expect($timeslot300['available'])->toBeFalse('3:00 PM should be unavailable (only 34:59 away)');
    expect($timeslot330['available'])->toBeTrue('3:30 PM should be available (64:59 away)');
    
    // Now test at 2:24:59 PM - exactly 35:01 before 3:00 PM
    $testTime2 = Carbon::parse('2024-01-10 14:24:59', $region->timezone);
    Carbon::setTestNow($testTime2);
    
    $response2 = getJson("/api/timeslots?date={$currentDate}")
        ->assertSuccessful();
    
    $timeslots2 = $response2->json('data');
    $timeslot300_2 = collect($timeslots2)->firstWhere('value', '15:00:00');
    
    // At 2:24:59 PM, 3:00 PM is 35:01 away, so it should be available
    expect($timeslot300_2['available'])->toBeTrue('3:00 PM should be available (35:01 away)');
    
    Carbon::setTestNow(); // Reset time
});

test('timeslots for future dates are not affected by minimum advance rule', function () {
    // Set a specific time for testing
    Carbon::setTestNow(Carbon::parse('2024-01-10 14:25:00'));
    
    $region = Region::first();
    $futureDate = now($region->timezone)->addDay()->format('Y-m-d');
    
    $response = getJson("/api/timeslots?date={$futureDate}")
        ->assertSuccessful();
    
    $timeslots = $response->json('data');
    
    // All timeslots for future dates should be available
    foreach ($timeslots as $timeslot) {
        expect($timeslot['available'])->toBeTrue();
    }
    
    Carbon::setTestNow(); // Reset time
});

test('minimum advance booking time uses ReservationService constant', function () {
    // Set a specific time for testing
    Carbon::setTestNow(Carbon::parse('2024-01-10 14:00:00'));
    
    $region = Region::first();
    $currentDate = now($region->timezone)->format('Y-m-d');
    
    $response = getJson("/api/timeslots?date={$currentDate}")
        ->assertSuccessful();
    
    $timeslots = $response->json('data');
    
    // Calculate the minimum time based on the constant
    $minimumTime = now($region->timezone)->addMinutes(ReservationService::MINUTES_PAST);
    $minimumTimeString = $minimumTime->format('H:i:s');
    
    // Find the first available timeslot
    $firstAvailable = collect($timeslots)->first(fn($slot) => $slot['available']);
    
    // The first available timeslot should be at or after the minimum time
    expect($firstAvailable['value'])->toBeGreaterThanOrEqual($minimumTimeString);
    
    // Find a timeslot just before the minimum time (if exists)
    $justBefore = collect($timeslots)->filter(fn($slot) => $slot['value'] < $minimumTimeString)->last();
    if ($justBefore) {
        expect($justBefore['available'])->toBeFalse();
    }
    
    Carbon::setTestNow(); // Reset time
});

test('timeslots in different regions respect their local timezone for advance booking', function () {
    // Test with Miami (EST/EDT)
    Carbon::setTestNow(Carbon::parse('2024-01-10 14:25:00', 'America/New_York'));
    
    $miamiDate = now('America/New_York')->format('Y-m-d');
    
    $response = getJson("/api/timeslots?date={$miamiDate}&region=miami")
        ->assertSuccessful();
    
    $miamiSlots = $response->json('data');
    $miami300 = collect($miamiSlots)->firstWhere('value', '15:00:00');
    expect($miami300['available'])->toBeFalse('3:00 PM Miami time should be unavailable');
    
    // Test with Paris (CET/CEST) - 6 hours ahead of Miami
    Carbon::setTestNow(Carbon::parse('2024-01-10 20:25:00', 'Europe/Paris'));
    
    $parisDate = now('Europe/Paris')->format('Y-m-d');
    
    $response = getJson("/api/timeslots?date={$parisDate}&region=paris")
        ->assertSuccessful();
    
    $parisSlots = $response->json('data');
    $paris2100 = collect($parisSlots)->firstWhere('value', '21:00:00');
    expect($paris2100['available'])->toBeFalse('9:00 PM Paris time should be unavailable');
    
    Carbon::setTestNow(); // Reset time
});