<?php

namespace App\Livewire;

use App\Models\Referral;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Support\RawJs;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Livewire\Attributes\On;

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

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan;
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
        
        // Reset readyToLoad so options are re-fetched cleanly
        if (property_exists($this, 'readyToLoad')) {
            $this->readyToLoad = true;
        }
        
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
        
        return new HtmlString(
            '<span class="text-xs sm:text-sm">
                Total Invitations: ' . $totalInvitations . ' | 
                Accounts Created: ' . $totalConversions . ' | 
                Conversion Rate: ' . $conversionRate . '%
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
    protected function extraJsOptions(): ?\Filament\Support\RawJs
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
                            tickAmount: 4,
                            labels: {
                                rotate: -90,
                                offsetY: 5,
                                style: {
                                    fontSize: '10px'
                                }
                            }
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
        
        // Add a timestamp to ensure the chart refreshes with new data
        $timestamp = time();
        
        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
                'zoom' => [
                    'enabled' => false,
                ],
                'background' => 'transparent',
                'redrawOnWindowResize' => true,
                'animations' => [
                    'enabled' => false, // Disable animations to avoid issues during updates
                ],
                // Add a cache-busting parameter to the ID
                'id' => "referralAnalytics",
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
                    'rotate' => -45,
                ],
                'tickPlacement' => 'on',
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
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'right',
            ],
            'responsive' => [
                [
                    'breakpoint' => 768, // Tablet breakpoint
                    'options' => [
                        'legend' => [
                            'position' => 'bottom',
                            'horizontalAlign' => 'center',
                        ],
                        'xaxis' => [
                            'tickAmount' => 8,
                            'labels' => [
                                'rotate' => -45,
                                'offsetY' => 5,
                            ]
                        ]
                    ]
                ],
                [
                    'breakpoint' => 480, // Mobile breakpoint
                    'options' => [
                        'chart' => [
                            'height' => 250,
                        ],
                        'xaxis' => [
                            'tickAmount' => 4, // Show fewer labels on mobile
                            'labels' => [
                                'rotate' => -90,
                                'offsetY' => 5,
                                'style' => [
                                    'fontSize' => '10px'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
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
} 