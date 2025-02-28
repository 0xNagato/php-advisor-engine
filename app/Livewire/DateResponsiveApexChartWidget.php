<?php

namespace App\Livewire;

use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Livewire\Attributes\On;

/**
 * A base class for ApexChart widgets that need to respond to date range changes.
 * This class extends the default ApexChartWidget and adds functionality for:
 * - Listening for dateRangeUpdated events
 * - Updating the chart when date filters change
 * - Preventing duplicate charts
 * - Handling mobile responsiveness
 */
abstract class DateResponsiveApexChartWidget extends ApexChartWidget
{
    use InteractsWithPageFilters;

    /**
     * The default height of the chart content
     */
    protected static ?int $contentHeight = 300;

    /**
     * The default column span of the widget
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * Disable polling by default
     */
    protected static ?string $pollingInterval = null;

    /**
     * Set to true to enable automatic loading when mounted 
     * Set to false if you want to defer loading
     */
    protected bool $autoLoad = true;
    
    /**
     * Set to true to make the widget lazy-loaded
     */
    protected static bool $isLazy = false;
    
    /**
     * Set to false initially and true after mounting
     */
    protected bool $isMounted = false;

    /**
     * Initialize the widget
     */
    public function mount(): void
    {
        parent::mount();
        
        $this->isMounted = true;
        
        if ($this->autoLoad) {
            $this->readyToLoad = true;
        }
    }

    /**
     * Get the column span for the widget
     */
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
        
        // Reset readyToLoad to refresh chart data
        $this->readyToLoad = true;
        
        // Force chart update with clean options
        $this->updateOptions();
        
        // Dispatch an event to ensure the chart is refreshed in the DOM
        $this->dispatch('chartUpdated', chartId: $this->getChartId());
    }

    /**
     * Get the chart ID for the current instance
     */
    protected function getChartId(): string
    {
        // Use static chartId if set, otherwise use a default based on class name
        return static::$chartId ?? lcfirst(class_basename($this));
    }

    /**
     * Parse date from filters with timezone handling
     * 
     * @param string $key The filter key to get
     * @param string|null $default The default date if not found (can be a relative date string like '-30 days')
     * @param bool $endOfDay Whether to set to end of day or start of day
     * @return Carbon
     */
    protected function getDateFromFilters(string $key, ?string $default = null, bool $endOfDay = false): Carbon
    {
        $userTimezone = auth()->user()->timezone ?? config('app.default_timezone');
        
        $dateString = $this->filters[$key] ?? null;
        
        if (!$dateString && $default) {
            $dateString = $default[0] === '-' || $default[0] === '+' 
                ? now($userTimezone)->modify($default)->format('Y-m-d')
                : $default;
        }
        
        $date = Carbon::parse($dateString, $userTimezone);
        
        if ($endOfDay) {
            $date->endOfDay();
        } else {
            $date->startOfDay();
        }
        
        return $date->setTimezone('UTC');
    }

    /**
     * Get start date from filters with timezone handling
     * Default is 30 days ago if not specified
     */
    protected function getStartDate(): Carbon
    {
        return $this->getDateFromFilters('startDate', '-30 days');
    }

    /**
     * Get end date from filters with timezone handling
     * Default is today if not specified
     */
    protected function getEndDate(): Carbon
    {
        return $this->getDateFromFilters('endDate', 'today', true);
    }
    
    /**
     * Add custom JavaScript to be included before the chart initialization
     * Override this method in your subclass to add custom JavaScript
     */
    protected function getCustomJsBeforeChart(): ?string
    {
        return null;
    }
    
    /**
     * Add custom JavaScript to be included after the chart initialization
     * Override this method in your subclass to add custom JavaScript
     */
    protected function getCustomJsAfterChart(): ?string
    {
        return null;
    }

    /**
     * Add custom JavaScript to handle mobile responsiveness and prevent duplicate charts
     */
    protected function extraJsOptions(): ?RawJs
    {
        // Get the ID directly from the method to ensure consistency
        $chartId = $this->getChartId();
        $beforeJs = $this->getCustomJsBeforeChart();
        $afterJs = $this->getCustomJsAfterChart();
        
        return RawJs::make(<<<JS
        {
            chart: {
                events: {
                    beforeMount: function(chart) {
                        // Clean up any previous chart with the same ID
                        const chartId = "{$chartId}-chart";
                        const existingCharts = document.querySelectorAll('[id="' + chartId + '"]');
                        
                        if (existingCharts.length > 1) {
                            console.log('Found existing charts with ID: ' + chartId + ', cleaning up...');
                            for (let i = 0; i < existingCharts.length - 1; i++) {
                                if (existingCharts[i].parentNode) {
                                    try {
                                        existingCharts[i].parentNode.removeChild(existingCharts[i]);
                                    } catch (e) {
                                        console.error('Error removing existing chart:', e);
                                    }
                                }
                            }
                        }
                        
                        // Execute custom JS before chart initialization
                        {$beforeJs}
                    },
                    mounted: function(chart) {
                        // Handle any duplicate charts that were created during initialization
                        setTimeout(function() {
                            const chartId = "{$chartId}-chart";
                            const charts = document.querySelectorAll('[id="' + chartId + '"]');
                            
                            if (charts.length > 1) {
                                console.log('Found duplicate charts after mount, cleaning up...');
                                // Keep only the last chart (most recently created)
                                const keepChart = charts[charts.length - 1];
                                
                                charts.forEach(function(el) {
                                    if (el !== keepChart && el.parentNode) {
                                        try {
                                            el.parentNode.removeChild(el);
                                        } catch (e) {
                                            console.error('Error removing duplicate chart:', e);
                                        }
                                    }
                                });
                            }
                        }, 100);
                        
                        // Execute custom JS after chart initialization
                        {$afterJs}
                    },
                    updated: function(chart) {
                        // Chart was updated
                        console.log('Chart updated:', chart);
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
     * Get default chart options that can be extended by child classes
     */
    protected function getDefaultOptions(): array
    {
        return [
            'chart' => [
                'type' => 'area',
                'height' => static::$contentHeight ?? 300,
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
                'id' => $this->getChartId(),
            ],
            'xaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 600,
                    ],
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
            'colors' => ['#4f46e5', '#10b981'], // Default colors - indigo and emerald
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
        ];
    }
} 