<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Livewire\Attributes\On;

class AffiliateMonthlyTrendsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'affiliateMonthlyTrends';

    protected static ?string $heading = 'Direct vs Referral Bookings';

    protected static ?int $contentHeight = 250;

    protected int|string|array $columnSpan = 4;

    public ?string $startMonth = null;
    public ?int $numberOfMonths = null;
    public ?string $region = null;
    public ?string $search = null;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan;
    }

    #[On('filtersUpdated')]
    public function updateFilters($data)
    {
        $this->startMonth = $data['startMonth'] ?? null;
        $this->numberOfMonths = $data['numberOfMonths'] ?? null;
        $this->region = $data['region'] ?? null;
        $this->search = $data['search'] ?? null;
    }

    protected function getOptions(): array
    {
        if (! $this->startMonth || ! $this->numberOfMonths) {
            return [
                'chart' => ['type' => 'line', 'height' => 300],
                'series' => [],
                'xaxis' => ['categories' => []],
                'noData' => ['text' => 'No data available'],
            ];
        }

        try {
            $data = $this->getChartData();
        } catch (\Exception $e) {
            \Log::error('AffiliateMonthlyTrendsChart error: '.$e->getMessage());

            return [
                'chart' => ['type' => 'line', 'height' => 300],
                'series' => [],
                'xaxis' => ['categories' => []],
                'noData' => ['text' => 'Error loading data'],
            ];
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 250,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'colors' => ['#7C3AED', '#10B981'],
            'series' => [
                [
                    'name' => 'Direct Bookings',
                    'data' => $data['directBookings'],
                ],
                [
                    'name' => 'Referral Bookings',
                    'data' => $data['referralBookings'],
                ],
            ],
            'xaxis' => [
                'categories' => $data['months'],
                'title' => [
                    'text' => 'Month',
                ],
            ],
            'yaxis' => [
                'title' => [
                    'text' => 'Bookings',
                ],
            ],
            'stroke' => [
                'width' => 3,
                'curve' => 'smooth',
            ],
            'markers' => [
                'size' => 6,
                'hover' => [
                    'size' => 8,
                ],
            ],
            'legend' => [
                'show' => true,
                'position' => 'top',
                'horizontalAlign' => 'right',
                'markers' => [
                    'width' => 12,
                    'height' => 12,
                    'radius' => 12,
                ],
            ],
            'grid' => [
                'show' => true,
                'borderColor' => '#E5E7EB',
            ],
            'tooltip' => [
                'shared' => true,
                'intersect' => false,
            ],
        ];
    }

    private function getChartData(): array
    {
        $timezone = auth()->user()->timezone ?? config('app.default_timezone');
        $startDate = Carbon::parse($this->startMonth.'-01', $timezone)->startOfDay()->setTimezone('UTC');
        $endDate = $startDate->copy()->addMonths($this->numberOfMonths)->subSecond();

        // Generate monthly data
        $months = [];
        $directBookings = [];
        $referralBookings = [];

        $currentDate = Carbon::parse($this->startMonth.'-01', $timezone);

        for ($i = 0; $i < $this->numberOfMonths; $i++) {
            $monthStart = $currentDate->copy()->startOfDay()->setTimezone('UTC');
            $monthEnd = $currentDate->copy()->endOfMonth()->endOfDay()->setTimezone('UTC');
            $monthLabel = $currentDate->format('M Y');

            // Get booking counts for this month
            $monthData = $this->getMonthlyBookingData($monthStart, $monthEnd, $this->region, $this->search);

            $months[] = $monthLabel;
            $directBookings[] = $monthData['direct'];
            $referralBookings[] = $monthData['referral'];

            $currentDate->addMonth();
        }

        return [
            'months' => $months,
            'directBookings' => $directBookings,
            'referralBookings' => $referralBookings,
        ];
    }

    private function getMonthlyBookingData(Carbon $monthStart, Carbon $monthEnd, ?string $region = null, ?string $search = null): array
    {
        // Use raw SQL query for better control and debugging
        $sql = "
            SELECT
                COUNT(DISTINCT CASE WHEN e.type IN ('concierge', 'concierge_bounty') THEN e.booking_id END) as direct_bookings,
                COUNT(DISTINCT CASE WHEN e.type IN ('concierge_referral_1', 'concierge_referral_2') THEN e.booking_id END) as referral_bookings
            FROM concierges c
            JOIN users u ON u.id = c.user_id
            JOIN earnings e ON e.user_id = u.id
            JOIN bookings b ON e.booking_id = b.id
            WHERE b.confirmed_at IS NOT NULL
            AND b.confirmed_at >= ?
            AND b.confirmed_at <= ?
            AND b.status IN ('confirmed', 'venue_confirmed', 'partially_refunded', 'no_show', 'cancelled')
            AND e.type IN ('concierge', 'concierge_referral_1', 'concierge_referral_2', 'concierge_bounty')
        ";

        $params = [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()];

        // Add region filter if specified
        if ($region) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM schedule_templates st
                JOIN venues v ON st.venue_id = v.id
                WHERE b.schedule_template_id = st.id AND v.region = ?
            )';
            $params[] = $region;
        }

        // Add search filter if specified
        if ($search) {
            $searchLower = strtolower($search);
            $sql .= ' AND (
                LOWER(u.first_name) LIKE ? OR
                LOWER(u.last_name) LIKE ? OR
                LOWER(u.email) LIKE ? OR
                LOWER(u.phone) LIKE ?
            )';
            $params[] = "%{$searchLower}%";
            $params[] = "%{$searchLower}%";
            $params[] = "%{$searchLower}%";
            $params[] = "%{$searchLower}%";
        }

        $result = DB::selectOne($sql, $params);

        return [
            'direct' => (int) ($result->direct_bookings ?? 0),
            'referral' => (int) ($result->referral_bookings ?? 0),
        ];
    }
}
