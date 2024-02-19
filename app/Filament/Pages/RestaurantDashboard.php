<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class RestaurantDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Restaurant Dashboard';

    protected static string $routePath = 'restaurant';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('restaurant');
    }

    public function mount(): void
    {
        $this->filters = [
            'startDate' => $this->filters['startDate'] ?? now()->subDays(30),
            'endDate' => $this->filters['endDate'] ?? now(),
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->default(now()->subDays(30)),
                        DatePicker::make('endDate')
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }
}
