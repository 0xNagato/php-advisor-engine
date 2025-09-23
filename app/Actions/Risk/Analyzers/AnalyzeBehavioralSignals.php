<?php

namespace App\Actions\Risk\Analyzers;

use App\Enums\BookingStatus;
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
            if ($notesCheck['identical'] && $notesCheck['count'] >= 3) {
                // Only penalize if 3+ identical notes - could be legitimate concierge templates
                $score += 5; // Reduced from 25 to 5 - much lower weight
                $reasons[] = 'Identical notes across multiple bookings';
                $features['identical_notes'] = true;
                $features['identical_notes_count'] = $notesCheck['count'];
            }
        }

        // Check device fingerprint if available
        // Be very lenient with device velocity - concierges often use same device
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
            'features' => $features,
        ];
    }

    /**
     * Check submission velocity for burst activity
     */
    protected function checkSubmissionVelocity(string $email, string $phone, ?string $ipAddress): array
    {
        // Only count CONFIRMED bookings for submission velocity
        $confirmedBookings = Booking::query()->where('created_at', '>', now()->subMinutes(10))
            ->where(function ($query) use ($email, $phone, $ipAddress) {
                $query->where('guest_email', $email)
                    ->orWhere('guest_phone', $phone)
                    ->orWhere('ip_address', $ipAddress);
            })
            ->whereIn('status', [
                BookingStatus::CONFIRMED->value,
                BookingStatus::VENUE_CONFIRMED->value,
                BookingStatus::COMPLETED->value,
            ])
            ->select('created_at')
            ->get();

        $timestamps = $confirmedBookings->pluck('created_at')->map(fn ($dt) => $dt->timestamp)->toArray();
        $count = count($timestamps);

        // Cache the count for performance (30 minute TTL)
        $key = 'submission_velocity:'.hash('sha256', $email.$phone.$ipAddress);
        Cache::put($key, $timestamps, now()->addMinutes(30));

        // Check for burst activity (more than 3 in 10 minutes)
        if ($count > 3) {
            return [
                'is_burst' => true,
                'score' => min(40, ($count - 3) * 10),
                'reason' => "Burst activity: {$count} submissions in 10 minutes",
                'count' => $count,
            ];
        }

        return [
            'is_burst' => false,
            'score' => 0,
            'count' => $count,
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

        // Check recent CONFIRMED bookings for identical notes
        $count = Booking::query()->where('created_at', '>', now()->subDays(7))
            ->where(function ($query) use ($email, $phone) {
                $query->where('guest_email', $email)
                    ->orWhere('guest_phone', $phone);
            })
            ->whereNotNull('notes')
            ->where('notes', $notes)
            ->whereIn('status', [
                BookingStatus::CONFIRMED->value,
                BookingStatus::VENUE_CONFIRMED->value,
                BookingStatus::COMPLETED->value,
            ])
            ->count();

        return [
            'identical' => $count > 1,
            'count' => $count,
        ];
    }

    /**
     * Check device velocity
     */
    protected function checkDeviceVelocity(string $device): array
    {
        // Only count CONFIRMED bookings for device velocity calculation
        $confirmedBookings = Booking::query()->where('device', $device)
            ->where('created_at', '>', now()->subHour())
            ->whereIn('status', [
                BookingStatus::CONFIRMED->value,
                BookingStatus::VENUE_CONFIRMED->value,
                BookingStatus::COMPLETED->value,
            ])
            ->select('created_at')
            ->get();

        $timestamps = $confirmedBookings->pluck('created_at')->map(fn ($dt) => $dt->timestamp)->toArray();
        $count = count($timestamps);

        // Cache the count for performance (2 hour TTL)
        $key = 'device_velocity:'.$device;
        Cache::put($key, $timestamps, now()->addHours(2));

        // Check for extreme automation (device-based is more reliable)
        // Realistic: 2-3 mins per booking, so 5 mins = max 2 bookings
        if ($count >= 50) {
            return [
                'is_suspicious' => true,
                'score' => 100, // Maximum penalty for extreme device automation
                'reason' => "Extreme device automation: {$count} CONFIRMED bookings from same device",
                'count' => $count,
            ];
        }

        // Check for short-term bursts first (5-minute window) - device-based
        if ($count >= 4) {
            return [
                'is_suspicious' => true,
                'score' => 40, // High penalty for device bursts
                'reason' => "Device burst: {$count} CONFIRMED bookings in 5 minutes",
                'count' => $count,
            ];
        }

        // Check hourly patterns - device-based thresholds (stricter since device is more reliable)
        // Realistic: 15-20 bookings per hour for very busy concierge
        if ($count > 30) {
            return [
                'is_suspicious' => true,
                'score' => 80, // Very high penalty for extreme device volume
                'reason' => "Extreme device volume: {$count} CONFIRMED bookings in last hour",
                'count' => $count,
            ];
        } elseif ($count > 25) {
            return [
                'is_suspicious' => true,
                'score' => 60, // High penalty for very high device volume
                'reason' => "Very high device activity: {$count} CONFIRMED bookings in last hour",
                'count' => $count,
            ];
        } elseif ($count > 15) {
            return [
                'is_suspicious' => true,
                'score' => 30, // Moderate penalty for high device volume
                'reason' => "High device activity: {$count} CONFIRMED bookings in last hour",
                'count' => $count,
            ];
        } elseif ($count > 10) {
            return [
                'is_suspicious' => true,
                'score' => 15, // Lower penalty for moderate device volume
                'reason' => "Elevated device activity: {$count} CONFIRMED bookings in last hour",
                'count' => $count,
            ];
        } elseif ($count > 3) {
            // 3+ per hour is normal concierge activity
            return [
                'is_suspicious' => true,
                'score' => 0, // No penalty for normal concierge activity
                'reason' => "Multiple bookings: {$count} CONFIRMED from same device",
                'count' => $count,
            ];
        }

        return [
            'is_suspicious' => false,
            'score' => 0,
            'count' => $count,
        ];
    }

    /**
     * Check for patterns in booking times
     */
    protected function checkBookingTimePattern(string $email, string $phone): array
    {
        // Check if user always books at exact same time of day
        $bookings = Booking::query()->where(function ($query) use ($email, $phone) {
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
        $key = 'failed_attempts:'.hash('sha256', $email.$phone.$ipAddress);
        $attempts = Cache::get($key, 0);

        return ['count' => $attempts];
    }

    /**
     * Check if user is trying multiple venues rapidly
     */
    protected function checkVenueHopping(string $email, string $phone): array
    {
        $key = 'venue_attempts:'.hash('sha256', $email.$phone);
        $venues = Cache::get($key, []);

        // Clean old entries (older than 30 minutes)
        $venues = array_filter($venues, fn ($ts) => $ts > now()->subMinutes(30)->timestamp);

        $uniqueVenues = count(array_unique(array_keys($venues)));

        if ($uniqueVenues > 3) {
            return [
                'is_hopping' => true,
                'count' => $uniqueVenues,
            ];
        }

        return [
            'is_hopping' => false,
            'count' => $uniqueVenues,
        ];
    }
}
