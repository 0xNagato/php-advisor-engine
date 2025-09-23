<?php

use App\Actions\Risk\SendBookingMonitoringAlert;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Concierge;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('sends booking monitoring alert with correct status formatting', function () {
    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    // Create test data
    $venue = Venue::factory()->create(['timezone' => 'America/New_York']);
    $concierge = Concierge::factory()->create();

    // Create a schedule template for the venue first
    $scheduleTemplate = $venue->scheduleTemplates()->create([
        'day_of_week' => 'monday',
        'start_time' => '18:00:00',
        'end_time' => '22:00:00',
        'is_available' => true,
        'available_tables' => 10,
    ]);

    $booking = Booking::factory()->create([
        'schedule_template_id' => $scheduleTemplate->id,
        'concierge_id' => $concierge->id,
        'status' => BookingStatus::REVIEW_PENDING,
        'guest_count' => 6,
        'risk_score' => 25,
        'risk_state' => 'soft',
        'guest_first_name' => 'John',
        'guest_last_name' => 'Doe',
        'guest_email' => 'john@example.com',
        'guest_phone' => '+1234567890',
        'booking_at' => now()->addDays(2),
        'is_prime' => false,
        'total_fee' => 0,
        'currency' => 'USD',
        'ip_address' => '192.168.1.1',
    ]);

    // Load the venue relationship to avoid N+1 issues
    $booking->load('venue', 'concierge');

    $riskResult = [
        'features' => [
            'breakdown' => ['ip_velocity' => 5, 'guest_history' => 20],
            'device' => 'abc123def456ghi789jkl',
            'velocity_count' => 2,
        ],
        'reasons' => ['Suspicious booking pattern'],
    ];

    // Set up Slack webhook URL
    config(['services.slack.all_bookings_webhook_url' => 'https://hooks.slack.com/test']);

    // Execute the action
    $action = new SendBookingMonitoringAlert;
    $action->handle($booking, $riskResult);

    // Verify the HTTP request was made
    Http::assertSent(function ($request) {
        $payload = $request->data();

        // Check that the status is properly formatted using the enum's label() method
        $statusField = collect($payload['attachments'][0]['fields'])
            ->firstWhere('title', 'Status');

        expect($statusField['value'])->toBe('Risk Review Pending');

        return true;
    });
});

it('handles different booking statuses correctly', function () {
    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    config(['services.slack.all_bookings_webhook_url' => 'https://hooks.slack.com/test']);

    $venue = Venue::factory()->create(['timezone' => 'America/New_York']);
    $concierge = Concierge::factory()->create();

    // Create a schedule template for the venue first
    $scheduleTemplate = $venue->scheduleTemplates()->create([
        'day_of_week' => 'monday',
        'start_time' => '18:00:00',
        'end_time' => '22:00:00',
        'is_available' => true,
        'available_tables' => 10,
    ]);

    $testCases = [
        BookingStatus::PENDING->value => 'Pending',
        BookingStatus::CONFIRMED->value => 'Confirmed',
        BookingStatus::CANCELLED->value => 'Cancelled',
        BookingStatus::REVIEW_PENDING->value => 'Risk Review Pending',
        BookingStatus::VENUE_CONFIRMED->value => 'Venue Confirmed',
    ];

    foreach ($testCases as $statusValue => $expectedLabel) {
        $booking = Booking::factory()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'concierge_id' => $concierge->id,
            'status' => BookingStatus::from($statusValue),
        ]);

        $booking->load('venue', 'concierge');

        $action = new SendBookingMonitoringAlert;
        $message = $action->formatMessage($booking, []);

        $statusField = collect($message['attachments'][0]['fields'])
            ->firstWhere('title', 'Status');

        expect($statusField['value'])->toBe($expectedLabel);
    }
});
