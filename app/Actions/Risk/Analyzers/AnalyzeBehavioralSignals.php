<?php

namespace App\Actions\Risk\Analyzers;

use App\Models\Booking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class AnalyzeBehavioralSignals
{
    use AsAction;

    /**
     * Analyze behavioral signals for risk
     *
     * @return array{score: int, reasons: array<string>, features: array<string, mixed>}
     */
    public function handle(
        string $email,
        string $phone,
        ?string $ipAddress,
        ?string $notes,
        ?Booking $booking = null
    ): array {
        $score = 0;
        $reasons = [];
        $features = [];

        // Check submission velocity
        $velocityCheck = $this->checkSubmissionVelocity($email, $phone, $ipAddress);
        if ($velocityCheck['is_burst']) {
            $score += $velocityCheck['score'];
            $reasons[] = $velocityCheck['reason'];
            $features['submission_burst'] = true;
            $features['submission_velocity'] = $velocityCheck['count'];
        }

        // Check for identical notes across bookings
        if ($notes) {
            $notesCheck = $this->checkIdenticalNotes($notes, $email, $phone);
            if ($notesCheck['identical']) {
                $score += 25;
                $reasons[] = 'Identical notes across multiple bookings';
                $features['identical_notes'] = true;
                $features['identical_notes_count'] = $notesCheck['count'];
            }
        }

        // Check device fingerprint if available
        if ($booking && $booking->device) {
            $deviceCheck = $this->checkDeviceVelocity($booking->device);
            if ($deviceCheck['is_suspicious']) {
                $score += $deviceCheck['score'];
                $reasons[] = $deviceCheck['reason'];
                $features['device_velocity'] = $deviceCheck['count'];
            }
        }

        // Check for rapid form submission (if we have timing data)
        if ($booking && $booking->clicked_at && $booking->confirmed_at) {
            $submissionTime = $booking->confirmed_at->diffInSeconds($booking->clicked_at);
            if ($submissionTime < 5) {
                $score += 30;
                $reasons[] = 'Form submitted too quickly';
                $features['rapid_submission'] = true;
                $features['submission_time'] = $submissionTime;
            }
        }

        // Check for pattern in booking times (always booking at exact same time)
        $bookingPattern = $this->checkBookingTimePattern($email, $phone);
        if ($bookingPattern['suspicious']) {
            $score += 20;
            $reasons[] = 'Suspicious booking time pattern';
            $features['time_pattern'] = true;
        }

        // Check for multiple failed attempts
        $failedAttempts = $this->checkFailedAttempts($email, $phone, $ipAddress);
        if ($failedAttempts['count'] > 3) {
            $score += min(30, $failedAttempts['count'] * 10);
            $reasons[] = "Multiple failed booking attempts: {$failedAttempts['count']}";
            $features['failed_attempts'] = $failedAttempts['count'];
        }

        // Check if user is trying multiple venues in short time
        $venueHopping = $this->checkVenueHopping($email, $phone);
        if ($venueHopping['is_hopping']) {
            $score += 25;
            $reasons[] = 'Multiple venues attempted in short time';
            $features['venue_hopping'] = true;
            $features['venues_attempted'] = $venueHopping['count'];
        }

        return [
            'score' => min(100, $score),
            'reasons' => $reasons,
            'features' => $features
        ];
    }

    /**
     * Check submission velocity for burst activity
     */
    protected function checkSubmissionVelocity(string $email, string $phone, ?string $ipAddress): array
    {
        $key = 'submission_velocity:' . hash('sha256', $email . $phone . $ipAddress);
        $timestamps = Cache::get($key, []);

        // Clean old entries (older than 10 minutes)
        $timestamps = array_filter($timestamps, fn($ts) => $ts > now()->subMinutes(10)->timestamp);

        // Add current timestamp
        $timestamps[] = now()->timestamp;

        // Update cache
        Cache::put($key, $timestamps, now()->addMinutes(30));

        $count = count($timestamps);

        // Check for burst activity (more than 3 in 10 minutes)
        if ($count > 3) {
            return [
                'is_burst' => true,
                'score' => min(40, ($count - 3) * 10),
                'reason' => "Burst activity: {$count} submissions in 10 minutes",
                'count' => $count
            ];
        }

        return [
            'is_burst' => false,
            'score' => 0,
            'count' => $count
        ];
    }

    /**
     * Check for identical notes across bookings
     */
    protected function checkIdenticalNotes(string $notes, string $email, string $phone): array
    {
        if (strlen($notes) < 10) {
            return ['identical' => false, 'count' => 0];
        }

        $notesHash = hash('sha256', strtolower(trim($notes)));

        // Check recent bookings for identical notes
        $count = Booking::where('created_at', '>', now()->subDays(7))
            ->where(function ($query) use ($email, $phone, $notes) {
                $query->where('guest_email', $email)
                    ->orWhere('guest_phone', $phone);
            })
            ->whereNotNull('notes')
            ->where('notes', $notes)
            ->count();

        return [
            'identical' => $count > 1,
            'count' => $count
        ];
    }

    /**
     * Check device velocity
     */
    protected function checkDeviceVelocity(string $device): array
    {
        $key = 'device_velocity:' . $device;
        $timestamps = Cache::get($key, []);

        // Clean old entries (older than 1 hour)
        $timestamps = array_filter($timestamps, fn($ts) => $ts > now()->subHour()->timestamp);

        // Add current timestamp
        $timestamps[] = now()->timestamp;

        // Update cache
        Cache::put($key, $timestamps, now()->addHours(2));

        $count = count($timestamps);

        // Check for suspicious activity - be much more aggressive
        if ($count > 20) {
            // Extreme abuse
            return [
                'is_suspicious' => true,
                'score' => 80,
                'reason' => "Extreme device abuse: {$count} bookings from same device",
                'count' => $count
            ];
        } elseif ($count > 10) {
            // Very high
            return [
                'is_suspicious' => true,
                'score' => 60,
                'reason' => "Very high device activity: {$count} bookings from same device",
                'count' => $count
            ];
        } elseif ($count > 5) {
            // High
            return [
                'is_suspicious' => true,
                'score' => 40,
                'reason' => "High device activity: {$count} bookings from same device",
                'count' => $count
            ];
        } elseif ($count > 3) {
            // Moderate
            return [
                'is_suspicious' => true,
                'score' => 20,
                'reason' => "Multiple bookings: {$count} from same device",
                'count' => $count
            ];
        }

        return [
            'is_suspicious' => false,
            'score' => 0,
            'count' => $count
        ];
    }

    /**
     * Check for patterns in booking times
     */
    protected function checkBookingTimePattern(string $email, string $phone): array
    {
        // Check if user always books at exact same time of day
        $bookings = Booking::where(function ($query) use ($email, $phone) {
            $query->where('guest_email', $email)
                ->orWhere('guest_phone', $phone);
        })
            ->where('created_at', '>', now()->subDays(30))
            ->select(DB::raw('EXTRACT(HOUR FROM created_at) as hour'))
            ->get();

        if ($bookings->count() < 3) {
            return ['suspicious' => false];
        }

        // Check if all bookings are at the same hour
        $hours = $bookings->pluck('hour')->unique();
        if ($hours->count() == 1 && $bookings->count() >= 3) {
            return ['suspicious' => true];
        }

        return ['suspicious' => false];
    }

    /**
     * Check for failed booking attempts
     */
    protected function checkFailedAttempts(string $email, string $phone, ?string $ipAddress): array
    {
        $key = 'failed_attempts:' . hash('sha256', $email . $phone . $ipAddress);
        $attempts = Cache::get($key, 0);

        return ['count' => $attempts];
    }

    /**
     * Check if user is trying multiple venues rapidly
     */
    protected function checkVenueHopping(string $email, string $phone): array
    {
        $key = 'venue_attempts:' . hash('sha256', $email . $phone);
        $venues = Cache::get($key, []);

        // Clean old entries (older than 30 minutes)
        $venues = array_filter($venues, fn($ts) => $ts > now()->subMinutes(30)->timestamp);

        $uniqueVenues = count(array_unique(array_keys($venues)));

        if ($uniqueVenues > 3) {
            return [
                'is_hopping' => true,
                'count' => $uniqueVenues
            ];
        }

        return [
            'is_hopping' => false,
            'count' => $uniqueVenues
        ];
    }
}