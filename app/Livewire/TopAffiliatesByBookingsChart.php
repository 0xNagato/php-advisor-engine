<?php

namespace App\Livewire;

use App\Enums\EarningType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TopAffiliatesByBookingsChart extends ApexChartWidget
{

    protected static ?string $chartId = 'topAffiliatesByBookings';
    protected static ?string $heading = 'Top 10 Affiliates by Total Bookings';
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
				'chart' => [ 'type' => 'bar', 'height' => 250, 'stacked' => true, 'toolbar' => [ 'show' => false ] ],
				'colors' => ['#6366F1', '#8B5CF6'],
				'series' => [
					[ 'name' => 'Direct Bookings', 'data' => [120, 90, 70, 110, 95, 80, 60, 40, 35, 25] ],
					[ 'name' => 'Referral Bookings', 'data' => [760, 720, 310, 160, 140, 120, 100, 90, 80, 70] ],
				],
				'xaxis' => [
					'categories' => ['PRIMA VIP Admin','Dee Amadi','Paul Onemnik','Paula Abrantes','Brian Bean','Robert Yeadon','John David Ibañez','Amelia Nunez','Luis Carreras','Laísla Ibiza'],
					'labels' => [ 'rotate' => -45, 'style' => [ 'fontSize' => '12px' ] ],
				],
				'yaxis' => [ 'title' => [ 'text' => 'Bookings' ] ],
				'plotOptions' => [ 'bar' => [ 'horizontal' => false ] ],
				'dataLabels' => [ 'enabled' => false ],
				'legend' => [ 'show' => true, 'position' => 'top' ],
				'tooltip' => [ 'shared' => true, 'intersect' => false ],
			];
		}

		if (!$this->startMonth || !$this->numberOfMonths) {
			return [
				'chart' => ['type' => 'bar', 'height' => 250, 'stacked' => true],
				'series' => [],
				'xaxis' => ['categories' => []],
				'noData' => ['text' => 'No data available']
			];
		}

        try {
            $data = $this->getChartData();
        } catch (\Exception $e) {
            \Log::error('TopAffiliatesByBookingsChart error: ' . $e->getMessage());
			return [
				'chart' => ['type' => 'bar', 'height' => 250, 'stacked' => true],
				'series' => [],
				'xaxis' => ['categories' => []],
				'noData' => ['text' => 'Error loading data']
			];
        }

		if (empty($data['affiliateNames'])) {
			return [
				'chart' => ['type' => 'bar', 'height' => 250, 'stacked' => true],
				'series' => [],
				'xaxis' => ['categories' => []],
				'noData' => ['text' => 'No data available']
			];
		}

		return [
			'chart' => [
				'type' => 'bar',
				'height' => 250,
				'stacked' => true,
				'toolbar' => [ 'show' => false ],
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
				'categories' => $data['affiliateNames'],
				'labels' => [ 'rotate' => -45, 'style' => [ 'fontSize' => '12px' ] ],
			],
			'yaxis' => [ 'title' => [ 'text' => 'Bookings' ] ],
			'plotOptions' => [ 'bar' => [ 'horizontal' => false ] ],
			'dataLabels' => [ 'enabled' => false ],
			'legend' => [ 'show' => true, 'position' => 'top', 'horizontalAlign' => 'right', 'markers' => ['width' => 12, 'height' => 12, 'radius' => 12] ],
			'tooltip' => [ 'shared' => true, 'intersect' => false ],
		];
    }

    private function getChartData(): array
    {
        $timezone = auth()->user()->timezone ?? config('app.default_timezone');
        $startDate = Carbon::parse($this->startMonth . '-01', $timezone)->startOfDay()->setTimezone('UTC');
        $endDate = $startDate->copy()->addMonths($this->numberOfMonths)->subSecond();

        // Use the same proven SQL pattern as the working chart and main table

        $sql = "
            SELECT
                c.id,
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                COUNT(DISTINCT CASE WHEN e.type IN ('concierge', 'concierge_bounty') THEN e.booking_id END) as direct_bookings,
                COUNT(DISTINCT CASE WHEN e.type IN ('concierge_referral_1', 'concierge_referral_2') THEN e.booking_id END) as referral_bookings,
                COUNT(DISTINCT e.booking_id) as total_bookings
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

        // Add region filter if specified
        if ($this->region) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM schedule_templates st
                JOIN venues v ON st.venue_id = v.id
                WHERE b.schedule_template_id = st.id AND v.region = ?
            )";
            $params[] = $this->region;
        }

        // Add search filter if specified
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
            HAVING COUNT(DISTINCT e.booking_id) > 0
            ORDER BY total_bookings DESC
            LIMIT 10
        ";

        $results = DB::select($sql, $params);

        $affiliateNames = [];
        $directBookings = [];
        $referralBookings = [];

        foreach ($results as $result) {
            $affiliateNames[] = $result->user_name;
            $directBookings[] = (int) ($result->direct_bookings ?? 0);
            $referralBookings[] = (int) ($result->referral_bookings ?? 0);
        }

        return [
            'affiliateNames' => $affiliateNames,
            'directBookings' => $directBookings,
            'referralBookings' => $referralBookings,
        ];
    }
}
