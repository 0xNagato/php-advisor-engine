<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class BookingAnalyticsWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.booking-analytics';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    public string $dateType = 'created';

    public function getShowBookingTimeProperty(): bool
    {
        return $this->dateType === 'booking';
    }

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan;
    }

    public function getAnalytics(): array
    {
        $userTimezone = auth()->user()?->timezone ?? config('app.default_timezone');

        // Parse dates in user timezone then convert to UTC for database queries
        $startDateUTC = Carbon::parse(
            $this->filters['startDate'] ?? now($userTimezone)->subDays(30)->format('Y-m-d'),
            $userTimezone
        )->startOfDay()->setTimezone('UTC');

        $endDateUTC = Carbon::parse(
            $this->filters['endDate'] ?? now($userTimezone)->format('Y-m-d'),
            $userTimezone
        )->endOfDay()->setTimezone('UTC');

        return [
            'topVenues' => $this->getTopVenues($startDateUTC, $endDateUTC),
            'popularTimes' => $this->getPopularTimes($startDateUTC, $endDateUTC),
            'partySizes' => $this->getPartySizes($startDateUTC, $endDateUTC),
            'primeAnalysis' => $this->getPrimeAnalysis($startDateUTC, $endDateUTC),
            'leadTimeAnalysis' => $this->getLeadTimeAnalysis($startDateUTC, $endDateUTC),
            'dayAnalysis' => $this->getDayAnalysis($startDateUTC, $endDateUTC),
            'calendarDayAnalysis' => $this->getCalendarDayAnalysis($startDateUTC, $endDateUTC),
            'statusAnalysis' => $this->getStatusAnalysis($startDateUTC, $endDateUTC),
            'topConcierges' => $this->getTopConcierges($startDateUTC, $endDateUTC),
            'bookingTypeAnalysis' => $this->getBookingTypeAnalysis($startDateUTC, $endDateUTC),
        ];
    }

    protected function getDateColumn(): string
    {
        return $this->getShowBookingTimeProperty() ? 'bookings.booking_at' : 'bookings.created_at';
    }

    protected function getTopVenues(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        // Join directly to the venue table via the schedule template
        $bookings = Booking::select(['bookings.id', 'bookings.schedule_template_id'])
            ->with(['venue']) // Use the venue relationship from the Booking model
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->get();

        $total = $bookings->count();

        // Group by venue
        $venueGroups = $bookings->groupBy(function ($booking) {
            return $booking->venue->id;
        });

        // Count bookings for each venue
        $venueCounts = [];
        foreach ($venueGroups as $venueId => $venueBookings) {
            $venueCounts[] = [
                'id' => $venueId,
                'name' => $venueBookings->first()->venue->name,
                'count' => $venueBookings->count(),
            ];
        }

        // Sort by booking count and take top 5
        $venueCounts = collect($venueCounts)->sortByDesc('count')->take(5);

        // Format results
        return $venueCounts->map(function ($venue) use ($total) {
            return [
                'name' => $venue['name'],
                'booking_count' => $venue['count'],
                'percentage' => $total > 0 ? round(($venue['count'] / $total) * 100, 1) : 0,
            ];
        })->values()->toArray();
    }

    protected function getPopularTimes(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        // Get all bookings within the date range
        $bookings = Booking::select('booking_at')
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->get();

        // Group by hour using Carbon
        $timeGroups = $bookings->groupBy(function ($booking) {
            $time = Carbon::parse($booking->booking_at);

            // Format as "7:00 PM" - group by hour
            return $time->format('g:i A');
        });

        // Count each time slot
        $timeSlotCounts = $timeGroups->map->count()->sortDesc();

        // Take top 5
        $popularTimes = $timeSlotCounts->take(5);

        $total = $bookings->count();

        // Format the results
        $results = [];
        foreach ($popularTimes as $timeSlot => $count) {
            $results[] = [
                'time_slot' => $timeSlot,
                'booking_count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $results;
    }

    protected function getPartySizes(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        // Get all confirmed bookings
        $bookings = Booking::select('guest_count')
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->get();

        // Group by guest count
        $guestCountGroups = $bookings->groupBy('guest_count');

        // Count occurrences of each guest count
        $guestCounts = $guestCountGroups->map->count();

        $total = $bookings->count();

        // Format the results
        $results = [];
        foreach ($guestCounts as $guestCount => $count) {
            $results[] = [
                'guest_count' => $guestCount,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        // Sort by guest count
        return collect($results)->sortBy('guest_count')->values()->toArray();
    }

    protected function getPrimeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();
        $currencyService = app(CurrencyConversionService::class);

        // Get all confirmed bookings
        $bookings = Booking::whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->get();

        // Group by prime status
        $primeGroups = $bookings->groupBy('is_prime');

        // Process each prime status group
        $results = [];
        foreach ($primeGroups as $isPrime => $primeBookings) {
            // Group bookings by currency for fee calculations
            $currencyGroups = $primeBookings->groupBy('currency');

            $avgFees = [];
            $avgPlatformEarnings = [];

            foreach ($currencyGroups as $currency => $currencyBookings) {
                $avgFees[$currency] = $currencyBookings->avg('total_fee');
                $avgPlatformEarnings[$currency] = $currencyBookings->avg('platform_earnings');
            }

            $results[$isPrime] = [
                'count' => $primeBookings->count(),
                'avg_fee_usd' => $currencyService->convertToUSD($avgFees),
                'avg_platform_earnings_usd' => $currencyService->convertToUSD($avgPlatformEarnings),
            ];
        }

        return $results;
    }

    protected function getLeadTimeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        // Get bookings within the specified date range
        $bookings = Booking::query()
            ->select(['booking_at', 'created_at'])
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->get();

        // Initialize the category counters
        $categories = [
            'Same day' => 0,
            'Next day' => 0,
            '2-7 days' => 0,
            '8-14 days' => 0,
            '15-30 days' => 0,
            '30+ days' => 0,
        ];

        foreach ($bookings as $booking) {
            // Extract date portions and ensure they're Carbon instances
            $bookingDate = Carbon::parse($booking->booking_at)->startOfDay();
            $createdDate = Carbon::parse($booking->created_at)->startOfDay();

            // Correct order: $createdDate->diffInDays($bookingDate, false)
            // This returns positive days when booking is after creation
            $daysInAdvance = $createdDate->diffInDays($bookingDate, false);

            // Check if booking was made for same day
            if ($daysInAdvance == 0) {
                $categories['Same day']++;
            } elseif ($daysInAdvance == 1) {
                $categories['Next day']++;
            } elseif ($daysInAdvance >= 2 && $daysInAdvance <= 7) {
                $categories['2-7 days']++;
            } elseif ($daysInAdvance >= 8 && $daysInAdvance <= 14) {
                $categories['8-14 days']++;
            } elseif ($daysInAdvance >= 15 && $daysInAdvance <= 30) {
                $categories['15-30 days']++;
            } elseif ($daysInAdvance > 30) {
                $categories['30+ days']++;
            } else {
                // Handle bookings in the past (negative days) - should be rare
                $categories['Same day']++;
            }
        }

        $total = $bookings->count();

        // Format the results and filter out empty categories
        $results = [];
        foreach ($categories as $category => $count) {
            // Only include categories with at least one booking
            if ($count > 0) {
                $results[] = [
                    'lead_time' => $category,
                    'count' => $count,
                    'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                ];
            }
        }

        return $results;
    }

    protected function getDayAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        // Get bookings within date range
        $bookings = Booking::select($dateColumn)
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->get();

        // Map bookings to their day of week
        $dayNames = $bookings->map(function ($booking) {
            // Get the appropriate date field
            $date = $booking->{$this->getShowBookingTimeProperty() ? 'booking_at' : 'created_at'};

            // Return the day name (Monday, Tuesday, etc.)
            return $date->format('l');
        });

        // Count by day name
        $dayCounts = $dayNames->countBy();

        $total = $bookings->count();

        // Ensure days are ordered correctly (Monday to Sunday)
        $orderedDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $results = [];

        // Build results array with proper ordering
        foreach ($orderedDays as $day) {
            $count = $dayCounts->get($day, 0);
            $results[] = [
                'day_name' => $day,
                'booking_count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $results;
    }

    protected function getCalendarDayAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        // Get bookings within date range
        $bookings = Booking::select($dateColumn)
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->get();

        // Group by calendar date (YYYY-MM-DD)
        $dateGroups = $bookings->groupBy(function ($booking) {
            $date = $booking->{$this->getShowBookingTimeProperty() ? 'booking_at' : 'created_at'};

            return Carbon::parse($date)->format('Y-m-d');
        });

        // Count occurrences of each calendar date and build formatted results
        $results = [];

        foreach ($dateGroups as $dateString => $bookingsOnDate) {
            $carbonDate = Carbon::parse($dateString);

            $results[] = [
                'date' => $carbonDate->format('M j'),
                'calendar_date' => $dateString,
                'day_name' => $carbonDate->format('l'),
                'booking_count' => $bookingsOnDate->count(),
            ];
        }

        // Sort by calendar date
        $results = collect($results)->sortBy('calendar_date')->values()->toArray();

        return $results;
    }

    protected function getStatusAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        // Get all bookings within the date range with status
        $bookings = Booking::select('status')
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->get();

        // Group by status and count
        $statusGroups = $bookings->groupBy(function ($booking) {
            return $booking->status->value;
        });

        // Count the number of each status
        $statusCounts = $statusGroups->map->count();

        $total = $bookings->count();

        // Format results including status label from enum
        $results = [];
        foreach ($statusCounts as $statusValue => $count) {
            $status = BookingStatus::from($statusValue);
            $results[] = [
                'status' => $status->label(),
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $results;
    }

    protected function getTopConcierges(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();
        $currencyService = app(CurrencyConversionService::class);

        // Get all confirmed bookings with their concierges
        $bookings = Booking::with(['concierge.user'])
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->whereNotNull('concierge_id')
            ->get();

        $total = $bookings->count();

        // Group by concierge
        $conciergeGroups = $bookings->groupBy('concierge_id');

        // Format and process each concierge's data
        $conciergeData = [];
        foreach ($conciergeGroups as $conciergeId => $conciergeBookings) {
            $concierge = $conciergeBookings->first()->concierge;
            $user = $concierge->user;

            // Group bookings by currency for fee calculations
            $currencyGroups = $conciergeBookings->groupBy('currency');

            $avgFees = [];
            $avgPlatformEarnings = [];

            foreach ($currencyGroups as $currency => $currencyBookings) {
                $avgFees[$currency] = $currencyBookings->avg('total_fee');
                $avgPlatformEarnings[$currency] = $currencyBookings->avg('platform_earnings');
            }

            $conciergeData[] = [
                'id' => $conciergeId,
                'name' => $user->first_name.' '.$user->last_name,
                'booking_count' => $conciergeBookings->count(),
                'percentage' => $total > 0 ? round(($conciergeBookings->count() / $total) * 100, 1) : 0,
                'avg_fee_usd' => $currencyService->convertToUSD($avgFees),
                'avg_platform_earnings_usd' => $currencyService->convertToUSD($avgPlatformEarnings),
            ];
        }

        // Sort by booking count and take top 5
        return collect($conciergeData)->sortByDesc('booking_count')->take(5)->values()->toArray();
    }

    protected function getBookingTypeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();
        $currencyService = app(CurrencyConversionService::class);

        // Get all confirmed bookings
        $bookings = Booking::whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->get();

        // Group by VIP status (has vip_code_id or not)
        $vipGroups = $bookings->groupBy(function ($booking) {
            return $booking->vip_code_id !== null;
        });

        // Process each VIP status group
        $results = [];
        foreach ($vipGroups as $isVip => $vipBookings) {
            // Group bookings by currency for fee calculations
            $currencyGroups = $vipBookings->groupBy('currency');

            $avgFees = [];
            $avgPlatformEarnings = [];

            foreach ($currencyGroups as $currency => $currencyBookings) {
                $avgFees[$currency] = $currencyBookings->avg('total_fee');
                $avgPlatformEarnings[$currency] = $currencyBookings->avg('platform_earnings');
            }

            $results[$isVip] = [
                'count' => $vipBookings->count(),
                'avg_fee_usd' => $currencyService->convertToUSD($avgFees),
                'avg_platform_earnings_usd' => $currencyService->convertToUSD($avgPlatformEarnings),
            ];
        }

        return $results;
    }
}
