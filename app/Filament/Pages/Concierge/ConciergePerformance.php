<?php

namespace App\Filament\Pages\Concierge;

use App\Enums\BookingStatus;
use App\Models\Concierge;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ConciergePerformance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.concierge.concierge-performance';

    protected static ?string $navigationLabel = 'Concierge Performance';

    protected static ?string $title = 'Concierge Performance';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'Advanced Tools';

    public array $weekRanges = [];

    public array $conciergeData = [];

    protected string $cacheKey = 'concierge_performance_data_6weeks';

    protected int $cacheTtlMinutes = 10;

    public function getTitle(): string
    {
        if (empty($this->weekRanges)) {
            return static::$title;
        }

        // Get the start date of the earliest week and end date of the latest week
        $earliestWeek = end($this->weekRanges);
        $latestWeek = reset($this->weekRanges);

        $startDate = $earliestWeek['start']->format('M j, Y');
        $endDate = $latestWeek['end']->format('M j, Y');

        return static::$title.' ('.$startDate.' - '.$endDate.')';
    }

    public function mount(): void
    {
        // Don't clear the cache on every page load
        $this->weekRanges = $this->getWeekRanges();
        $this->conciergeData = $this->getConciergePerformanceData();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasActiveRole(['super_admin', 'admin']);
    }

    public function getWeekRanges(): array
    {
        $weekRanges = [];
        $endDate = Carbon::now()->endOfWeek();

        for ($i = 0; $i < 6; $i++) {
            $startDate = $endDate->copy()->subDays(6);
            $weekRanges[] = [
                'start' => $startDate->copy(),
                'end' => $endDate->copy(),
                'label' => $startDate->format('M j').' - '.$endDate->format('M j'),
            ];
            $endDate = $startDate->copy()->subDay();
        }

        return $weekRanges;
    }

    public function getConciergePerformanceData(): array
    {
        // Get data from cache if available, otherwise calculate and cache for 10 minutes
        return Cache::remember($this->cacheKey, $this->cacheTtlMinutes * 60, function () {
            $conciergeData = [];
            $concierges = Concierge::with(['user'])->whereHas('user')->get();
            $weekRanges = $this->getWeekRanges(); // Ensure we use the updated week ranges

            foreach ($concierges as $concierge) {
                $conciergeRow = [
                    'id' => $concierge->id,
                    'user_id' => $concierge->user_id ?? null,
                    'name' => $concierge->user->name ?? 'Unknown',
                    'hotel' => $concierge->hotel_name ?? '-',
                    'email' => $concierge->user->email ?? '-',
                    'phone' => $concierge->user?->localFormattedPhone ?? $concierge->user?->phone ?? '-',
                    'totalBookings' => 0,
                    'weeklyBookings' => [],
                ];

                // Calculate bookings for each week (strictly using the current week ranges)
                foreach ($weekRanges as $index => $weekRange) {
                    $bookingCount = $concierge->bookings()
                        ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::VENUE_CONFIRMED])
                        ->whereBetween('created_at', [
                            $weekRange['start']->startOfDay(),
                            $weekRange['end']->endOfDay(),
                        ])
                        ->count();

                    $conciergeRow['weeklyBookings'][$index] = $bookingCount;
                    $conciergeRow['totalBookings'] += $bookingCount;
                }

                // Only include concierges with at least one booking
                if ($conciergeRow['totalBookings'] > 0) {
                    $conciergeData[] = $conciergeRow;
                }
            }

            // Sort by total bookings descending
            usort($conciergeData, function ($a, $b) {
                return $b['totalBookings'] <=> $a['totalBookings'];
            });

            return $conciergeData;
        });
    }

    // Method to manually clear the cache (for development)
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}
