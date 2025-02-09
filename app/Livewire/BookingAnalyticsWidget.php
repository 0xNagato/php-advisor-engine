<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\CurrencyConversionService;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class BookingAnalyticsWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'livewire.booking-analytics';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    public string $dateType = 'booking';

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
        $startDate = Carbon::parse($this->filters['startDate'] ?? now()->subDays(30))->startOfDay();
        $endDate = Carbon::parse($this->filters['endDate'] ?? now())->endOfDay();

        return [
            'topVenues' => $this->getTopVenues($startDate, $endDate),
            'popularTimes' => $this->getPopularTimes($startDate, $endDate),
            'partySizes' => $this->getPartySizes($startDate, $endDate),
            'primeAnalysis' => $this->getPrimeAnalysis($startDate, $endDate),
            'leadTimeAnalysis' => $this->getLeadTimeAnalysis($startDate, $endDate),
            'dayAnalysis' => $this->getDayAnalysis($startDate, $endDate),
            'calendarDayAnalysis' => $this->getCalendarDayAnalysis($startDate, $endDate),
            'statusAnalysis' => $this->getStatusAnalysis($startDate, $endDate),
            'topConcierges' => $this->getTopConcierges($startDate, $endDate),
            'bookingTypeAnalysis' => $this->getBookingTypeAnalysis($startDate, $endDate),
        ];
    }

    protected function getDateColumn(): string
    {
        return $this->getShowBookingTimeProperty() ? 'bookings.booking_at' : 'bookings.created_at';
    }

    protected function getTopVenues(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        // Get total bookings first
        $total = Booking::query()
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->count();

        $results = Booking::query()
            ->select('v.name', DB::raw('COUNT(*) as booking_count'))
            ->join('schedule_templates as st', 'bookings.schedule_template_id', '=', 'st.id')
            ->join('venues as v', 'st.venue_id', '=', 'v.id')
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('v.id', 'v.name')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get();

        return $results->map(fn ($item) => [
            'name' => $item->name,
            'booking_count' => $item->booking_count,
            'percentage' => $total > 0 ? round(($item->booking_count / $total) * 100, 1) : 0,
        ])->toArray();
    }

    protected function getPopularTimes(Carbon $startDate, Carbon $endDate): array
    {
        // Get total bookings first
        $total = Booking::query()
            ->whereBetween($this->getDateColumn(), [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->count();

        $results = Booking::query()
            ->select(
                DB::raw("TIME_FORMAT(bookings.booking_at, '%l:%i %p') as time_slot"),
                DB::raw('COUNT(*) as booking_count')
            )
            ->whereBetween($this->getDateColumn(), [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('time_slot')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get();

        return $results->map(fn ($item) => [
            'time_slot' => $item->time_slot,
            'booking_count' => $item->booking_count,
            'percentage' => $total > 0 ? round(($item->booking_count / $total) * 100, 1) : 0,
        ])->toArray();
    }

    protected function getPartySizes(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        $results = Booking::query()
            ->select('bookings.guest_count', DB::raw('COUNT(*) as count'))
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('bookings.guest_count')
            ->orderBy('bookings.guest_count')
            ->get();

        $total = $results->sum('count');

        return $results->map(fn ($item) => [
            'guest_count' => $item->guest_count,
            'count' => $item->count,
            'percentage' => $total > 0 ? round(($item->count / $total) * 100, 1) : 0,
        ])->toArray();
    }

    protected function getPrimeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();
        $currencyService = app(CurrencyConversionService::class);

        return Booking::query()
            ->select(
                'bookings.is_prime',
                'bookings.currency',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(bookings.total_fee) as avg_fee'),
                DB::raw('AVG(bookings.platform_earnings) as avg_platform_earnings')
            )
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('bookings.is_prime', 'bookings.currency')
            ->get()
            ->groupBy('is_prime')
            ->map(function ($group) use ($currencyService) {
                $totalCount = $group->sum('count');
                $avgFees = $group->mapWithKeys(fn ($item) => [$item->currency => $item->avg_fee])->toArray();
                $avgPlatformEarnings = $group->mapWithKeys(fn ($item) => [$item->currency => $item->avg_platform_earnings])->toArray();

                return [
                    'count' => $totalCount,
                    'avg_fee_usd' => $currencyService->convertToUSD($avgFees),
                    'avg_platform_earnings_usd' => $currencyService->convertToUSD($avgPlatformEarnings),
                ];
            })
            ->toArray();
    }

    protected function getLeadTimeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        $results = Booking::query()
            ->select(
                DB::raw('
                    CASE
                        WHEN DATEDIFF(bookings.booking_at, bookings.created_at) = 0 THEN "Same day"
                        WHEN DATEDIFF(bookings.booking_at, bookings.created_at) = 1 THEN "Next day"
                        WHEN DATEDIFF(bookings.booking_at, bookings.created_at) <= 7 THEN "2-7 days"
                        WHEN DATEDIFF(bookings.booking_at, bookings.created_at) <= 14 THEN "8-14 days"
                        WHEN DATEDIFF(bookings.booking_at, bookings.created_at) <= 30 THEN "15-30 days"
                        ELSE "30+ days"
                    END as lead_time
                '),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('lead_time')
            ->orderByRaw('
                CASE lead_time
                    WHEN "Same day" THEN 1
                    WHEN "Next day" THEN 2
                    WHEN "2-7 days" THEN 3
                    WHEN "8-14 days" THEN 4
                    WHEN "15-30 days" THEN 5
                    ELSE 6
                END
            ')
            ->get();

        $total = $results->sum('count');

        return $results->map(fn ($item) => [
            'lead_time' => $item->lead_time,
            'count' => $item->count,
            'percentage' => $total > 0 ? round(($item->count / $total) * 100, 1) : 0,
        ])->toArray();
    }

    protected function getDayAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        $results = Booking::query()
            ->select(
                DB::raw("DAYNAME($dateColumn) as day_name"),
                DB::raw('COUNT(*) as booking_count')
            )
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('day_name')
            ->orderByRaw('FIELD(day_name, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")')
            ->get();

        $total = $results->sum('booking_count');

        return $results->map(fn ($item) => [
            'day_name' => $item->day_name,
            'booking_count' => $item->booking_count,
            'percentage' => $total > 0 ? round(($item->booking_count / $total) * 100, 1) : 0,
        ])->toArray();
    }

    protected function getCalendarDayAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        return Booking::query()
            ->select(
                DB::raw("DATE($dateColumn) as calendar_date"),
                DB::raw('COUNT(*) as booking_count'),
                DB::raw("DAYNAME($dateColumn) as day_name")
            )
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('calendar_date', 'day_name')
            ->orderBy('calendar_date')
            ->get()
            ->map(fn ($item) => [
                'date' => Carbon::parse($item->calendar_date)->format('M j'),
                'day_name' => $item->day_name,
                'booking_count' => $item->booking_count,
            ])
            ->toArray();
    }

    protected function getStatusAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();

        $query = Booking::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->groupBy('status');

        $results = $query->get();
        $total = $results->sum('count');

        return $results->map(fn ($item) => [
            'status' => $item->status->label(),
            'count' => $item->count,
            'percentage' => $total > 0 ? round(($item->count / $total) * 100, 1) : 0,
        ])->toArray();
    }

    protected function getTopConcierges(Carbon $startDate, Carbon $endDate): array
    {
        $currencyService = app(CurrencyConversionService::class);

        // Get total bookings first for percentage calculation
        $total = Booking::query()
            ->whereBetween($this->getDateColumn(), [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->count();

        return Booking::query()
            ->select([
                'concierges.id',
                'concierges.hotel_name',
                'users.first_name',
                'users.last_name',
                'bookings.currency',
                DB::raw('COUNT(*) as booking_count'),
                DB::raw('AVG(bookings.total_fee) as avg_fee'),
                DB::raw('AVG(bookings.platform_earnings) as avg_platform_earnings'),
            ])
            ->join('concierges', 'bookings.concierge_id', '=', 'concierges.id')
            ->join('users', 'concierges.user_id', '=', 'users.id')
            ->whereBetween($this->getDateColumn(), [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('concierges.id', 'concierges.hotel_name', 'users.first_name', 'users.last_name', 'bookings.currency')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->groupBy('id')
            ->map(function ($group) use ($currencyService, $total) {
                $first = $group->first();
                $totalCount = $group->sum('booking_count');
                $avgFees = $group->mapWithKeys(fn ($item) => [$item->currency => $item->avg_fee])->toArray();
                $avgPlatformEarnings = $group->mapWithKeys(fn ($item) => [$item->currency => $item->avg_platform_earnings])->toArray();

                return [
                    'name' => $first->first_name.' '.$first->last_name,
                    'booking_count' => $totalCount,
                    'percentage' => $total > 0 ? round(($totalCount / $total) * 100, 1) : 0,
                    'avg_fee_usd' => $currencyService->convertToUSD($avgFees),
                    'avg_platform_earnings_usd' => $currencyService->convertToUSD($avgPlatformEarnings),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getBookingTypeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->getDateColumn();
        $currencyService = app(CurrencyConversionService::class);

        $results = Booking::query()
            ->select(
                DB::raw('CASE WHEN vip_code_id IS NOT NULL THEN true ELSE false END as is_vip'),
                'bookings.currency',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(bookings.total_fee) as avg_fee'),
                DB::raw('AVG(bookings.platform_earnings) as avg_platform_earnings')
            )
            ->whereBetween($dateColumn, [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('is_vip', 'bookings.currency')
            ->get()
            ->groupBy('is_vip');

        return $results->map(function ($group) use ($currencyService) {
            $totalCount = $group->sum('count');
            $avgFees = $group->mapWithKeys(fn ($item) => [$item->currency => $item->avg_fee])->toArray();
            $avgPlatformEarnings = $group->mapWithKeys(fn ($item) => [$item->currency => $item->avg_platform_earnings])->toArray();

            return [
                'count' => $totalCount,
                'avg_fee_usd' => $currencyService->convertToUSD($avgFees),
                'avg_platform_earnings_usd' => $currencyService->convertToUSD($avgPlatformEarnings),
            ];
        })->toArray();
    }
}
