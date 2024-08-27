<?php

namespace App\Filament\Pages;

use App\Livewire\Admin\AdminRecentBookings;
use App\Livewire\BookingsOverview;
use App\Livewire\Concierge\ConciergeOverallLeaderboard;
use App\Livewire\Partner\PartnerOverallLeaderboard;
use App\Livewire\Venue\VenueLeaderboard;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property Form $form
 */
class AdminDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static string $routePath = 'admin';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    public function mount(): void
    {
        $this->filters['startDate'] ??= now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] ??= now()->format('Y-m-d');
    }

    public function getHeading(): string|Htmlable
    {
        return 'Dashboard';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! isset($this->filters['startDate'], $this->filters['endDate'])) {
            return null; // or return a default value like 'N/A' or an empty string
        }

        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);

        $formattedStartDate = $startDate->format('M j');
        $formattedEndDate = $endDate->format('M j');

        return $formattedStartDate.' - '.$formattedEndDate;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BookingsOverview::make([
                'filters' => [
                    'startDate' => $this->filters['startDate'] ?? null,
                    'endDate' => $this->filters['endDate'] ?? null,
                ],
            ]),
            AdminRecentBookings::make([
                'columnSpan' => '1',
            ]),
            VenueLeaderboard::make([
                'columnSpan' => '1',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            ConciergeOverallLeaderboard::make([
                'columnSpan' => '1',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            PartnerOverallLeaderboard::make([
                'columnSpan' => '1',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Date Range')
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
                ]),
        ];
    }
}
