<?php

namespace App\Livewire;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TopAffiliatesByEarningsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'topAffiliatesByEarnings';
    protected static ?string $heading = 'Top 10 Affiliates by Total Earnings';
    protected static ?int $contentHeight = 250;
    protected int|string|array $columnSpan = 4;

    public ?string $startMonth = null;
    public ?int $numberOfMonths = null;
    public ?string $region = null;
    public ?string $search = null;
    public bool $useMockData = false;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan;
    }

    public function getContentHeight(): ?int
    {
        return static::$contentHeight;
    }

    protected function getOptions(): array
    {
        if ($this->useMockData) {
            return [
                'chart' => [ 'type' => 'bar', 'height' => 250, 'toolbar' => ['show' => false] ],
                'series' => [ [ 'name' => 'Earnings', 'data' => [10,20,15,12,18,9,7,14,11,13] ] ],
                'xaxis' => [ 'categories' => ['Alpha','Beta ②','Cafe','Nino','Munchen','Tokyo 東京','Dubai دبي','Sao Paulo','Zurich','Krakow'] ],
                'dataLabels' => ['enabled' => false],
            ];
        }

        if (!$this->startMonth || !$this->numberOfMonths) {
            return [
                'chart' => ['type' => 'bar', 'height' => 250],
                'series' => [],
                'xaxis' => ['categories' => []],
                'noData' => ['text' => 'No data available']
            ];
        }

        try {
            $data = $this->getChartData();
        } catch (\Exception $e) {
            \Log::error('TopAffiliatesByEarningsChart error: ' . $e->getMessage());
            return [
                'chart' => ['type' => 'bar', 'height' => 250],
                'series' => [],
                'xaxis' => ['categories' => []],
                'noData' => ['text' => 'Error loading data']
            ];
        }

        if (empty($data['affiliateNames'])) {
            return [
                'chart' => ['type' => 'bar', 'height' => 250],
                'series' => [],
                'xaxis' => ['categories' => []],
                'noData' => ['text' => 'No data available']
            ];
        }

        $earningsInDollars = array_map(fn($cents) => round($cents / 100, 2), $data['earnings']);

        return [
            'chart' => [ 'type' => 'bar', 'height' => 250, 'toolbar' => ['show' => false] ],
            'colors' => ['#10B981'],
            'series' => [ [ 'name' => 'Earnings', 'data' => $earningsInDollars ] ],
            'xaxis' => [ 'categories' => $data['affiliateNames'], 'labels' => [ 'rotate' => -45, 'style' => [ 'fontSize' => '12px' ] ] ],
            'yaxis' => [ 'title' => [ 'text' => 'Earnings ($)' ] ],
            'plotOptions' => [ 'bar' => [ 'horizontal' => false ] ],
            'dataLabels' => [ 'enabled' => false ],
            'legend' => [ 'show' => true, 'position' => 'top', 'horizontalAlign' => 'right', 'markers' => ['width' => 12, 'height' => 12, 'radius' => 12] ],
            'tooltip' => [ 'shared' => false, 'intersect' => true ],
        ];
    }

    private function getChartData(): array
    {
        $timezone = auth()->user()->timezone ?? config('app.default_timezone');
        $startDate = \Carbon\Carbon::parse($this->startMonth . '-01', $timezone)->startOfDay()->setTimezone('UTC');
        $endDate = $startDate->copy()->addMonths($this->numberOfMonths)->subSecond();

        $sql = "
            SELECT
                c.id,
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                SUM(e.amount) as total_earnings
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

        $params = [$startDate->toDateTimeString(), $endDate->toDateTimeString()];

        if ($this->region) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM schedule_templates st
                JOIN venues v ON st.venue_id = v.id
                WHERE b.schedule_template_id = st.id AND v.region = ?
            )";
            $params[] = $this->region;
        }

        if ($this->search) {
            $searchLower = strtolower($this->search);
            $sql .= " AND (
                LOWER(u.first_name) LIKE ? OR
                LOWER(u.last_name) LIKE ? OR
                LOWER(u.email) LIKE ? OR
                LOWER(u.phone) LIKE ?
            )";
            $params[] = "%{$searchLower}%";
            $params[] = "%{$searchLower}%";
            $params[] = "%{$searchLower}%";
            $params[] = "%{$searchLower}%";
        }

        $sql .= "
            GROUP BY c.id, u.first_name, u.last_name
            HAVING SUM(e.amount) > 0
            ORDER BY total_earnings DESC
            LIMIT 10
        ";

        $results = \Illuminate\Support\Facades\DB::select($sql, $params);

        $affiliateNames = [];
        $earnings = [];

        foreach ($results as $result) {
            $affiliateNames[] = $result->user_name;
            $earnings[] = (int) ($result->total_earnings ?? 0);
        }

        return [ 'affiliateNames' => $affiliateNames, 'earnings' => $earnings ];
    }
}
