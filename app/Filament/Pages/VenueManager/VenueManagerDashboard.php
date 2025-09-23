<?php

namespace App\Filament\Pages\VenueManager;

use App\Filament\DateRangeFilterAction;
use App\Livewire\DateRangeFilterWidget;
use App\Livewire\VenueManager\VenueGroupOverview;
use App\Livewire\VenueManager\VenueGroupRecentBookings;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueGroup;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;

class VenueManagerDashboard extends Dashboard
{
    use HasFiltersAction;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.pages.venue-manager.manager-dashboard';

    protected static string $routePath = 'venue-manager';

    public ?VenueGroup $venueGroup = null;

    /** @var Collection<int, Venue> */
    public Collection $venues;

    public static function getNavigationLabel(): string
    {
        return (auth()->user()?->currentVenueGroup()?->name ?? 'Venue Group').' Overview';
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function getHeading(): string
    {
        return static::getNavigationLabel();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasActiveRole('venue_manager');
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->hasActiveRole('venue_manager'), 403, 'You are not authorized to access this page');

        $this->venueGroup = auth()->user()->currentVenueGroup();

        /** @var User $user */
        $user = auth()->user();
        $allowedVenueIds = $this->venueGroup?->getAllowedVenueIds($user) ?? [];

        $this->venues = $this->venueGroup?->venues()
            ->when(
                filled($allowedVenueIds),
                fn ($query) => $query->whereIn('id', $allowedVenueIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->get() ?? new Collection;

        $this->filters['startDate'] ??= now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] ??= now()->format('Y-m-d');
    }

    public function getSubheading(): string|null|Htmlable
    {
        if (! isset($this->filters['startDate'], $this->filters['endDate'])) {
            return null;
        }

        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);

        return "{$startDate->format('M j')} - {$endDate->format('M j')}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_venue')
                ->label('Add New Venue')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(route('venue-manager.add-venue'))
                ->visible(fn () => auth()->user()->hasActiveRole('venue_manager')),
            DateRangeFilterAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DateRangeFilterWidget::make([
                'startDate' => $this->filters['startDate'],
                'endDate' => $this->filters['endDate'],
            ]),
            VenueGroupOverview::make([
                'venues' => $this->venues,
                'columnSpan' => 'full',
                'startDate' => Carbon::parse($this->filters['startDate']),
                'endDate' => Carbon::parse($this->filters['endDate']),
            ]),
            VenueGroupRecentBookings::make([
                'venues' => $this->venues,
                'columnSpan' => 'full',
            ]),
        ];
    }

    #[On('dateRangeUpdated')]
    public function updateDateRange(string $startDate, string $endDate): void
    {
        $this->filters['startDate'] = $startDate;
        $this->filters['endDate'] = $endDate;
    }
}
