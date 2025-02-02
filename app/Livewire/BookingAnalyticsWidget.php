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
        ];
    }

    protected function getTopVenues(Carbon $startDate, Carbon $endDate): array
    {
        return Booking::query()
            ->select('v.name', DB::raw('COUNT(*) as booking_count'))
            ->join('schedule_templates as st', 'bookings.schedule_template_id', '=', 'st.id')
            ->join('venues as v', 'st.venue_id', '=', 'v.id')
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('v.id', 'v.name')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    protected function getPopularTimes(Carbon $startDate, Carbon $endDate): array
    {
        return Booking::query()
            ->select(DB::raw('TIME_FORMAT(bookings.booking_at, "%l:%i %p") as time_slot'), DB::raw('COUNT(*) as booking_count'))
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('time_slot')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    protected function getPartySizes(Carbon $startDate, Carbon $endDate): array
    {
        return Booking::query()
            ->select('bookings.guest_count', DB::raw('COUNT(*) as count'))
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('bookings.guest_count')
            ->orderBy('bookings.guest_count')
            ->get()
            ->toArray();
    }

    protected function getPrimeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $currencyService = app(CurrencyConversionService::class);

        return Booking::query()
            ->select(
                'bookings.is_prime',
                'bookings.currency',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(bookings.total_fee) as avg_fee')
            )
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('bookings.is_prime', 'bookings.currency')
            ->get()
            ->groupBy('is_prime')
            ->map(function ($group) use ($currencyService) {
                $totalCount = $group->sum('count');
                $avgFees = $group->mapWithKeys(fn ($item) => [$item->currency => $item->avg_fee])->toArray();
                $avgFeeUSD = $currencyService->convertToUSD($avgFees);

                return [
                    'count' => $totalCount,
                    'avg_fee_usd' => $avgFeeUSD,
                ];
            })
            ->toArray();
    }

    protected function getLeadTimeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        return Booking::query()
            ->select(
                DB::raw('
                    CASE
                        WHEN DATEDIFF(bookings.booking_at, bookings.created_at) = 0 THEN "Same day"
                        WHEN DATEDIFF(bookings.booking_at, bookings.created_at) = 1 THEN "Next day"
                        WHEN DATEDIFF(bookings.booking_at, bookings.created_at) <= 7 THEN "2-7 days"
                        ELSE "8+ days"
                    END as lead_time
                '),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('lead_time')
            ->orderByRaw('
                CASE lead_time
                    WHEN "Same day" THEN 1
                    WHEN "Next day" THEN 2
                    WHEN "2-7 days" THEN 3
                    ELSE 4
                END
            ')
            ->get()
            ->toArray();
    }

    protected function getDayAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        return Booking::query()
            ->select(
                DB::raw('DAYNAME(bookings.booking_at) as day_name'),
                DB::raw('COUNT(*) as booking_count')
            )
            ->whereBetween('bookings.booking_at', [$startDate, $endDate])
            ->whereIn('bookings.status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
            ->groupBy('day_name')
            ->orderByRaw('FIELD(day_name, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")')
            ->get()
            ->toArray();
    }
}
