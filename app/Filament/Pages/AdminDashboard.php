<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class AdminDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Admin Dashboard';

    protected static string $routePath = 'admin';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin');
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

    protected function getHeaderActions(): array
    {
        return [
            Action::Make('addConcierge')
                ->label('Concierge')
                ->link()
                ->icon('heroicon-m-plus-circle')
                ->url(fn(): string => route('filament.admin.resources.concierges.create')),
            Action::Make('addRestaurant')
                ->label('Restaurant')
                ->link()
                ->icon('heroicon-m-plus-circle')
                ->url(fn(): string => route('filament.admin.resources.restaurants.create')),
        ];
    }
}
