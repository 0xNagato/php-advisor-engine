<?php

namespace App\Console\Commands;

use App\Services\CoverManagerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class CheckCoverManagerAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'covermanager:check-availability
                            {restaurant_id : The CoverManager restaurant ID}
                            {date : Date to check (today, tomorrow, monday, June 30, 2025-06-30)}
                            {--party-size=2 : Party size to check (default: 2)}
                            {--time=19:00 : Specific time to check (default: 19:00)}
                            {--show-all : Show all available times instead of checking specific time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check availability for a CoverManager restaurant on a specific date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $restaurantId = $this->argument('restaurant_id');
        $dateString = $this->argument('date');
        $partySize = (int) $this->option('party-size');
        $time = $this->option('time');
        $showAll = $this->option('show-all');

        // Parse the date
        try {
            $date = $this->parseDate($dateString);
        } catch (InvalidArgumentException $e) {
            $this->error("Invalid date format: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info('🔍 Checking CoverManager availability...');
        $this->info("📍 Restaurant ID: {$restaurantId}");
        $this->info("📅 Date: {$date->format('Y-m-d (l)')}");
        $this->info("👥 Party size: {$partySize}");

        if (! $showAll) {
            $this->info("🕐 Time: {$time}");
        }

        $this->newLine();

        try {
            $coverManagerService = app(CoverManagerService::class);

            // Skip restaurant ID validation - we'll discover if it's invalid from the API response

            // Check availability
            $this->info('🔍 Checking availability...');
            $availability = $coverManagerService->checkAvailabilityRaw($restaurantId, $date, $time, $partySize);

            if (blank($availability)) {
                $this->error('❌ No availability data returned from CoverManager');

                return self::FAILURE;
            }

            // Handle CoverManager error response
            if (isset($availability['resp']) && $availability['resp'] === 0) {
                $error = $availability['error'] ?? $availability['status'] ?? 'Unknown error';
                $this->error("❌ CoverManager API error: {$error}");

                return self::FAILURE;
            }

            $this->info('✅ Availability data received');
            $this->newLine();

            if ($showAll) {
                $this->displayAllAvailability($availability, $partySize);
            } else {
                $this->displaySpecificTimeAvailability($availability, $time, $partySize);
            }

            // Show raw response for debugging
            if ($this->getOutput()->isVerbose()) {
                $this->newLine();
                $this->info('🔍 Raw API Response:');
                $this->line(json_encode($availability, JSON_PRETTY_PRINT));
            }

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error("❌ Error checking availability: {$e->getMessage()}");

            if ($this->getOutput()->isVerbose()) {
                $this->error("Stack trace: {$e->getTraceAsString()}");
            }

            return self::FAILURE;
        }
    }

    /**
     * Parse date string into Carbon instance
     */
    private function parseDate(string $dateString): Carbon
    {
        $dateString = strtolower(trim($dateString));

        // Handle relative dates
        switch ($dateString) {
            case 'today':
                return Carbon::today();
            case 'tomorrow':
                return Carbon::tomorrow();
        }

        // Handle day names (e.g., 'monday', 'tuesday')
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        if (in_array($dateString, $days)) {
            return Carbon::parse("next {$dateString}");
        }

        // Try parsing natural language dates
        try {
            return Carbon::parse($dateString);
        } catch (Throwable) {
            throw new InvalidArgumentException("Unable to parse date: {$dateString}. Try formats like 'today', 'tomorrow', 'monday', 'June 30', or '2025-06-30'");
        }
    }

    /**
     * Display availability for all times
     */
    private function displayAllAvailability(array $availability, int $partySize): void
    {
        $this->info("🕐 Available times for {$partySize} people:");
        $this->newLine();

        if (! isset($availability['availability']['people'][(string) $partySize])) {
            $this->warn("⚠️  No availability found for party size {$partySize}");

            // Show what party sizes are available
            if (isset($availability['availability']['people'])) {
                $availableSizes = array_keys($availability['availability']['people']);
                $this->info('Available party sizes: '.implode(', ', $availableSizes));
            }

            return;
        }

        $timeSlots = $availability['availability']['people'][(string) $partySize];

        if (blank($timeSlots)) {
            $this->warn("⚠️  No time slots available for party size {$partySize}");

            return;
        }

        foreach ($timeSlots as $time => $details) {
            $discount = isset($details['discount']) && $details['discount'] ? ' (with discount)' : '';
            $this->line("  ✅ {$time}{$discount}");
        }
    }

    /**
     * Display availability for a specific time
     */
    private function displaySpecificTimeAvailability(array $availability, string $time, int $partySize): void
    {
        $available = false;
        $hasDiscount = false;

        // Check if specific time and party size is available
        if (isset($availability['availability']['people'][(string) $partySize][$time])) {
            $available = true;
            $hasDiscount = $availability['availability']['people'][(string) $partySize][$time]['discount'] ?? false;
        }

        if ($available) {
            $discountText = $hasDiscount ? ' (with discount)' : '';
            $this->info("✅ Available at {$time} for {$partySize} people{$discountText}");
        } else {
            $this->error("❌ Not available at {$time} for {$partySize} people");

            // Show alternative times if available
            if (isset($availability['availability']['people'][(string) $partySize])) {
                $alternativeTimes = array_keys($availability['availability']['people'][(string) $partySize]);
                if (filled($alternativeTimes)) {
                    $this->newLine();
                    $this->info("💡 Alternative times for {$partySize} people:");
                    foreach ($alternativeTimes as $altTime) {
                        $details = $availability['availability']['people'][(string) $partySize][$altTime];
                        $discount = isset($details['discount']) && $details['discount'] ? ' (with discount)' : '';
                        $this->line("  ⏰ {$altTime}{$discount}");
                    }
                }
            }
        }
    }
}
