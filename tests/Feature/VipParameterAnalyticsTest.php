<?php

use App\Enums\BookingStatus;
use App\Livewire\Concierge\VipCodesTable;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Models\VipCode;
use App\Models\VipSession;
use Illuminate\Support\Str;

test('parameter analytics counts all sessions even without bookings', function () {
    // Setup: Create venue and schedule template
    $venue = Venue::factory()->create();
    $scheduleTemplate = ScheduleTemplate::factory()->create(['venue_id' => $venue->id]);

    // Create VIP code
    $concierge = Concierge::factory()->create();
    $vipCode = VipCode::factory()->create(['concierge_id' => $concierge->id]);

    // Create sessions with different parameters
    // Session 1: utm_source=facebook (no booking)
    VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'query_params' => ['utm_source' => 'facebook', 'utm_campaign' => 'summer'],
        'created_at' => now(),
    ]);

    // Session 2: utm_source=instagram (no booking)
    VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'query_params' => ['utm_source' => 'instagram', 'utm_campaign' => 'summer'],
        'created_at' => now(),
    ]);

    // Session 3: utm_source=facebook (will have booking)
    $session3 = VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'query_params' => ['utm_source' => 'facebook', 'utm_campaign' => 'summer'],
        'created_at' => now(),
    ]);

    // Create a booking only for session 3
    Booking::factory()->create([
        'uuid' => Str::uuid(),
        'vip_code_id' => $vipCode->id,
        'vip_session_id' => $session3->id,
        'schedule_template_id' => $scheduleTemplate->id,
        'query_params' => ['utm_source' => 'facebook', 'utm_campaign' => 'summer'],
        'status' => BookingStatus::CONFIRMED,
        'total_fee' => 10000,
        'booking_at' => now()->addDay(),
        'created_at' => now(),
    ]);

    // Create instance of VipCodesTable to test the method
    $table = new VipCodesTable;

    // Use reflection to access private method
    $reflection = new ReflectionClass($table);
    $method = $reflection->getMethod('buildParameterAnalytics');
    $method->setAccessible(true);

    $sessions = $vipCode->sessions()->get();
    $bookings = $vipCode->bookings()->with('earnings')->get();

    $analytics = $method->invoke($table, $sessions, $bookings);

    // Find the facebook and instagram analytics
    $facebookAnalytics = $analytics->firstWhere('value', 'facebook');
    $instagramAnalytics = $analytics->firstWhere('value', 'instagram');
    $summerAnalytics = $analytics->firstWhere('value', 'summer');

    // Check facebook: 2 sessions, 1 booking
    expect($facebookAnalytics)->not->toBeNull();
    expect($facebookAnalytics['sessions'])->toBe(2);
    expect($facebookAnalytics['bookings'])->toBe(1);
    expect($facebookAnalytics['conversion'])->toBe(50.0);

    // Check instagram: 1 session, 0 bookings
    expect($instagramAnalytics)->not->toBeNull();
    expect($instagramAnalytics['sessions'])->toBe(1);
    expect($instagramAnalytics['bookings'])->toBe(0);
    expect($instagramAnalytics['conversion'])->toBe(0.0);

    // Check summer campaign: 3 sessions, 1 booking
    expect($summerAnalytics)->not->toBeNull();
    expect($summerAnalytics['sessions'])->toBe(3);
    expect($summerAnalytics['bookings'])->toBe(1);
});

test('parameter analytics counts multiple bookings with same parameters correctly', function () {
    // Setup: Create venue and schedule template
    $venue = Venue::factory()->create();
    $scheduleTemplate = ScheduleTemplate::factory()->create(['venue_id' => $venue->id]);

    // Create VIP code
    $concierge = Concierge::factory()->create();
    $vipCode = VipCode::factory()->create(['concierge_id' => $concierge->id]);

    // Create 3 sessions all with utm_source=facebook
    $params = ['utm_source' => 'facebook', 'utm_campaign' => 'test'];

    $session1 = VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'query_params' => $params,
        'created_at' => now(),
    ]);

    $session2 = VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'query_params' => $params,
        'created_at' => now(),
    ]);

    $session3 = VipSession::factory()->create([
        'vip_code_id' => $vipCode->id,
        'query_params' => $params,
        'created_at' => now(),
    ]);

    // Create 2 bookings with same parameters
    Booking::factory()->create([
        'uuid' => Str::uuid(),
        'vip_code_id' => $vipCode->id,
        'vip_session_id' => $session1->id,
        'schedule_template_id' => $scheduleTemplate->id,
        'query_params' => $params,
        'status' => BookingStatus::CONFIRMED,
        'total_fee' => 10000,
        'booking_at' => now()->addDay(),
        'created_at' => now(),
    ]);

    Booking::factory()->create([
        'uuid' => Str::uuid(),
        'vip_code_id' => $vipCode->id,
        'vip_session_id' => $session2->id,
        'schedule_template_id' => $scheduleTemplate->id,
        'query_params' => $params,
        'status' => BookingStatus::CONFIRMED,
        'total_fee' => 10000,
        'booking_at' => now()->addDay(),
        'created_at' => now(),
    ]);

    // Create instance of VipCodesTable to test the method
    $table = new VipCodesTable;

    // Use reflection to access private method
    $reflection = new ReflectionClass($table);
    $method = $reflection->getMethod('buildParameterAnalytics');
    $method->setAccessible(true);

    $sessions = $vipCode->sessions()->get();
    $bookings = $vipCode->bookings()->with('earnings')->get();

    $analytics = $method->invoke($table, $sessions, $bookings);

    // Find the facebook analytics
    $facebookAnalytics = $analytics->firstWhere('value', 'facebook');

    // Check counts: 3 sessions, 2 bookings
    expect($facebookAnalytics)->not->toBeNull();
    expect($facebookAnalytics['sessions'])->toBe(3);
    expect($facebookAnalytics['bookings'])->toBe(2);
    expect(round($facebookAnalytics['conversion'], 1))->toBe(66.7); // 2/3 = 66.7%
});
