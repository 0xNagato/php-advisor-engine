<?php

namespace App\Filament;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;
use Filament\Pages\Dashboard\Actions\FilterAction;

class DateRangeFilterAction extends FilterAction
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->label('Date Range')
            ->iconButton()
            ->icon('heroicon-o-calendar')
            ->color('primary')
            ->form([
                Actions::make([
                    Action::make('last30Days')
                        ->label('Last 30 Days')
                        ->action(function (Set $set) {
                            $set('startDate', now()->subDays(30)->format('Y-m-d'));
                            $set('endDate', now()->format('Y-m-d'));
                        }),
                    Action::make('monthToDate')
                        ->label('Month to Date')
                        ->action(function (Set $set) {
                            $set('startDate', now()->startOfMonth()->format('Y-m-d'));
                            $set('endDate', now()->format('Y-m-d'));
                        }),
                ]),
                DatePicker::make('startDate')
                    ->native(false),
                DatePicker::make('endDate')
                    ->native(false),
            ]);
    }
}
