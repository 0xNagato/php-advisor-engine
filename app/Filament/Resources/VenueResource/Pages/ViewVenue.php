<?php

namespace App\Filament\Resources\VenueResource\Pages;

use App\Filament\Resources\VenueResource;
use App\Livewire\Venue\VenueLeaderboard;
use App\Livewire\Venue\VenueRecentBookings;
use App\Livewire\VenueOverview;
use App\Models\Venue;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

/**
 * @method Venue getRecord()
 *
 * @property Venue $record
 */
class ViewVenue extends ViewRecord
{
    use HasFiltersAction;

    protected static string $resource = VenueResource::class;

    public function mount(int|string $record): void
    {
        $this->filters['startDate'] = $this->filters['startDate'] ?? now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] = $this->filters['endDate'] ?? now()->format('Y-m-d');

        parent::mount($record);
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->name;
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

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()
                ->iconButton()
                ->redirectTo(config('app.platform_url'))
                ->record($this->getRecord()->user),
            EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->iconButton(),
            FilterAction::make()
                ->label('Date Range')
                ->iconButton()
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->form([
                    Actions::make([
                        Actions\Action::make('last30Days')
                            ->label('Last 30 Days')
                            ->action(function (Set $set) {
                                $set('startDate', now()->subDays(30)->format('Y-m-d'));
                                $set('endDate', now()->format('Y-m-d'));
                            }),
                        Actions\Action::make('monthToDate')
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

    protected function getHeaderWidgets(): array
    {
        return [
            VenueOverview::make(['venue' => $this->getRecord(), 'columnSpan' => 'full']),
            // VenueStats::make(['venue' => $this->getRecord(), 'columnSpan' => 'full']),
            VenueRecentBookings::make(['venue' => $this->getRecord(), 'columnSpan' => '1']),
            VenueLeaderboard::make(['venue' => $this->getRecord(), 'columnSpan' => '1']),
        ];
    }
}
