<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;

class AdminDashboard extends Dashboard
{
    use Dashboard\Concerns\HasFiltersAction;

    protected static ?string $title = 'Dashboard';

    protected static string $view = 'filament.resources.concierges.pages.view-concierge';

    protected static string $routePath = 'admin';

    // protected ?string $heading = '';

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

    protected function getHeaderActions(): array
    {
        return [
            Dashboard\Actions\FilterAction::make()
                ->label('Date Range')
                ->iconButton()
                ->icon('heroicon-o-calendar')
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                    // ...
                ]),
            Action::Make('addConcierge')
                ->label('Concierge')
                ->link()
                ->icon('govicon-user-suit')
                ->iconButton()
                ->url(fn (): string => route('filament.admin.resources.concierges.create')),
            Action::Make('addRestaurant')
                ->label('Restaurant')
                ->link()
                ->iconButton()
                ->icon('heroicon-o-building-storefront')
                ->url(fn (): string => route('filament.admin.resources.restaurants.create')),
            Action::Make('addPartner')
                ->label('Partner')
                ->link()
                ->iconButton()
                ->icon('gmdi-business-center-o')
                ->url(fn (): string => route('filament.admin.resources.partners.create')),
        ];
    }
}
