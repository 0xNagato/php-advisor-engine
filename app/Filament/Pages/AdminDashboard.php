<?php

namespace App\Filament\Pages;

use App\Livewire\Admin\AdminRecentBookings;
use App\Livewire\BookingsOverview;
use App\Livewire\Concierge\ConciergeOverallLeaderboard;
use App\Livewire\DateRangeFilterWidget;
use App\Livewire\Partner\PartnerOverallLeaderboard;
use App\Livewire\Venue\VenueOverallLeaderboard;
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
use Livewire\Attributes\On;

/**
 * @property Form $form
 */
class AdminDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static string $routePath = 'admin';

    protected static string $view = 'filament.pages.admin.admin-dashboard';

    public bool $isLoading = false;

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
            return null;
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
            DateRangeFilterWidget::make([
                'startDate' => $this->filters['startDate'],
                'endDate' => $this->filters['endDate'],
            ]),
            BookingsOverview::make([
                'filters' => [
                    'startDate' => $this->filters['startDate'] ?? null,
                    'endDate' => $this->filters['endDate'] ?? null,
                ],
            ]),
            AdminRecentBookings::make([
                'columnSpan' => '1',
            ]),
            VenueOverallLeaderboard::make([
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

    #[On('dateRangeUpdated')]
    public function updateDateRange(string $startDate, string $endDate): void
    {
        $this->isLoading = true;
        $this->filters['startDate'] = $startDate;
        $this->filters['endDate'] = $endDate;
        $this->isLoading = false;
    }
}
