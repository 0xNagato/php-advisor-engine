<?php

namespace App\Console\Commands;

use App\Actions\Booking\CompleteBooking;
use App\Actions\Booking\CreateBooking;
use App\Models\Concierge;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\Venue;
use App\Models\VipCode;
use App\Models\VipSession;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CreateProperVipTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vip:create-proper-test-data {--user-id=1} {--booking-count=5} {--venue-id=} {--region=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create proper VIP test data using real venues and their existing schedules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $bookingCount = $this->option('booking-count');
        $venueId = $this->option('venue-id');
        $region = $this->option('region');

        $this->info("Creating proper VIP test data for user ID: {$userId} using real venues");

        // Find the user and their concierge
        $user = User::query()->find($userId);
        if (! $user) {
            $this->error("User with ID {$userId} not found");

            return 1;
        }

        $concierge = $user->concierge;
        if (! $concierge) {
            $this->error("User {$userId} doesn't have a concierge profile");

            return 1;
        }

        $this->info("Found concierge: {$concierge->hotel_name} (ID: {$concierge->id})");
        $this->info('VIP codes: '.implode(', ', $concierge->vipCodes->pluck('code')->toArray()));

        // Find real venues based on region or venue ID
        if ($venueId) {
            $venue = Venue::query()->find($venueId);
            if (! $venue) {
                $this->error("Venue with ID {$venueId} not found");

                return 1;
            }
            $this->info("Using specified venue: {$venue->name} (ID: {$venue->id}) in {$venue->region}");
        } else {
            // Use region filter or default to miami
            $targetRegion = $region ?: 'miami';
            $venues = Venue::query()->where('status', 'active')->where('region', $targetRegion)->limit(3)->get();
            if ($venues->count() === 0) {
                $this->error("No active {$targetRegion} venues found");

                return 1;
            }

            $this->info("Found {$venues->count()} real {$targetRegion} venues:");
            foreach ($venues as $v) {
                $this->line("  - {$v->name} (ID: {$v->id})");
            }

            $venue = $venues->first();
        }

        $this->info("Using venue: {$venue->name} (ID: {$venue->id})");

        // Get schedule templates for this venue with venue relationship
        $schedules = ScheduleTemplate::with('venue')
            ->where('venue_id', $venue->id)
            ->where('is_available', true)
            ->limit(10)
            ->get();

        if ($schedules->count() === 0) {
            $this->error("No available schedules found for venue {$venue->name}");

            return 1;
        }

        $this->info("Found {$schedules->count()} available schedules for {$venue->name}");

        // Create proper test bookings
        $this->createProperBookings($bookingCount, $concierge, $schedules);

        $this->newLine();
        $this->info('✅ Proper VIP test data created successfully!');
        $this->info('📊 Summary:');
        $this->info("  - {$bookingCount} proper test bookings created using real venues");
        $this->info("  - Venue: {$venue->name}");
        $this->info('  - VIP codes used: '.implode(', ', $concierge->vipCodes->pluck('code')->toArray()));

        return 0;
    }

    private function createProperBookings(int $count, Concierge $concierge, $schedules): void
    {
        $this->info("Creating {$count} proper test bookings...");

        $vipCodes = $concierge->vipCodes->pluck('code')->toArray();
        $guestCounts = [2, 4, 6, 8];

        for ($i = 0; $i < $count; $i++) {
            // Pick a random schedule
            $schedule = $schedules->random();

            // Calculate booking date (1-30 days in advance)
            $daysAhead = random_int(1, 30);
            $bookingDate = now()->addDays($daysAhead);

            // Set booking time to the schedule start time
            $bookingTime = Carbon::parse($schedule->start_time);
            $bookingAt = $bookingDate->copy()->setTime($bookingTime->hour, $bookingTime->minute, 0);

            // Pick random VIP code
            $vipCode = $vipCodes[array_rand($vipCodes)];
            $guestCount = $guestCounts[array_rand($guestCounts)];

            // Create VIP session with query parameters
            $vipCodeModel = VipCode::query()->where('code', $vipCode)->first();

            // Generate query parameters for this session
            $queryParams = [
                'utm_source' => ['facebook', 'instagram', 'google', 'twitter'][array_rand(['facebook', 'instagram', 'google', 'twitter'])],
                'utm_medium' => 'social',
                'utm_campaign' => ['summer2024', 'holiday2024', 'winter2025'][array_rand(['summer2024', 'holiday2024', 'winter2025'])],
                'utm_content' => 'ad_'.random_int(1000, 9999),
                'cuisine' => [['italian', 'french'], ['mexican', 'asian'], ['american', 'mediterranean']][array_rand([['italian', 'french'], ['mexican', 'asian'], ['american', 'mediterranean']])],
                'guest_count' => $guestCount,
                'budget' => random_int(100, 300),
                'occasion' => ['date_night', 'business_meeting', 'celebration'][array_rand(['date_night', 'business_meeting', 'celebration'])],
            ];

            $session = VipSession::query()->create([
                'vip_code_id' => $vipCodeModel->id,
                'token' => 'test_session_'.$vipCode.'_'.uniqid(),
                'expires_at' => now()->addHours(24),
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'query_params' => $queryParams,
                'landing_url' => 'https://prima.test/vip/'.$vipCode,
                'referer_url' => 'https://facebook.com/ads/campaign',
                'started_at' => now()->subMinutes(random_int(5, 30)),
            ]);

            // Create the booking using the proper CreateBooking action with VIP session
            try {
                $bookingResult = CreateBooking::run(
                    scheduleTemplateId: $schedule->id,
                    data: [
                        'date' => $bookingDate->format('Y-m-d'),
                        'guest_count' => $guestCount,
                    ],
                    vipCode: $vipCodeModel,
                    source: 'vip_test_data',
                    device: 'web',
                    vipSessionId: $session->id  // Link to VIP session
                );

                // The booking is now created with PENDING status
                $booking = $bookingResult->booking;

                // Complete the booking to make it CONFIRMED
                $result = CompleteBooking::run(
                    $booking,
                    '', // No payment intent for non-prime bookings
                    [
                        'firstName' => fake()->firstName(),
                        'lastName' => fake()->lastName(),
                        'phone' => '+16473823326',
                        'email' => fake()->unique()->safeEmail(),
                        'notes' => 'VIP booking via '.$session->query_params['utm_source'].' campaign',
                        'r' => 'vip_session',
                    ]
                );

                if ($result['success']) {
                    $this->line("  ✅ Created: {$guestCount} guests at {$schedule->venue->name} on {$bookingDate->format('M j')} using VIP code {$vipCode} (Booking ID: {$booking->id}, Session ID: {$session->id})");
                } else {
                    $this->line("  ⚠️ Created but failed to complete: {$guestCount} guests at {$schedule->venue->name} on {$bookingDate->format('M j')} using VIP code {$vipCode} (Error: {$result['message']})");
                }
            } catch (Exception $e) {
                $this->line("  ❌ Failed to create: {$guestCount} guests at {$schedule->venue->name} on {$bookingDate->format('M j')} using VIP code {$vipCode} (Error: {$e->getMessage()})");
            }
        }
    }
}
