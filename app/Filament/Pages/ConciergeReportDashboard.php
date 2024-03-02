<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;

class ConciergeReportDashboard extends Dashboard
{
    use Dashboard\Concerns\HasFiltersAction;

    protected static ?string $title = 'My Earnings';

    protected static string $routePath = 'concierge/report';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected ?string $heading = 'My Earnings';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('concierge');
    }

    public function mount(): void
    {
        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Dashboard\Actions\FilterAction::make()
                ->label('Date Range')
                ->icon('heroicon-o-calendar')
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                    // ...
                ]),
        ];
    }

    // public function filtersForm(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             Section::make()
    //                 ->schema([
    //                     DatePicker::make('startDate')
    //                         ->default(now()->subDays(30)),
    //                     DatePicker::make('endDate')
    //                         ->default(now()),
    //                 ])
    //                 ->columns(2),
    //         ]);
    // }
}
