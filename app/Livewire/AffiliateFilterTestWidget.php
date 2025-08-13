<?php

namespace App\Livewire;

use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AffiliateFilterTestWidget extends Widget
{
    protected static string $view = 'livewire.affiliate-filter-test-widget';

    protected int|string|array $columnSpan = 1;

    public ?string $startMonth = null;
    public ?int $numberOfMonths = null;
    public ?string $region = null;
    public ?string $search = null;
    public string $lastUpdated = '';
    public array $queryResults = [];
    public float $queryDuration = 0;

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
        $this->lastUpdated = now()->format('H:i:s.u');
        
        // Run the same database query as ApexCharts widget
        $this->runSameQuery();
    }

    private function runSameQuery()
    {
        if (!$this->startMonth || !$this->numberOfMonths) {
            $this->queryResults = [];
            $this->queryDuration = 0;
            return;
        }

        $startTime = microtime(true);
        
        try {
            $timezone = auth()->user()->timezone ?? config('app.default_timezone');
            $startDate = Carbon::parse($this->startMonth.'-01', $timezone)->startOfDay()->setTimezone('UTC');
            
            $this->queryResults = [];
            $currentDate = Carbon::parse($this->startMonth.'-01', $timezone);

            for ($i = 0; $i < $this->numberOfMonths; $i++) {
                $monthStart = $currentDate->copy()->startOfDay()->setTimezone('UTC');
                $monthEnd = $currentDate->copy()->endOfMonth()->endOfDay()->setTimezone('UTC');
                $monthLabel = $currentDate->format('M Y');

                // Run exact same query as ApexCharts widget
                $monthData = $this->getMonthlyBookingData($monthStart, $monthEnd, $this->region, $this->search);
                
                $this->queryResults[] = [
                    'month' => $monthLabel,
                    'direct' => $monthData['direct'],
                    'referral' => $monthData['referral'],
                ];

                $currentDate->addMonth();
            }
        } catch (\Exception $e) {
            $this->queryResults = [['error' => $e->getMessage()]];
        }
        
        $this->queryDuration = round((microtime(true) - $startTime) * 1000, 2);
    }

    private function getMonthlyBookingData(Carbon $monthStart, Carbon $monthEnd, ?string $region = null, ?string $search = null): array
    {
        // Exact same SQL as AffiliateMonthlyTrendsChart
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

        if ($region) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM schedule_templates st
                JOIN venues v ON st.venue_id = v.id
                WHERE b.schedule_template_id = st.id AND v.region = ?
            )';
            $params[] = $region;
        }

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