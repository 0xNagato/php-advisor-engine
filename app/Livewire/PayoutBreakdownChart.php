<?php

namespace App\Livewire;

use App\Enums\EarningType;
use App\Models\Booking;
use App\Models\Earning;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class PayoutBreakdownChart extends ChartWidget
{
    public ?Booking $booking = null;

    protected function getData(): array
    {
        if (! $this->booking) {
            return [];
        }

        $earnings = Earning::with('user')
            ->where('booking_id', $this->booking->id)
            ->where('type', '!=', EarningType::REFUND)
            ->get()->sumByUserAndType();

        $data = $earnings->map(fn ($earning) => [
            'label' => $earning['user']->name.' ('.Str::title(str_replace('_', ' ', $earning['type'])).')',
            'value' => $earning['amount'],
        ])->toArray();

        $data[] = [
            'label' => 'PRIMA',
            'value' => $this->booking->final_platform_earnings_total,
        ];

        return [
            'datasets' => [
                [
                    'data' => collect($data)->pluck('value')->toArray(),
                    'backgroundColor' => [
                        'rgba(79, 70, 229, 0.8)',   // indigo-600
                        'rgba(249, 115, 22, 0.8)',  // orange-500
                        'rgba(20, 184, 166, 0.8)',  // teal-500
                        'rgba(244, 63, 94, 0.8)',   // rose-500
                        'rgba(245, 158, 11, 0.8)',  // amber-500
                        'rgba(34, 197, 94, 0.8)',   // green-500
                        'rgba(16, 185, 129, 0.8)',  // green-600
                        'rgba(100, 116, 139, 0.8)', // slate-500
                        'rgba(71, 85, 105, 0.8)',   // slate-600
                        'rgba(99, 102, 241, 0.8)',  // indigo-500
                        'rgba(129, 140, 248, 0.8)', // indigo-400
                        'rgba(251, 146, 60, 0.8)',  // orange-400
                    ],
                ],
            ],
            'labels' => collect($data)->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): RawJs
    {
        $currency = $this->booking?->currency ?? 'USD';

        return RawJs::make(<<<JS
        {
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            const value = context.raw;
                            if (value !== null) {
                                const formattedValue = new Intl.NumberFormat('en-US', {
                                    style: 'currency',
                                    currency: '{$currency}',
                                    minimumFractionDigits: 2
                                }).format(value / 100);
                                label += formattedValue;
                            }
                            return label;
                        }
                    }
                }
            }
        }
        JS
        );
    }
}
