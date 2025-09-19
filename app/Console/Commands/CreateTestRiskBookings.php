<?php

namespace App\Console\Commands;

use App\Actions\Risk\ProcessBookingRisk;
use App\Models\Booking;
use App\Models\Concierge;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CreateTestRiskBookings extends Command
{
    protected $signature = 'test:create-risk-bookings';
    protected $description = 'Create test bookings with various risk levels';

    public function handle()
    {
        // Enable AI screening
        config(['app.ai_screening_enabled' => true]);

        // Get a concierge for creating bookings
        $concierge = Concierge::with('user')->first();
        if (!$concierge) {
            $this->error('No concierge found');
            return Command::FAILURE;
        }

        // Get available schedules from the view for tomorrow
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $availableSchedules = DB::table('schedule_with_bookings')
            ->where('booking_date', $tomorrow)
            ->where('is_available', true)
            ->where('remaining_tables', '>', 0)
            ->where('prime_time', false) // Non-prime to avoid payment
            ->limit(10)
            ->get();

        if ($availableSchedules->isEmpty()) {
            $this->error('No available non-prime schedules found for tomorrow');
            return Command::FAILURE;
        }

        $testCases = [
            [
                'name' => 'Low Risk - Normal Booking',
                'guest_first_name' => 'John',
                'guest_last_name' => 'Smith',
                'guest_email' => 'john.smith@gmail.com',
                'guest_phone' => '+14155551234',
                'guest_count' => 4,
                'notes' => 'Anniversary dinner',
                'ip_address' => '73.162.195.22', // Residential IP
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            ],
            [
                'name' => 'Medium Risk - Suspicious Email',
                'guest_first_name' => 'Test',
                'guest_last_name' => 'User',
                'guest_email' => 'test123@tempmail.com',
                'guest_phone' => '+14155559999',
                'guest_count' => 8,
                'notes' => null,
                'ip_address' => '192.168.1.1', // Private IP
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            [
                'name' => 'High Risk - Offensive Content',
                'guest_first_name' => 'Fuck',
                'guest_last_name' => 'You',
                'guest_email' => 'offensive@mailinator.com',
                'guest_phone' => '+15555555555',
                'guest_count' => 15,
                'notes' => 'This is bullshit',
                'ip_address' => '45.142.122.1', // Datacenter IP
                'user_agent' => 'Python-urllib/3.9',
            ],
            [
                'name' => 'High Risk - Fake Name Pattern',
                'guest_first_name' => 'Asdf',
                'guest_last_name' => 'Qwerty',
                'guest_email' => 'random@guerrillamail.com',
                'guest_phone' => '+11111111111',
                'guest_count' => 20,
                'notes' => 'test test test',
                'ip_address' => '104.236.72.34', // VPN IP
                'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)',
            ],
            [
                'name' => 'Medium Risk - Suspicious Name',
                'guest_first_name' => 'Mickey',
                'guest_last_name' => 'Mouse',
                'guest_email' => 'mickey.mouse@disneyland.com',
                'guest_phone' => '+13105551234',
                'guest_count' => 6,
                'notes' => 'Birthday party for kids',
                'ip_address' => '8.8.8.8', // Google DNS
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
            ],
        ];

        foreach ($testCases as $index => $test) {
            $this->info("Creating: {$test['name']}");

            // Get a random available schedule
            $schedule = $availableSchedules->random();

            try {
                // Create the booking directly
                $booking = Booking::create([
                    'schedule_template_id' => $schedule->schedule_template_id,
                    'uuid' => Str::uuid(),
                    'concierge_id' => $concierge->id,
                    'guest_first_name' => $test['guest_first_name'],
                    'guest_last_name' => $test['guest_last_name'],
                    'guest_email' => $test['guest_email'],
                    'guest_phone' => $test['guest_phone'],
                    'booking_at' => $schedule->booking_at,
                    'guest_count' => min($test['guest_count'], $schedule->party_size ?? 20),
                    'total_fee' => 0, // Non-prime
                    'currency' => 'USD',
                    'status' => 'confirmed',
                    'is_prime' => false,
                    'confirmed_at' => now(),
                    'notes' => $test['notes'],
                    'ip_address' => $test['ip_address'],
                    'user_agent' => $test['user_agent'],
                    'source' => 'test',
                    'device' => 'cli',
                ]);

                // Process risk assessment
                ProcessBookingRisk::run($booking);

                // Reload to get the risk data
                $booking->refresh();
                $booking->load('venue');

                $this->line("  ID: {$booking->id}");
                $this->line("  Venue: {$booking->venue->name}");
                $this->line("  Risk Score: {$booking->risk_score}/100");
                $this->line("  Risk State: " . ($booking->risk_state ?: 'none'));

                if ($booking->risk_metadata) {
                    $metadata = $booking->risk_metadata;

                    // Check if it's a Laravel Data object or array
                    if (is_object($metadata)) {
                        $this->line("  AI Used: " . ($metadata->llmUsed ? 'Yes' : 'No'));

                        if ($metadata->llmUsed && $metadata->llmResponse) {
                            $aiData = json_decode($metadata->llmResponse, true);
                            if ($aiData) {
                                $this->line("  AI Score: " . ($aiData['risk_score'] ?? 'N/A'));
                                $this->line("  AI Confidence: " . ($aiData['confidence'] ?? 'N/A'));
                            }
                        }

                        // Try to get breakdown
                        if (method_exists($metadata, 'getFormattedBreakdown')) {
                            $breakdown = $metadata->getFormattedBreakdown();
                            if (!empty($breakdown)) {
                                $this->line("  Risk Breakdown:");
                                foreach ($breakdown as $category => $data) {
                                    $this->line("    - {$category}: {$data['score']} (weighted: {$data['weighted']})");
                                }
                            }
                        }
                    } else {
                        // Handle as array/json
                        $metadataArray = is_string($metadata) ? json_decode($metadata, true) : (array) $metadata;
                        $this->line("  AI Used: " . (($metadataArray['llmUsed'] ?? false) ? 'Yes' : 'No'));
                    }
                }

                if (!empty($booking->risk_reasons)) {
                    $this->line("  Risk Reasons:");
                    $reasons = is_string($booking->risk_reasons) ? json_decode($booking->risk_reasons, true) : $booking->risk_reasons;
                    foreach ($reasons as $reason) {
                        $this->line("    - {$reason}");
                    }
                }

                $this->info("  View at: /platform/risk-reviews/{$booking->id}");
                $this->line("");

            } catch (\Exception $e) {
                $this->error("  Failed to create booking: " . $e->getMessage());
                $this->line("");
            }
        }

        // Show summary
        $this->info("=== Summary ===");
        $recentBookings = Booking::whereNotNull('risk_score')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('risk_score', 'desc')
            ->get(['id', 'guest_first_name', 'guest_last_name', 'risk_score', 'risk_state']);

        $this->table(
            ['ID', 'Guest Name', 'Risk Score', 'Risk State'],
            $recentBookings->map(function ($b) {
                return [
                    $b->id,
                    "{$b->guest_first_name} {$b->guest_last_name}",
                    "{$b->risk_score}/100",
                    $b->risk_state ?: 'none'
                ];
            })
        );

        return Command::SUCCESS;
    }
}