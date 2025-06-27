<?php

use App\Enums\VenueStatus;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

it('generates a slug when creating a venue', function () {
    $venue = Venue::factory()->create([
        'name' => 'Fancy Venue',
        'region' => 'New York',
    ]);

    $expectedSlug = Str::slug('New York-Fancy Venue');

    expect($venue->slug)->toBe($expectedSlug);
});

it('returns the logo URL when the logo_path is present', function () {
    $venue = Venue::factory()->create([
        'name' => 'Test Venue',
        'logo_path' => 'logos/venue.jpg',
    ]);

    Storage::shouldReceive('disk->url')
        ->with('logos/venue.jpg')
        ->andReturn('https://example.com/logos/venue.jpg');

    expect($venue->logo)->toBe('https://example.com/logos/venue.jpg');
});

it('generates default open days and party sizes', function () {
    $venue = Venue::factory()->create();

    $expectedOpenDays = [
        'monday' => 'open',
        'tuesday' => 'open',
        'wednesday' => 'open',
        'thursday' => 'open',
        'friday' => 'open',
        'saturday' => 'open',
        'sunday' => 'open',
    ];

    $expectedPartySizes = [
        'Special Request' => 0,
        '2' => 2,
        '4' => 4,
        '6' => 6,
        '8' => 8,
        '10' => 10,
        '12' => 12,
        '14' => 14,
        '16' => 16,
        '18' => 18,
        '20' => 20,
    ];

    expect($venue->open_days)->toEqual($expectedOpenDays)
        ->and($venue->party_sizes)->toEqual($expectedPartySizes);
});

it('creates default schedules when a venue is created', function () {
    $venue = Venue::factory()->create();

    $schedules = ScheduleTemplate::where('venue_id', $venue->id)->get();

    expect($schedules)->not->toBeEmpty();
    expect($schedules->groupBy('day_of_week')->count())->toBe(7); // 7 days in a week
});

it('gets operating hours from schedule templates', function () {
    $venue = Venue::factory()->create();

    ScheduleTemplate::factory(10)->create([
        'venue_id' => $venue->id,
        'start_time' => '10:00:00',
        'end_time' => '22:00:00',
        'is_available' => true,
    ]);

    $operatingHours = $venue->getOperatingHours();

    $earliestStartTime = $venue->scheduleTemplates()
        ->where('is_available', true)
        ->min('start_time');

    $latestEndTime = $venue->scheduleTemplates()
        ->where('is_available', true)
        ->max('end_time');

    expect($operatingHours['earliest_start_time'])->toBe($earliestStartTime)
        ->and($operatingHours['latest_end_time'])->toBe($latestEndTime);
});

it('fetches only available venues using the scope', function () {
    $securedUser = User::factory()->create(['secured_at' => now()]);

    $availableVenue = Venue::factory()->for($securedUser)->create([
        'status' => VenueStatus::ACTIVE,
    ]);

    $unavailableVenue = Venue::factory()->for($securedUser)->create([
        'status' => VenueStatus::UPCOMING,
    ]);

    $venues = Venue::select(['id', 'name', 'status'])->active()->get();

    expect($venues->contains($availableVenue))->toBeTrue()
        ->and($venues->contains($unavailableVenue))->toBeFalse();
});
