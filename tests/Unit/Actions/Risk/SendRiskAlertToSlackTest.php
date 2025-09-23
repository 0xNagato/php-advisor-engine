<?php

use App\Actions\Risk\SendRiskAlertToSlack;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.slack.risk_webhook_url' => 'https://hooks.slack.com/test']);
    config(['app.url' => 'https://prima.test']);
});

it('sends different slack messages for low risk bookings', function () {
    Http::fake();

    $venue = Venue::factory()->create(['name' => 'Test Restaurant', 'region' => 'NYC']);
    $schedule = ScheduleTemplate::factory()->create(['venue_id' => $venue->id]);

    $concierge = Concierge::factory()->create();

    // Create a booking without triggering model events
    $booking = new Booking;
    $booking->schedule_template_id = $schedule->id;
    $booking->concierge_id = $concierge->id;
    $booking->guest_first_name = 'John';
    $booking->guest_last_name = 'Doe';
    $booking->guest_email = 'john@example.com';
    $booking->guest_phone = '+1234567890';
    $booking->guest_count = 4;
    $booking->risk_score = 15;
    $booking->risk_state = null; // Low risk
    $booking->status = 'confirmed';
    $booking->booking_at = now()->addDays(3);
    $booking->uuid = \Illuminate\Support\Str::uuid();
    $booking->currency = 'USD';
    $booking->total_fee = 0;
    $booking->original_total = 0;
    $booking->saveQuietly();

    $riskResult = [
        'score' => 15,
        'reasons' => [],
        'features' => [],
    ];

    SendRiskAlertToSlack::run($booking->fresh(), $riskResult);

    Http::assertSent(function ($request) use ($booking) {
        $payload = $request->data();
        $attachment = $payload['attachments'][0];

        // Check for low risk indicators
        expect($attachment['color'])->toBe('good');
        expect($attachment['title'])->toContain('🟢');
        expect($attachment['title'])->toContain('LOW RISK');
        // Check for appropriate text based on venue configuration
        // This test doesn't set up platform integration, so it should say venue contacts notified
        expect($attachment['text'])->toContain('venue contacts notified');

        // Check primary action is View Details for low risk
        $actions = $attachment['actions'];
        expect($actions[0]['text'])->toContain('View Details');
        expect($actions[0]['url'])->toContain('/platform/bookings/'.$booking->id);

        return true;
    });
});

it('sends different slack messages for medium risk bookings', function () {
    Http::fake();

    $venue = Venue::factory()->create(['name' => 'Test Restaurant', 'region' => 'LA']);
    $schedule = ScheduleTemplate::factory()->create(['venue_id' => $venue->id]);

    $concierge = Concierge::factory()->create();

    $booking = new Booking;
    $booking->schedule_template_id = $schedule->id;
    $booking->concierge_id = $concierge->id;
    $booking->guest_first_name = 'Jane';
    $booking->guest_last_name = 'Smith';
    $booking->guest_email = 'jane@tempmail.com';
    $booking->guest_phone = '+1234567890';
    $booking->guest_count = 6;
    $booking->risk_score = 45;
    $booking->risk_state = 'soft';
    $booking->status = 'review_pending';
    $booking->booking_at = now()->addDays(2);
    $booking->uuid = \Illuminate\Support\Str::uuid();
    $booking->currency = 'USD';
    $booking->total_fee = 0;
    $booking->original_total = 0;
    $booking->saveQuietly();

    $riskResult = [
        'score' => 45,
        'reasons' => ['Disposable email domain', 'Large party size'],
        'features' => [],
    ];

    SendRiskAlertToSlack::run($booking->fresh(), $riskResult);

    Http::assertSent(function ($request) use ($booking) {
        $payload = $request->data();
        $attachment = $payload['attachments'][0];

        // Check for medium risk indicators
        expect($attachment['color'])->toBe('warning');
        expect($attachment['title'])->toContain('🟡');
        expect($attachment['title'])->toContain('MEDIUM RISK');
        expect($attachment['text'])->toContain('hold');
        expect($attachment['text'])->toContain('requires immediate review');

        // Check primary action is Review Booking for medium risk
        $actions = $attachment['actions'];
        expect($actions[0]['text'])->toContain('Review Booking');
        expect($actions[0]['url'])->toContain('/platform/risk-reviews/'.$booking->id);
        expect($actions[0]['style'])->toBe('primary');

        // Check risk reasons are included
        $hasRiskIndicators = false;
        foreach ($attachment['fields'] as $field) {
            if ($field['title'] === 'Risk Indicators') {
                $hasRiskIndicators = true;
                expect($field['value'])->toContain('Disposable email');
                break;
            }
        }
        expect($hasRiskIndicators)->toBeTrue();

        return true;
    });
});

