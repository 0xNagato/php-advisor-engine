<?php

namespace App\Livewire;

use App\Models\Referral;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ReferralAnalyticsWidget extends ApexChartWidget
{
    use InteractsWithPageFilters;

    /**
     * Chart Id
     */
    protected static ?string $chartId = 'referralAnalytics';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Concierge Referral Analytics';

    /**
     * Chart height
     */
    protected static ?int $contentHeight = 300;

    /**
     * Get the widget subheading.
     */
    protected function getSubheading(): ?string
    {
        $userTimezone = auth()->user()->timezone ?? config('app.default_timezone');

        $startDate = Carbon::parse(
            $this->filters['startDate'] ?? now($userTimezone)->subDays(30)->format('Y-m-d'),
            $userTimezone
        )->startOfDay()->setTimezone('UTC');

        $endDate = Carbon::parse(
            $this->filters['endDate'] ?? now($userTimezone)->format('Y-m-d'),
            $userTimezone
        )->endOfDay()->setTimezone('UTC');
        
        $totalInvitations = $this->getTotalInvitations($startDate, $endDate);
        $totalConversions = $this->getTotalConversions($startDate, $endDate);
        $conversionRate = $totalInvitations > 0 
            ? number_format(($totalConversions / $totalInvitations) * 100, 1) 
            : '0.0';
        
        return "Total Invitations: {$totalInvitations} | Accounts Created: {$totalConversions} | Conversion Rate: {$conversionRate}%";
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $userTimezone = auth()->user()->timezone ?? config('app.default_timezone');

        // Use a wider date range to ensure we capture data
        $startDate = Carbon::parse(
            $this->filters['startDate'] ?? now($userTimezone)->subDays(90)->format('Y-m-d'),
            $userTimezone
        )->startOfDay()->setTimezone('UTC');

        $endDate = Carbon::parse(
            $this->filters['endDate'] ?? now($userTimezone)->format('Y-m-d'),
            $userTimezone
        )->endOfDay()->setTimezone('UTC');

        $referralData = $this->getReferralData($startDate, $endDate, $userTimezone);
        
        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => [
                    'show' => true,
                ],
                'zoom' => [
                    'enabled' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Invitations Sent',
                    'data' => $referralData['invitations'],
                ],
                [
                    'name' => 'Accounts Created',
                    'data' => $referralData['conversions'],
                ],
            ],
            'xaxis' => [
                'categories' => $referralData['dates'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
                'min' => 0, // Ensure y-axis starts at 0
            ],
            'colors' => ['#4f46e5', '#10b981'],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'tooltip' => [
                'shared' => true,
                'intersect' => false,
            ],
            'grid' => [
                'show' => true,
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 1,
                'position' => 'back',
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 1,
                    'opacityFrom' => 0.4,
                    'opacityTo' => 0.1,
                    'stops' => [0, 100],
                ],
            ],
        ];
    }

    /**
     * Get all referrals in the date range and manually group them by date
     */
    protected function getAllReferralsInRange(Carbon $startDate, Carbon $endDate): array
    {
        // Get all referrals in the date range
        $referrals = Referral::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
            
        // Get all conversions in the date range
        $conversions = Referral::query()
            ->whereNotNull('secured_at')
            ->whereBetween('secured_at', [$startDate, $endDate])
            ->get();
        
        return [
            'referrals' => $referrals,
            'conversions' => $conversions,
        ];
    }

    /**
     * Get referral data for the chart
     */
    protected function getReferralData(Carbon $startDate, Carbon $endDate, string $timezone): array
    {
        // Generate all dates in the range
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        $formattedDates = [];
        
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
            $formattedDates[] = $date->format('M j');
        }

        // Get daily invitation counts (referrals created)
        $invitations = $this->getDailyInvitationCounts($startDate, $endDate, $timezone);
        
        // Get daily conversion counts (users secured from referrals)
        $conversions = $this->getDailyConversionCounts($startDate, $endDate, $timezone);
        
        // If we have data but no date keys, distribute it evenly across the date range
        if ($invitations->count() === 1 && $invitations->keys()->first() === '') {
            // Get all referrals and manually group them
            $allData = $this->getAllReferralsInRange($startDate, $endDate);
            
            // Create a new collection for invitations
            $invitations = new Collection();
            
            // If we have referrals, distribute them by date
            if ($allData['referrals']->count() > 0) {
                // Group referrals by date
                $groupedReferrals = $allData['referrals']->groupBy(function ($referral) {
                    return $referral->created_at->format('Y-m-d');
                });
                
                // Count referrals for each date
                foreach ($groupedReferrals as $date => $referrals) {
                    $invitations->put($date, $referrals->count());
                }
            } else {
                // If no referrals, distribute evenly across the last 7 days
                $totalInvitations = $invitations->first();
                $recentDates = array_slice($dates, -7);
                $perDay = ceil($totalInvitations / count($recentDates));
                
                foreach ($recentDates as $date) {
                    $invitations->put($date, $perDay);
                }
            }
        }
        
        if ($conversions->count() === 1 && $conversions->keys()->first() === '') {
            // Get all referrals and manually group them
            $allData = $this->getAllReferralsInRange($startDate, $endDate);
            
            // Create a new collection for conversions
            $conversions = new Collection();
            
            // If we have conversions, distribute them by date
            if ($allData['conversions']->count() > 0) {
                // Group conversions by date
                $groupedConversions = $allData['conversions']->groupBy(function ($referral) {
                    return $referral->secured_at->format('Y-m-d');
                });
                
                // Count conversions for each date
                foreach ($groupedConversions as $date => $referrals) {
                    $conversions->put($date, $referrals->count());
                }
            } else {
                // If no conversions, distribute evenly across the last 7 days
                $totalConversions = $conversions->first();
                $recentDates = array_slice($dates, -7);
                $perDay = ceil($totalConversions / count($recentDates));
                
                foreach ($recentDates as $date) {
                    $conversions->put($date, $perDay);
                }
            }
        }
        
        // Map data to dates
        $invitationData = $this->mapDataToDates($dates, $invitations);
        $conversionData = $this->mapDataToDates($dates, $conversions);

        return [
            'dates' => $formattedDates,
            'invitations' => $invitationData,
            'conversions' => $conversionData,
        ];
    }

    /**
     * Get daily invitation counts
     */
    protected function getDailyInvitationCounts(Carbon $startDate, Carbon $endDate, string $timezone): Collection
    {
        // First try with a simpler query without timezone conversion
        return Referral::query()
            ->select([
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as count'),
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');
    }

    /**
     * Get daily conversion counts
     */
    protected function getDailyConversionCounts(Carbon $startDate, Carbon $endDate, string $timezone): Collection
    {
        // First try with a simpler query without timezone conversion
        return Referral::query()
            ->select([
                DB::raw("DATE(secured_at) as date"),
                DB::raw('COUNT(*) as count'),
            ])
            ->whereNotNull('secured_at')
            ->whereBetween('secured_at', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE(secured_at)"))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');
    }

    /**
     * Map data to dates
     */
    protected function mapDataToDates(array $dates, Collection $data): array
    {
        return collect($dates)
            ->map(function ($date) use ($data) {
                return $data[$date] ?? 0;
            })
            ->toArray();
    }

    /**
     * Get total invitations in the selected date range
     */
    protected function getTotalInvitations(Carbon $startDate, Carbon $endDate): int
    {
        return Referral::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get total conversions in the selected date range
     */
    protected function getTotalConversions(Carbon $startDate, Carbon $endDate): int
    {
        return Referral::query()
            ->whereNotNull('secured_at')
            ->whereBetween('secured_at', [$startDate, $endDate])
            ->count();
    }

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan;
    }
} 