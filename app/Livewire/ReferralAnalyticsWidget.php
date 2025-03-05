<?php

namespace App\Livewire;

use App\Models\Referral;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class ReferralAnalyticsWidget extends DateResponsiveApexChartWidget
{
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

    public int|string|array $columnSpan;

    protected static ?string $pollingInterval = null;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan ?? 'full';
    }

    /**
     * Listen for dateRangeUpdated event and update the widget
     */
    #[On('dateRangeUpdated')]
    public function updateDateRange(string $startDate, string $endDate): void
    {
        // Update the filters
        $this->filters['startDate'] = $startDate;
        $this->filters['endDate'] = $endDate;
        // Reset readyToLoad to refresh chart data
        $this->readyToLoad = true;

        // Force chart update with clean options
        $this->updateOptions();

        // Dispatch an event to ensure the chart is refreshed in the DOM
        $this->dispatch('chartUpdated', chartId: $this->getChartId());
    }

    /**
     * Get the widget subheading.
     */
    protected function getSubheading(): string|Htmlable|null
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        $totalInvitations = $this->getTotalInvitations($startDate, $endDate);
        $totalConversions = $this->getTotalConversions($startDate, $endDate);
        $conversionRate = $totalInvitations > 0
            ? number_format(($totalConversions / $totalInvitations) * 100, 1)
            : '0.0';

        return new HtmlString(
            '<span class="text-xs sm:text-sm">
                Total Invitations: '.$totalInvitations.' |
                Accounts Created: '.$totalConversions.' |
                Conversion Rate: '.$conversionRate.'%
            </span>'
        );
    }

    /**
     * Get the chart ID for the current instance
     */
    protected function getChartId(): string
    {
        return static::$chartId ?? 'referralAnalytics';
    }

    /**
     * Add custom JavaScript to handle mobile responsiveness and prevent duplicate charts
     */
    protected function extraJsOptions(): ?RawJs
    {
        // Get the ID directly from the static property to ensure consistency
        $chartId = static::$chartId;

        return RawJs::make(<<<JS
        {
            chart: {
                events: {
                    mounted: function(chart) {
                        // Handle duplicate charts
                        setTimeout(function() {
                            const chartId = "{$chartId}-chart";
                            const charts = document.querySelectorAll('[id="' + chartId + '"]');

                            if (charts.length > 1) {
                                console.log('Found duplicate charts, cleaning up...');
                                // Keep only the last chart (most recently created)
                                const keepChart = charts[charts.length - 1];

                                charts.forEach(function(el) {
                                    if (el !== keepChart && el.parentNode) {
                                        el.parentNode.removeChild(el);
                                    }
                                });
                            }
                            
                            // Add slight chart offset to prevent first label cutoff
                            chart.updateOptions({
                                chart: {
                                    offsetX: 5
                                }
                            });
                        }, 100);
                    }
                }
            },
            responsive: [
                {
                    breakpoint: 768,
                    options: {
                        xaxis: {
                            tickAmount: 8,
                            labels: {
                                rotate: -45,
                                offsetY: 5
                            }
                        },
                        chart: {
                            offsetX: 5,
                            offsetY: 0
                        },
                        legend: {
                            position: 'bottom',
                            horizontalAlign: 'center'
                        }
                    }
                },
                {
                    breakpoint: 480,
                    options: {
                        xaxis: {
                            tickAmount: 5,
                            labels: {
                                rotate: -45,
                                offsetY: 5,
                                offsetX: 0,
                                style: {
                                    fontSize: '10px'
                                }
                            }
                        },
                        chart: {
                            width: '95%',
                            offsetX: 0
                        },
                        margin: {
                            right: 15
                        }
                    }
                }
            ]
        }
        JS);
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     */
    protected function getOptions(): array
    {
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();
        $userTimezone = auth()->user()->timezone ?? config('app.default_timezone');

        $referralData = $this->getReferralData($startDate, $endDate, $userTimezone);

        // Calculate appropriate number of ticks based on date range
        $dayCount = $startDate->diffInDays($endDate) + 1;
        $tickAmount = min(15, max(5, intval($dayCount / 4)));

        // Merge our specific options with the default options from the parent class
        return array_merge($this->getDefaultOptions(), [
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
                    'rotate' => -45,
                    'offsetX' => 5,
                ],
                'tickAmount' => $tickAmount,
                'tickPlacement' => 'on',
            ],
        ]);
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
     * Get referral data for chart
     */
    protected function getReferralData(Carbon $startDate, Carbon $endDate, string $timezone): array
    {
        // Generate a date range for the chart
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        // Create arrays for dates (both for display and for mapping)
        $dateFormat = [];         // Display format
        $dateKeys = [];           // SQL format (Y-m-d) for matching with query results

        // Determine if year should be shown (when spanning multiple years)
        $spanMultipleYears = $startDate->year !== $endDate->year;
        $dateFormatPattern = $spanMultipleYears ? 'M j, Y' : 'M j';

        foreach ($period as $date) {
            $localDate = $date->copy()->setTimezone($timezone);
            $dateKeys[] = $localDate->format('Y-m-d');
            $dateFormat[] = $localDate->format($dateFormatPattern);
        }

        // Get invitation counts by date
        $invitationsByDate = $this->getInvitationsByDate($startDate, $endDate, $timezone);

        // Get conversion counts by date
        $conversionsByDate = $this->getConversionsByDate($startDate, $endDate, $timezone);

        // Handle edge case where data doesn't have proper date keys
        if (count($invitationsByDate) === 1 && ! isset($invitationsByDate[$dateKeys[0]]) && ! isset($invitationsByDate[$dateKeys[count($dateKeys) - 1]])) {
            // Get all referrals and manually group them
            $allData = $this->getAllReferralsInRange($startDate, $endDate);

            // Create a new array for invitations
            $invitationsByDate = [];

            // If we have referrals, distribute them by date
            if ($allData['referrals']->count() > 0) {
                // Group referrals by date
                $groupedReferrals = $allData['referrals']->groupBy(fn ($referral) => $referral->created_at->format('Y-m-d'));

                // Count referrals for each date
                foreach ($groupedReferrals as $date => $referrals) {
                    $invitationsByDate[$date] = $referrals->count();
                }
            }
        }

        // Same for conversions
        if (count($conversionsByDate) === 1 && ! isset($conversionsByDate[$dateKeys[0]]) && ! isset($conversionsByDate[$dateKeys[count($dateKeys) - 1]])) {
            // Get all referrals and manually group them
            $allData = $this->getAllReferralsInRange($startDate, $endDate);

            // Create a new array for conversions
            $conversionsByDate = [];

            // If we have conversions, distribute them by date
            if ($allData['conversions']->count() > 0) {
                // Group conversions by date
                $groupedConversions = $allData['conversions']->groupBy(fn ($referral) => $referral->secured_at->format('Y-m-d'));

                // Count conversions for each date
                foreach ($groupedConversions as $date => $referrals) {
                    $conversionsByDate[$date] = $referrals->count();
                }
            }
        }

        // Fill in the data arrays with counts for each date in the period
        $invitations = [];
        $conversions = [];

        // Map data to each date in the period
        foreach ($dateKeys as $index => $sqlDate) {
            $invitations[] = $invitationsByDate[$sqlDate] ?? 0;
            $conversions[] = $conversionsByDate[$sqlDate] ?? 0;
        }

        return [
            'dates' => $dateFormat,
            'invitations' => $invitations,
            'conversions' => $conversions,
        ];
    }

    /**
     * Get invitation counts by date
     */
    protected function getInvitationsByDate(Carbon $startDate, Carbon $endDate, string $timezone): array
    {
        // Use the original working query format (without timezone conversion)
        return Referral::query()
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Get conversion counts by date
     */
    protected function getConversionsByDate(Carbon $startDate, Carbon $endDate, string $timezone): array
    {
        // Use the original working query format (without timezone conversion)
        return Referral::query()
            ->select([
                DB::raw('DATE(secured_at) as date'),
                DB::raw('COUNT(*) as count'),
            ])
            ->whereNotNull('secured_at')
            ->whereBetween('secured_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(secured_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Get total invitations in the date range
     */
    protected function getTotalInvitations(Carbon $startDate, Carbon $endDate): int
    {
        return Referral::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get total conversions in the date range
     */
    protected function getTotalConversions(Carbon $startDate, Carbon $endDate): int
    {
        return Referral::query()
            ->whereNotNull('secured_at')
            ->whereBetween('secured_at', [$startDate, $endDate])
            ->count();
    }
}