it('sends different slack messages for high risk bookings', function () {
    Http::fake();

    $venue = Venue::factory()->create(['name' => 'Test Restaurant', 'region' => 'SF']);
    $schedule = ScheduleTemplate::factory()->create(['venue_id' => $venue->id]);

    $concierge = Concierge::factory()->create();

    $booking = new Booking;
    $booking->schedule_template_id = $schedule->id;
    $booking->concierge_id = $concierge->id;
    $booking->guest_first_name = 'Suspicious';
    $booking->guest_last_name = 'User';
    $booking->guest_email = 'fake@mailinator.com';
    $booking->guest_phone = '+1234567890';
    $booking->guest_count = 15;
    $booking->risk_score = 85;
    $booking->risk_state = 'hard';
    $booking->status = 'review_pending';
    $booking->booking_at = now()->addDays(1);
    $booking->uuid = \Illuminate\Support\Str::uuid();
    $booking->currency = 'USD';
    $booking->total_fee = 0;
    $booking->original_total = 0;
    $booking->saveQuietly();

    $riskResult = [
        'score' => 85,
        'reasons' => [
            'Disposable email domain',
            'Large party size',
            'Suspicious name pattern',
            'High-risk IP location',
        ],
        'features' => [],
    ];

    SendRiskAlertToSlack::run($booking->fresh(), $riskResult);

    Http::assertSent(function ($request) use ($booking) {
        $payload = $request->data();
        $attachment = $payload['attachments'][0];

        // Check for high risk indicators
        expect($attachment['color'])->toBe('danger');
        expect($attachment['title'])->toContain('🔴');
        expect($attachment['title'])->toContain('HIGH RISK');
        expect($attachment['text'])->toContain('hold');
        expect($attachment['text'])->toContain('requires immediate review');

        // Check primary action is Review Booking for high risk
        $actions = $attachment['actions'];
        expect($actions[0]['text'])->toContain('Review Booking');
        expect($actions[0]['url'])->toContain('/platform/risk-reviews/'.$booking->id);
        expect($actions[0]['style'])->toBe('primary');

        // Check multiple risk reasons are included
        $hasRiskIndicators = false;
        foreach ($attachment['fields'] as $field) {
            if ($field['title'] === 'Risk Indicators') {
                $hasRiskIndicators = true;
                expect($field['value'])->toContain('Disposable email');
                expect($field['value'])->toContain('Large party');
                break;
            }
        }
        expect($hasRiskIndicators)->toBeTrue();

        return true;
    });
});

it('includes booking details in all slack messages', function () {
    Http::fake();

    $venue = Venue::factory()->create(['name' => 'Prima Restaurant', 'region' => 'NYC']);
    $schedule = ScheduleTemplate::factory()->create(['venue_id' => $venue->id]);

    $concierge = Concierge::factory()->create();

    $booking = new Booking;
    $booking->schedule_template_id = $schedule->id;
    $booking->concierge_id = $concierge->id;
    $booking->guest_first_name = 'Test';
    $booking->guest_last_name = 'Guest';
    $booking->guest_email = 'test@example.com';
    $booking->guest_phone = '+1234567890';
    $booking->guest_count = 2;
    $booking->booking_at = now()->addDays(3);
    $booking->risk_score = 10;
    $booking->risk_state = null;
    $booking->status = 'confirmed';
    $booking->uuid = \Illuminate\Support\Str::uuid();
    $booking->currency = 'USD';
    $booking->total_fee = 0;
    $booking->original_total = 0;
    $booking->saveQuietly();

    $riskResult = ['score' => 10, 'reasons' => [], 'features' => []];

    SendRiskAlertToSlack::run($booking->fresh(), $riskResult);

    Http::assertSent(function ($request) {
        $payload = $request->data();
        $attachment = $payload['attachments'][0];

        // Check all booking details are present
        $fields = collect($attachment['fields']);

        $guestField = $fields->firstWhere('title', 'Guest');
        expect($guestField['value'])->toContain('test@example.com');

        $venueField = $fields->firstWhere('title', 'Venue');
        expect($venueField['value'])->toBe('Prima Restaurant');

        $partyField = $fields->firstWhere('title', 'Party Size');
        expect($partyField['value'])->toBe('2 guests');

        $scoreField = $fields->firstWhere('title', 'Risk Score');
        expect($scoreField['value'])->toBe('10/100');

        // Check footer
        expect($attachment['footer'])->toContain('NYC');

        return true;
    });
});
