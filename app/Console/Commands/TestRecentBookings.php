<?php

namespace App\Console\Commands;

use App\Actions\Risk\ScoreBookingSuspicion;
use App\Models\Booking;
use Illuminate\Console\Command;

class TestRecentBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-recent-bookings {--days=1 : Number of days to look back} {--limit=10 : Number of CONFIRMED bookings to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test risk scoring on recent CONFIRMED bookings only to verify false positive fixes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $limit = $this->option('limit');

        $this->info("Testing Risk Scoring on Recent Bookings");
        $this->info("=========================================");
        $this->info("Looking back: {$days} days");
        $this->info("Testing limit: {$limit} bookings");
        $this->newLine();

        // Get recent CONFIRMED bookings
        $this->info("Note: Testing risk scoring logic on recent CONFIRMED bookings only.");
        $this->newLine();

        // Get recent CONFIRMED bookings from the database
        $recentBookings = Booking::where('created_at', '>', now()->subDays($days))
            ->whereIn('status', [
                \App\Enums\BookingStatus::CONFIRMED->value,
                \App\Enums\BookingStatus::VENUE_CONFIRMED->value,
                \App\Enums\BookingStatus::COMPLETED->value
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get(['id', 'guest_first_name', 'guest_last_name', 'guest_email', 'guest_phone', 'notes', 'ip_address', 'created_at', 'status']);

        if ($recentBookings->isEmpty()) {
            $this->warn("No recent bookings found in the last {$days} days.");
            return;
        }

        $this->info("Found {$recentBookings->count()} recent CONFIRMED bookings to test");
        $this->newLine();

        $results = [];
        $totalScore = 0;

        foreach ($recentBookings as $booking) {
            $this->info("Testing Booking #{$booking->id}: {$booking->guest_first_name} {$booking->guest_last_name} ({$booking->status->label()})");
            $this->info("Email: {$booking->guest_email}");

            try {
                // Test risk scoring on this booking
                $result = ScoreBookingSuspicion::run(
                    $booking->guest_email ?? '',
                    $booking->guest_phone ?? '',
                    trim(($booking->guest_first_name ?? '') . ' ' . ($booking->guest_last_name ?? '')),
                    $booking->ip_address,
                    'Mozilla/5.0 (Booking Browser)',
                    $booking->notes,
                    null // No booking object for this test
                );

                $riskLevel = $result['score'] >= 80 ? 'HIGH' : ($result['score'] >= 40 ? 'MEDIUM' : 'LOW');

                $this->info("Risk Score: {$result['score']}/100");
                $this->info("Risk Level: {$riskLevel}");

                if (!empty($result['reasons'])) {
                    $this->info("Reasons: " . implode(', ', array_slice($result['reasons'], 0, 3)));
                }

                // Categorize the booking
                $isLegitimate = $this->isLegitimateBooking($booking, $result['score']);
                $status = $isLegitimate ? '✅ LEGITIMATE' : '❌ SUSPICIOUS';
                $this->info("Assessment: {$status}");

                $results[] = [
                    'booking_id' => $booking->id,
                    'name' => trim(($booking->guest_first_name ?? '') . ' ' . ($booking->guest_last_name ?? '')),
                    'email' => $booking->guest_email,
                    'score' => $result['score'],
                    'risk_level' => $riskLevel,
                    'assessment' => $isLegitimate ? 'LEGITIMATE' : 'SUSPICIOUS'
                ];

                $totalScore += $result['score'];

            } catch (\Exception $e) {
                $this->error("Error testing booking #{$booking->id}: {$e->getMessage()}");
            }

            $this->newLine();
        }

        // Show summary statistics
        $this->info('Summary Statistics:');
        $this->info('===================');

        $legitimateCount = collect($results)->where('assessment', 'LEGITIMATE')->count();
        $suspiciousCount = collect($results)->where('assessment', 'SUSPICIOUS')->count();
        $averageScore = $totalScore / count($results);

        $this->info("Total Bookings Tested: " . count($results));
        $this->info("Legitimate Bookings: {$legitimateCount}");
        $this->info("Suspicious Bookings: {$suspiciousCount}");
        $this->info("Average Risk Score: " . round($averageScore, 1) . "/100");
        $this->info("False Positive Rate: " . round(($suspiciousCount / count($results)) * 100, 1) . "%");

        // Show breakdown by risk level
        $this->info("\nRisk Level Distribution:");
        $lowRisk = collect($results)->where('risk_level', 'LOW')->count();
        $mediumRisk = collect($results)->where('risk_level', 'MEDIUM')->count();
        $highRisk = collect($results)->where('risk_level', 'HIGH')->count();

        $this->table(
            ['Risk Level', 'Count', 'Percentage'],
            [
                ['Low (0-39)', $lowRisk, round(($lowRisk / count($results)) * 100, 1) . '%'],
                ['Medium (40-79)', $mediumRisk, round(($mediumRisk / count($results)) * 100, 1) . '%'],
                ['High (80+)', $highRisk, round(($highRisk / count($results)) * 100, 1) . '%'],
            ]
        );

        // Recommendations
        $this->newLine();
        if ($suspiciousCount > ($legitimateCount * 0.3)) {
            $this->warn("⚠️ High false positive rate detected. Consider further tuning of thresholds.");
        } else {
            $this->info("✅ Risk scoring appears to be working well with acceptable false positive rate.");
        }
    }

    /**
     * Determine if a booking appears to be legitimate based on various factors
     */
    protected function isLegitimateBooking($booking, $score): bool
    {
        $name = trim(($booking->guest_first_name ?? '') . ' ' . ($booking->guest_last_name ?? ''));
        $email = $booking->guest_email ?? '';
        $notes = $booking->notes ?? '';

        // Legitimate indicators
        $legitimateIndicators = 0;

        // Professional/business email domains
        if (preg_match('/@([a-zA-Z0-9-]+\.)+(com|net|org|edu|gov|mil|biz|info|mobi|name|aero|asia|cat|coop|int|jobs|mobi|museum|post|pro|tel|travel|xxx)$/i', $email)) {
            $legitimateIndicators++;
        }

        // Business-related notes
        if (preg_match('/(business|client|meeting|dinner|corporate|company|work|office|professional)/i', $notes)) {
            $legitimateIndicators++;
        }

        // Normal-looking name (not obviously fake)
        if (strlen($name) > 5 && !preg_match('/(test|fake|bot|robot|lorem|ipsum|example|demo|sample)/i', $name)) {
            $legitimateIndicators++;
        }

        // International phone numbers can be legitimate
        if (!empty($booking->guest_phone) && strlen($booking->guest_phone) > 10) {
            $legitimateIndicators++;
        }

        // Very low scores are likely legitimate
        if ($score < 20) {
            return true;
        }

        // Medium scores with legitimate indicators are likely false positives
        if ($score < 60 && $legitimateIndicators >= 2) {
            return true;
        }

        return false;
    }
}
